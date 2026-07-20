<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class RetornoFaccaoController extends Controller
{
    /**
     * Listar envios e retornos de facção.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // Envios para Facções
        $envios = Database::fetchAll(
            "SELECT ef.*, op.id as op_id, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome
             FROM envios_faccao ef
             JOIN ordens_producao op ON ef.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             JOIN oficinas_faccoes of ON ef.oficina_faccao_id = of.id
             WHERE ef.tenant_id = :tenant_id
             ORDER BY ef.id DESC",
            ['tenant_id' => $tenantId]
        );

        // Retornos de Facções
        $retornos = Database::fetchAll(
            "SELECT rf.*, op.id as op_id, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome
             FROM retornos_faccao rf
             JOIN ordens_producao op ON rf.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             LEFT JOIN envios_faccao ef ON rf.envio_faccao_id = ef.id
             LEFT JOIN oficinas_faccoes of ON (ef.oficina_faccao_id = of.id OR op.oficina_faccao_id = of.id)
             WHERE rf.tenant_id = :tenant_id
             ORDER BY rf.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('retornos/index', [
            'title' => 'Gestão de Facções (Envios e Retornos)',
            'subtitle' => 'Envie lotes cortados para oficinas terceirizadas, acompanhe os retornos e apure as perdas',
            'envios' => $envios,
            'retornos' => $retornos
        ]);
    }

    /**
     * Formulário para Enviar Lote/Corte para Facção.
     */
    public function envioForm(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // OPs com corte realizado ou em andamento
        $ops = Database::fetchAll(
            "SELECT op.id, op.quantidade, pm.nome as modelo_nome, pm.referencia,
                    (SELECT COALESCE(SUM(quantidade_cortada), 0) FROM ordens_corte WHERE ordem_producao_id = op.id) as total_cortado
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id AND op.status IN ('aberta', 'em andamento')
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        $oficinas = Database::fetchAll(
            "SELECT id, nome, capacidade_produtiva FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('retornos/envio', [
            'title' => 'Enviar Lote para Facção',
            'subtitle' => 'Selecione a OP/Corte e atribua a quantidade enviada para a oficina terceirizada',
            'ops' => $ops,
            'oficinas' => $oficinas,
            'action' => '/retornos/envio'
        ]);
    }

    /**
     * Processar Envio para Facção.
     */
    public function storeEnvio(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $ordem_producao_id = (int)($_POST['ordem_producao_id'] ?? 0);
        $oficina_faccao_id = (int)($_POST['oficina_faccao_id'] ?? 0);
        $quantidade_enviada = (int)($_POST['quantidade_enviada'] ?? 0);
        $etapa_destino = trim($_POST['etapa_destino'] ?? 'Costura');
        $data_envio = $_POST['data_envio'] ?? date('Y-m-d');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($ordem_producao_id <= 0 || $oficina_faccao_id <= 0 || $quantidade_enviada <= 0) {
            $this->setFlash('error', 'Preencha a OP, Oficina e a Quantidade Enviada corretamente.');
            $this->redirect('/retornos/envio');
        }

        Database::query(
            "INSERT INTO envios_faccao (tenant_id, ordem_producao_id, oficina_faccao_id, quantidade_enviada, etapa_destino, data_envio, status, observacoes) 
             VALUES (:tenant_id, :op_id, :oficina_id, :qtd, :etapa, :data_envio, 'enviado', :obs)",
            [
                'tenant_id'  => $tenantId,
                'op_id'      => $ordem_producao_id,
                'oficina_id' => $oficina_faccao_id,
                'qtd'        => $quantidade_enviada,
                'etapa'      => $etapa_destino,
                'data_envio' => $data_envio,
                'obs'        => $observacoes
            ]
        );

        // Atualizar status da OP para em andamento se estivesse aberta
        Database::query(
            "UPDATE ordens_producao SET status = 'em andamento' WHERE id = :op_id AND status = 'aberta'",
            ['op_id' => $ordem_producao_id]
        );

        $this->setFlash('success', 'Lote enviado para a facção com sucesso!');
        $this->redirect('/retornos');
    }

    /**
     * Formulário de Retorno da Facção.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // Envios pendentes de retorno
        $envios = Database::fetchAll(
            "SELECT ef.*, op.id as op_id, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome, of.mao_obra_peca
             FROM envios_faccao ef
             JOIN ordens_producao op ON ef.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             JOIN oficinas_faccoes of ON ef.oficina_faccao_id = of.id
             WHERE ef.tenant_id = :tenant_id AND ef.status IN ('enviado', 'retornado_parcial')
             ORDER BY ef.id DESC",
            ['tenant_id' => $tenantId]
        );

        // OPs ativas como fallback
        $opsFallback = Database::fetchAll(
            "SELECT op.id, op.quantidade, pm.nome as modelo_nome, pm.referencia
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id AND op.status IN ('aberta', 'em andamento')
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('retornos/form', [
            'title' => 'Lançar Retorno de Oficina / Facção',
            'subtitle' => 'Apure a quantidade de peças boas entregues, as perdas e as etapas concluídas',
            'envios' => $envios,
            'opsFallback' => $opsFallback,
            'action' => '/retornos/novo'
        ]);
    }

    /**
     * Salvar Retorno da Facção.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $userId = $_SESSION['user_id'] ?? null;

        $envio_faccao_id = !empty($_POST['envio_faccao_id']) ? (int)$_POST['envio_faccao_id'] : null;
        $ordem_producao_id = (int)($_POST['ordem_producao_id'] ?? 0);
        $quantidade_enviada = (int)($_POST['quantidade_enviada'] ?? 0);
        $quantidade_retornada_boa = (int)($_POST['quantidade_retornada_boa'] ?? 0);
        $quantidade_defeito_perda = (int)($_POST['quantidade_defeito_perda'] ?? 0);
        $motivo_defeito = trim($_POST['motivo_defeito'] ?? '');
        $data_retorno = $_POST['data_retorno'] ?? date('Y-m-d');
        
        $etapas_array = $_POST['etapas_concluidas'] ?? ['costura'];
        $etapas_concluidas_str = is_array($etapas_array) ? implode(', ', $etapas_array) : (string)$etapas_array;

        if ($ordem_producao_id <= 0 || $quantidade_retornada_boa < 0 || $quantidade_defeito_perda < 0) {
            $this->setFlash('error', 'Preencha os dados de quantidades de retorno corretamente.');
            $this->redirect('/retornos/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $oficinaId = null;
            $maoObraPeca = 0.0;

            if ($envio_faccao_id) {
                $envio = Database::fetch(
                    "SELECT ef.*, of.id as oficina_id, of.mao_obra_peca 
                     FROM envios_faccao ef 
                     JOIN oficinas_faccoes of ON ef.oficina_faccao_id = of.id 
                     WHERE ef.id = :id AND ef.tenant_id = :tenant_id",
                    ['id' => $envio_faccao_id, 'tenant_id' => $tenantId]
                );
                if ($envio) {
                    $oficinaId = $envio['oficina_id'];
                    $maoObraPeca = (float)$envio['mao_obra_peca'];
                }
            }

            if (!$oficinaId) {
                $op = Database::fetch(
                    "SELECT op.*, of.id as oficina_id, of.mao_obra_peca 
                     FROM ordens_producao op 
                     LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id 
                     WHERE op.id = :id AND op.tenant_id = :tenant_id",
                    ['id' => $ordem_producao_id, 'tenant_id' => $tenantId]
                );
                if ($op) {
                    $oficinaId = $op['oficina_id'];
                    $maoObraPeca = (float)($op['mao_obra_peca'] ?? 0);
                }
            }

            // 1. Inserir retorno
            $stmt = $db->prepare(
                "INSERT INTO retornos_faccao (tenant_id, ordem_producao_id, envio_faccao_id, quantidade_enviada, quantidade_retornada_boa, quantidade_defeito_perda, etapas_concluidas, motivo_defeito, data_retorno) 
                 VALUES (:tenant_id, :ordem_producao_id, :envio_faccao_id, :quantidade_enviada, :quantidade_retornada_boa, :quantidade_defeito_perda, :etapas, :motivo, :data_retorno)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'ordem_producao_id' => $ordem_producao_id,
                'envio_faccao_id' => $envio_faccao_id,
                'quantidade_enviada' => $quantidade_enviada,
                'quantidade_retornada_boa' => $quantidade_retornada_boa,
                'quantidade_defeito_perda' => $quantidade_defeito_perda,
                'etapas' => $etapas_concluidas_str,
                'motivo' => $motivo_defeito,
                'data_retorno' => $data_retorno
            ]);
            $retornoId = $db->lastInsertId();

            // 2. Atualizar status do envio (se houver)
            if ($envio_faccao_id) {
                $statusEnvio = ($quantidade_retornada_boa + $quantidade_defeito_perda >= $quantidade_enviada) ? 'retornado_total' : 'retornado_parcial';
                $db->prepare("UPDATE envios_faccao SET status = :st WHERE id = :id")
                   ->execute(['st' => $statusEnvio, 'id' => $envio_faccao_id]);
            }

            // 3. Registrar perda na tabela controle_qualidade se houver defeitos
            if ($quantidade_defeito_perda > 0) {
                $db->prepare(
                    "INSERT INTO controle_qualidade (tenant_id, ordem_producao_id, quantidade_aprovada, quantidade_reprovada, tipo_defeito, responsavel) 
                     VALUES (:tenant_id, :op_id, :aprov, :reprov, :tipo, 'Retorno de Facção')"
                )->execute([
                    'tenant_id' => $tenantId,
                    'op_id' => $ordem_producao_id,
                    'aprov' => $quantidade_retornada_boa,
                    'reprov' => $quantidade_defeito_perda,
                    'tipo' => $motivo_defeito ?: 'Defeito / Perda em Facção'
                ]);
            }

            // 4. Gerar faturamento devido à Oficina (contas a pagar)
            if ($oficinaId && $maoObraPeca > 0) {
                $valorDevido = $quantidade_retornada_boa * $maoObraPeca;
                $stmtFin = $db->prepare(
                    "INSERT INTO financeiro_faccoes (tenant_id, oficina_faccao_id, retorno_faccao_id, valor_devido, valor_pago, status) 
                     VALUES (:tenant_id, :oficina_id, :retorno_id, :valor_devido, 0.00, 'pendente')"
                );
                $stmtFin->execute([
                    'tenant_id' => $tenantId,
                    'oficina_id' => $oficinaId,
                    'retorno_id' => $retornoId,
                    'valor_devido' => $valorDevido
                ]);
            }

            // 5. Atualizar etapas no Chão de Fábrica conforme indicado pela facção
            foreach ($etapas_array as $etapaNorm) {
                $etapaClean = strtolower(trim($etapaNorm));
                $db->prepare(
                    "UPDATE chao_fabrica_etapas 
                     SET status = 'concluído' 
                     WHERE ordem_producao_id = :op_id AND LOWER(etapa) = :etapa"
                )->execute(['op_id' => $ordem_producao_id, 'etapa' => $etapaClean]);
            }

            // Colocar a próxima etapa pendente em andamento no Chão de Fábrica
            $proximaEtapa = Database::fetch(
                "SELECT id FROM chao_fabrica_etapas WHERE ordem_producao_id = :op_id AND status = 'pendente' ORDER BY id ASC LIMIT 1",
                ['op_id' => $ordem_producao_id]
            );
            if ($proximaEtapa) {
                Database::query(
                    "UPDATE chao_fabrica_etapas SET status = 'em andamento' WHERE id = :id",
                    ['id' => $proximaEtapa['id']]
                );
            }

            $db->commit();
            $this->setFlash('success', 'Retorno de facção gravado com sucesso. Etapas atualizadas no Chão de Fábrica.');
            $this->redirect('/retornos');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao salvar retorno: ' . $e->getMessage());
            $this->redirect('/retornos/novo');
        }
    }
}
