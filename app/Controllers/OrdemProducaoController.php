<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class OrdemProducaoController extends Controller
{
    /**
     * Listar ordens de produção.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $busca = trim($_GET['busca'] ?? '');

        if (!empty($busca)) {
            $ops = Database::fetchAll(
                "SELECT op.*, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome, pv.cliente as cliente_nome
                 FROM ordens_producao op
                 JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
                 LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
                 LEFT JOIN pedidos_venda pv ON op.pedido_venda_id = pv.id
                 WHERE op.tenant_id = :tenant_id 
                   AND (pm.nome LIKE :busca OR pm.referencia LIKE :busca2 OR of.nome LIKE :busca3)
                 ORDER BY op.id DESC",
                [
                    'tenant_id' => $tenantId,
                    'busca'     => '%' . $busca . '%',
                    'busca2'    => '%' . $busca . '%',
                    'busca3'    => '%' . $busca . '%',
                ]
            );
        } else {
            $ops = Database::fetchAll(
                "SELECT op.*, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome, pv.cliente as cliente_nome
                 FROM ordens_producao op
                 JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
                 LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
                 LEFT JOIN pedidos_venda pv ON op.pedido_venda_id = pv.id
                 WHERE op.tenant_id = :tenant_id 
                 ORDER BY op.id DESC",
                ['tenant_id' => $tenantId]
            );
        }

        // Buscar variações de cada OP
        foreach ($ops as &$op) {
            $op['variantes'] = Database::fetchAll(
                "SELECT opv.quantidade, pv.cor, pv.tamanho 
                 FROM ordens_producao_variantes opv
                 JOIN produtos_variantes pv ON opv.produto_variante_id = pv.id
                 WHERE opv.ordem_producao_id = :op_id AND opv.tenant_id = :tenant_id",
                ['op_id' => $op['id'], 'tenant_id' => $tenantId]
            );
        }

        $this->render('ops/index', [
            'title' => 'Ordens de Produção (OP)',
            'subtitle' => 'Planeje, distribua para oficinas e acompanhe a fabricação de lotes de roupas',
            'ops' => $ops,
            'busca' => $busca
        ]);
    }

    /**
     * Formulário de criação de OP.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia FROM produtos_modelos WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $oficinas = Database::fetchAll(
            "SELECT id, nome FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        // Pedidos pendentes de venda para vincular
        $pedidos = Database::fetchAll(
            "SELECT pv.id, pv.cliente, pv.quantidade, pv.tamanho, pm.referencia 
             FROM pedidos_venda pv
             JOIN produtos_modelos pm ON pv.produto_modelo_id = pm.id
             WHERE pv.tenant_id = :tenant_id AND pv.status = 'pendente'
             ORDER BY pv.id DESC",
            ['tenant_id' => $tenantId]
        );

        // Carregar fichas e insumos para simulação
        $fichasModelos = [];
        $fichasRaw = Database::fetchAll(
            "SELECT ft.produto_modelo_id, ft.tempo_padrao, fti.materia_prima_id, fti.quantidade, mp.nome as materia_nome, mp.unidade_medida
             FROM fichas_tecnicas ft
             LEFT JOIN fichas_tecnicas_itens fti ON ft.id = fti.ficha_tecnica_id
             LEFT JOIN materias_primas mp ON fti.materia_prima_id = mp.id
             WHERE ft.tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        );

        foreach ($fichasRaw as $frow) {
            $mId = $frow['produto_modelo_id'];
            if (!isset($fichasModelos[$mId])) {
                $fichasModelos[$mId] = [
                    'tempo_padrao' => (float)$frow['tempo_padrao'],
                    'insumos' => []
                ];
            }
            if ($frow['materia_prima_id']) {
                $fichasModelos[$mId]['insumos'][] = [
                    'materia_prima_id' => $frow['materia_prima_id'],
                    'nome' => $frow['materia_nome'],
                    'quantidade' => (float)$frow['quantidade'],
                    'unidade_medida' => $frow['unidade_medida']
                ];
            }
        }
        $variantes = Database::fetchAll(
            "SELECT id, produto_modelo_id, cor, tamanho FROM produtos_variantes WHERE tenant_id = :tenant_id ORDER BY cor, tamanho ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('ops/form', [
            'title' => 'Nova Ordem de Produção',
            'subtitle' => 'Crie uma nova OP vinculada a modelo ou pedido comercial',
            'op' => null,
            'modelos' => $modelos,
            'oficinas' => $oficinas,
            'pedidos' => $pedidos,
            'fichasModelos' => $fichasModelos,
            'variantes' => $variantes,
            'opVariantesSelected' => [],
            'action' => '/ops/novo'
        ]);
    }

    /**
     * Gravar nova OP.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $pedido_venda_id = !empty($_POST['pedido_venda_id']) ? (int)$_POST['pedido_venda_id'] : null;
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        
        $variante_ids = $_POST['variante_ids'] ?? [];
        $variante_quantidades = $_POST['variante_quantidades'] ?? [];
        
        $oficina_faccao_id = !empty($_POST['oficina_faccao_id']) ? (int)$_POST['oficina_faccao_id'] : null;
        $operadores = (int)($_POST['operadores'] ?? 1);
        $prazo = $_POST['prazo'] ?? '';
        $status = $_POST['status'] ?? 'aberta';

        $quantidade = 0;
        $variantesSalvar = [];
        foreach ($variante_ids as $index => $vId) {
            $vId = (int)$vId;
            $qtd = (int)($variante_quantidades[$index] ?? 0);
            if ($vId > 0 && $qtd > 0) {
                $quantidade += $qtd;
                $variantesSalvar[] = [
                    'variante_id' => $vId,
                    'quantidade' => $qtd
                ];
            }
        }

        if ($produto_modelo_id <= 0 || $quantidade <= 0 || empty($prazo)) {
            $this->setFlash('error', 'Preencha os campos obrigatórios (Modelo, Variações/Quantidades e Prazo).');
            $this->redirect('/ops/novo');
        }

        // Salvar a primeira variante na coluna legada para fins de compatibilidade
        $produto_variante_id = !empty($variantesSalvar) ? $variantesSalvar[0]['variante_id'] : null;

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir OP
            $stmt = $db->prepare(
                "INSERT INTO ordens_producao (tenant_id, pedido_venda_id, produto_modelo_id, produto_variante_id, oficina_faccao_id, quantidade, operadores, prazo, status) 
                  VALUES (:tenant_id, :pedido_venda_id, :produto_modelo_id, :produto_variante_id, :oficina_faccao_id, :quantidade, :operadores, :prazo, :status)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'pedido_venda_id' => $pedido_venda_id,
                'produto_modelo_id' => $produto_modelo_id,
                'produto_variante_id' => $produto_variante_id,
                'oficina_faccao_id' => $oficina_faccao_id,
                'quantidade' => $quantidade,
                'operadores' => $operadores,
                'prazo' => $prazo,
                'status' => $status
            ]);
            $opId = $db->lastInsertId();

            // 1.2 Inserir na tabela pivot ordens_producao_variantes
            $stmtVar = $db->prepare(
                "INSERT INTO ordens_producao_variantes (tenant_id, ordem_producao_id, produto_variante_id, quantidade) 
                 VALUES (:tenant_id, :op_id, :variante_id, :quantidade)"
            );
            foreach ($variantesSalvar as $v) {
                $stmtVar->execute([
                    'tenant_id' => $tenantId,
                    'op_id' => $opId,
                    'variante_id' => $v['variante_id'],
                    'quantidade' => $v['quantidade']
                ]);
            }

            // Se for criada com status 'em andamento', realizar a baixa automática de estoque
            if ($status === 'em andamento') {
                $this->baixarEstoqueOP($db, $tenantId, $opId, $produto_modelo_id, $quantidade);
            }

            // 2. Criar automaticamente as 5 etapas no Chão de Fábrica para cada variação
            $stmtEtapa = $db->prepare(
                "INSERT INTO chao_fabrica_etapas (tenant_id, ordem_producao_id, produto_variante_id, etapa, status) 
                 VALUES (:tenant_id, :op_id, :variante_id, :etapa, 'pendente')"
            );

            $etapas = ['corte', 'costura', 'acabamento', 'revisão', 'embalagem'];
            foreach ($variantesSalvar as $v) {
                foreach ($etapas as $et) {
                    $stmtEtapa->execute([
                        'tenant_id' => $tenantId,
                        'op_id' => $opId,
                        'variante_id' => $v['variante_id'],
                        'etapa' => $et
                    ]);
                }
            }

            if (empty($variantesSalvar)) {
                // Fallback para OPs sem variações
                foreach ($etapas as $et) {
                    $stmtEtapa->execute([
                        'tenant_id' => $tenantId,
                        'op_id' => $opId,
                        'variante_id' => null,
                        'etapa' => $et
                    ]);
                }
            }

            // 3. Se estiver vinculada a um pedido de venda, atualizar o status do pedido para "em produção"
            if ($pedido_venda_id) {
                $db->prepare("UPDATE pedidos_venda SET status = 'em produção' WHERE id = :pedido_id")
                   ->execute(['pedido_id' => $pedido_venda_id]);
            }

            $db->commit();
            $this->setFlash('success', 'Ordem de Produção (OP) criada com sucesso.');
            $this->redirect('/ops');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao criar OP: ' . $e->getMessage());
            $this->redirect('/ops/novo');
        }
    }

    /**
     * Formulário de edição de OP.
     */
    public function edit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $op = Database::fetch(
            "SELECT * FROM ordens_producao WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$op) {
            $this->setFlash('error', 'Ordem de Produção não encontrada.');
            $this->redirect('/ops');
        }

        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia FROM produtos_modelos WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $oficinas = Database::fetchAll(
            "SELECT id, nome FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $pedidos = Database::fetchAll(
            "SELECT pv.id, pv.cliente, pv.quantidade, pv.tamanho, pm.referencia 
             FROM pedidos_venda pv
             JOIN produtos_modelos pm ON pv.produto_modelo_id = pm.id
             WHERE pv.tenant_id = :tenant_id 
               AND (pv.status = 'pendente' OR pv.id = :ped_id)
             ORDER BY pv.id DESC",
            ['tenant_id' => $tenantId, 'ped_id' => $op['pedido_venda_id']]
        );

        // Carregar fichas e insumos para simulação
        $fichasModelos = [];
        $fichasRaw = Database::fetchAll(
            "SELECT ft.produto_modelo_id, ft.tempo_padrao, fti.materia_prima_id, fti.quantidade, mp.nome as materia_nome, mp.unidade_medida
             FROM fichas_tecnicas ft
             LEFT JOIN fichas_tecnicas_itens fti ON ft.id = fti.ficha_tecnica_id
             LEFT JOIN materias_primas mp ON fti.materia_prima_id = mp.id
             WHERE ft.tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        );

        foreach ($fichasRaw as $frow) {
            $mId = $frow['produto_modelo_id'];
            if (!isset($fichasModelos[$mId])) {
                $fichasModelos[$mId] = [
                    'tempo_padrao' => (float)$frow['tempo_padrao'],
                    'insumos' => []
                ];
            }
            if ($frow['materia_prima_id']) {
                $fichasModelos[$mId]['insumos'][] = [
                    'materia_prima_id' => $frow['materia_prima_id'],
                    'nome' => $frow['materia_nome'],
                    'quantidade' => (float)$frow['quantidade'],
                    'unidade_medida' => $frow['unidade_medida']
                ];
            }
        }

        $variantes = Database::fetchAll(
            "SELECT id, produto_modelo_id, cor, tamanho FROM produtos_variantes WHERE tenant_id = :tenant_id ORDER BY cor, tamanho ASC",
            ['tenant_id' => $tenantId]
        );

        $opVariantesSelected = Database::fetchAll(
            "SELECT opv.*, pv.cor, pv.tamanho 
             FROM ordens_producao_variantes opv
             JOIN produtos_variantes pv ON opv.produto_variante_id = pv.id
             WHERE opv.ordem_producao_id = :op_id AND opv.tenant_id = :tenant_id",
            ['op_id' => $id, 'tenant_id' => $tenantId]
        );

        $this->render('ops/form', [
            'title' => 'Editar Ordem de Produção',
            'subtitle' => "Ajuste as definições da OP #{$id}",
            'op' => $op,
            'modelos' => $modelos,
            'oficinas' => $oficinas,
            'pedidos' => $pedidos,
            'fichasModelos' => $fichasModelos,
            'variantes' => $variantes,
            'opVariantesSelected' => $opVariantesSelected,
            'action' => "/ops/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar OP.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $pedido_venda_id = !empty($_POST['pedido_venda_id']) ? (int)$_POST['pedido_venda_id'] : null;
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $oficina_faccao_id = !empty($_POST['oficina_faccao_id']) ? (int)$_POST['oficina_faccao_id'] : null;
        
        $variante_ids = $_POST['variante_ids'] ?? [];
        $variante_quantidades = $_POST['variante_quantidades'] ?? [];
        
        $operadores = (int)($_POST['operadores'] ?? 1);
        $prazo = $_POST['prazo'] ?? '';
        $status = $_POST['status'] ?? 'aberta';

        $quantidade = 0;
        $variantesSalvar = [];
        foreach ($variante_ids as $index => $vId) {
            $vId = (int)$vId;
            $qtd = (int)($variante_quantidades[$index] ?? 0);
            if ($vId > 0 && $qtd > 0) {
                $quantidade += $qtd;
                $variantesSalvar[] = [
                    'variante_id' => $vId,
                    'quantidade' => $qtd
                ];
            }
        }

        if ($produto_modelo_id <= 0 || $quantidade <= 0 || empty($prazo)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios (Modelo, Variações/Quantidades e Prazo).');
            $this->redirect("/ops/editar?id={$id}");
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Obter OP original para verificar mudanças de vinculo de pedido, status e estoque
            $opOriginal = Database::fetch(
                "SELECT pedido_venda_id, status, estoque_baixado, produto_modelo_id, quantidade FROM ordens_producao WHERE id = :id",
                ['id' => $id]
            );

            if ($opOriginal) {
                $statusAnterior = $opOriginal['status'];
                $baixadoAnterior = (int)$opOriginal['estoque_baixado'];
                $modeloAnterior = (int)$opOriginal['produto_modelo_id'];
                $qtdAnterior = (int)$opOriginal['quantidade'];

                // 1. Se já estava baixado, mas o status mudou (não é mais em andamento) ou mudou modelo/quantidade: estornar estoque anterior
                if ($baixadoAnterior === 1 && ($status !== 'em andamento' || $produto_modelo_id !== $modeloAnterior || $quantidade !== $qtdAnterior)) {
                    $this->estornarEstoqueOP($db, $tenantId, $id, $modeloAnterior, $qtdAnterior);
                }

                // 2. Se o novo status for em andamento, e ainda não está baixado (ou mudou modelo/quantidade): baixar novo estoque
                if ($status === 'em andamento' && ($baixadoAnterior === 0 || $statusAnterior !== 'em andamento' || $produto_modelo_id !== $modeloAnterior || $quantidade !== $qtdAnterior)) {
                    $this->baixarEstoqueOP($db, $tenantId, $id, $produto_modelo_id, $quantidade);
                }
            }

            // Salvar a primeira variante na coluna legada para fins de compatibilidade
            $produto_variante_id = !empty($variantesSalvar) ? $variantesSalvar[0]['variante_id'] : null;

            // 3. Atualizar OP
            $stmt = $db->prepare(
                "UPDATE ordens_producao 
                 SET pedido_venda_id = :pedido_venda_id, produto_modelo_id = :produto_modelo_id, produto_variante_id = :produto_variante_id,
                     oficina_faccao_id = :oficina_faccao_id, quantidade = :quantidade, operadores = :operadores, prazo = :prazo, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id"
            );
            $stmt->execute([
                'pedido_venda_id' => $pedido_venda_id,
                'produto_modelo_id' => $produto_modelo_id,
                'produto_variante_id' => $produto_variante_id,
                'oficina_faccao_id' => $oficina_faccao_id,
                'quantidade' => $quantidade,
                'operadores' => $operadores,
                'prazo' => $prazo,
                'status' => $status,
                'tenant_id' => $tenantId,
                'id' => $id
            ]);

            // Sincronizar variações na tabela pivot
            // Obter variações atualmente salvas
            $currentPivotVars = Database::fetchAll(
                "SELECT produto_variante_id FROM ordens_producao_variantes WHERE ordem_producao_id = :op_id",
                ['op_id' => $id]
            );
            $currentPivotIds = array_column($currentPivotVars, 'produto_variante_id');

            // Deletar anteriores
            $db->prepare("DELETE FROM ordens_producao_variantes WHERE ordem_producao_id = :op_id AND tenant_id = :tenant_id")
               ->execute(['op_id' => $id, 'tenant_id' => $tenantId]);

            // Inserir novas
            $stmtVar = $db->prepare(
                "INSERT INTO ordens_producao_variantes (tenant_id, ordem_producao_id, produto_variante_id, quantidade) 
                 VALUES (:tenant_id, :op_id, :variante_id, :quantidade)"
            );
            foreach ($variantesSalvar as $v) {
                $stmtVar->execute([
                    'tenant_id' => $tenantId,
                    'op_id' => $id,
                    'variante_id' => $v['variante_id'],
                    'quantidade' => $v['quantidade']
                ]);
            }

            // Sincronizar etapas do Chão de Fábrica para variações
            $newVarIds = array_column($variantesSalvar, 'variante_id');

            // 1. Deletar etapas de variações removidas
            foreach ($currentPivotIds as $oldId) {
                if (!in_array($oldId, $newVarIds)) {
                    $db->prepare("DELETE FROM chao_fabrica_etapas WHERE ordem_producao_id = :op_id AND produto_variante_id = :var_id")
                       ->execute(['op_id' => $id, 'var_id' => $oldId]);
                }
            }

            // 2. Inserir etapas para novas variações
            $stmtEtapa = $db->prepare(
                "INSERT INTO chao_fabrica_etapas (tenant_id, ordem_producao_id, produto_variante_id, etapa, status) 
                 VALUES (:tenant_id, :op_id, :variante_id, :etapa, 'pendente')"
            );
            $etapas = ['corte', 'costura', 'acabamento', 'revisão', 'embalagem'];
            foreach ($newVarIds as $nId) {
                if (!in_array($nId, $currentPivotIds)) {
                    foreach ($etapas as $et) {
                        $stmtEtapa->execute([
                            'tenant_id' => $tenantId,
                            'op_id' => $id,
                            'variante_id' => $nId,
                            'etapa' => $et
                        ]);
                    }
                }
            }

            // Se mudou o pedido de venda, atualizar os status do pedido
            if ($opOriginal && $opOriginal['pedido_venda_id'] != $pedido_venda_id) {
                // Voltar o antigo para pendente
                if ($opOriginal['pedido_venda_id']) {
                    $db->prepare("UPDATE pedidos_venda SET status = 'pendente' WHERE id = :pedido_id")
                       ->execute(['pedido_id' => $opOriginal['pedido_venda_id']]);
                }
                // Definir o novo para em produção
                if ($pedido_venda_id) {
                    $db->prepare("UPDATE pedidos_venda SET status = 'em produção' WHERE id = :pedido_id")
                       ->execute(['pedido_id' => $pedido_venda_id]);
                }
            }

            // Se OP foi concluída, e existe pedido vinculado, atualizar pedido para "entregue"
            if ($status === 'concluída' && $pedido_venda_id) {
                $db->prepare("UPDATE pedidos_venda SET status = 'entregue' WHERE id = :pedido_id")
                   ->execute(['pedido_id' => $pedido_venda_id]);
            }

            $db->commit();
            $this->setFlash('success', 'Ordem de Produção atualizada com sucesso.');
            $this->redirect('/ops');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/ops/editar?id={$id}");
        }
    }

    /**
     * Excluir OP.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $op = Database::fetch(
                "SELECT pedido_venda_id, status, estoque_baixado, produto_modelo_id, quantidade FROM ordens_producao WHERE id = :id AND tenant_id = :tenant_id",
                ['id' => $id, 'tenant_id' => $tenantId]
            );

            if ($op) {
                // Se o estoque foi baixado, estornar antes de deletar
                if ((int)$op['estoque_baixado'] === 1) {
                    $this->estornarEstoqueOP($db, $tenantId, $id, (int)$op['produto_modelo_id'], (int)$op['quantidade']);
                }

                // Deletar
                $db->prepare("DELETE FROM ordens_producao WHERE id = :id AND tenant_id = :tenant_id")
                   ->execute(['id' => $id, 'tenant_id' => $tenantId]);

                // Se tinha pedido de venda vinculado, voltar status para pendente
                if ($op['pedido_venda_id']) {
                    $db->prepare("UPDATE pedidos_venda SET status = 'pendente' WHERE id = :pedido_id")
                       ->execute(['pedido_id' => $op['pedido_venda_id']]);
                }

                $db->commit();
                $this->setFlash('success', 'OP excluída com sucesso.');
            } else {
                $db->rollBack();
                $this->setFlash('error', 'OP não encontrada.');
            }
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao excluir OP: ' . $e->getMessage());
        }

        $this->redirect('/ops');
    }

    /**
     * Realiza a baixa do estoque com base nos insumos da ficha técnica multiplicados pela quantidade da OP.
     */
    private function baixarEstoqueOP($db, int $tenantId, int $opId, int $modeloId, int $quantidadeOP): void
    {
        $ficha = Database::fetch(
            "SELECT id FROM fichas_tecnicas WHERE produto_modelo_id = :modelo_id AND tenant_id = :tenant_id",
            ['modelo_id' => $modeloId, 'tenant_id' => $tenantId]
        );

        if (!$ficha) {
            return;
        }

        $insumos = Database::fetchAll(
            "SELECT materia_prima_id, quantidade FROM fichas_tecnicas_itens WHERE ficha_tecnica_id = :ficha_id",
            ['ficha_id' => $ficha['id']]
        );

        $userId = $_SESSION['user_id'] ?? null;
        $stmtBaixar = $db->prepare(
            "UPDATE materias_primas SET estoque_atual = estoque_atual - :quantidade WHERE id = :id AND tenant_id = :tenant_id"
        );
        $stmtMov = $db->prepare(
            "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id)
             VALUES (:tenant_id, 'materia_prima', :item_id, :quantidade, 'saída', :motivo, :usuario_id)"
        );

        foreach ($insumos as $ins) {
            $mId = (int)$ins['materia_prima_id'];
            $consumoTotal = (float)$ins['quantidade'] * $quantidadeOP;

            if ($mId > 0 && $consumoTotal > 0) {
                // Atualiza estoque físico
                $stmtBaixar->execute([
                    'quantidade' => $consumoTotal,
                    'id' => $mId,
                    'tenant_id' => $tenantId
                ]);

                // Grava movimentação
                $stmtMov->execute([
                    'tenant_id' => $tenantId,
                    'item_id' => $mId,
                    'quantidade' => $consumoTotal,
                    'motivo' => "Consumo automático da OP #{$opId}",
                    'usuario_id' => $userId
                ]);
            }
        }

        $db->prepare("UPDATE ordens_producao SET estoque_baixado = 1 WHERE id = :id AND tenant_id = :tenant_id")
           ->execute(['id' => $opId, 'tenant_id' => $tenantId]);
    }

    /**
     * Realiza o estorno no estoque devolvendo as matérias-primas baixadas da OP.
     */
    private function estornarEstoqueOP($db, int $tenantId, int $opId, int $modeloId, int $quantidadeOP): void
    {
        $ficha = Database::fetch(
            "SELECT id FROM fichas_tecnicas WHERE produto_modelo_id = :modelo_id AND tenant_id = :tenant_id",
            ['modelo_id' => $modeloId, 'tenant_id' => $tenantId]
        );

        if (!$ficha) {
            return;
        }

        $insumos = Database::fetchAll(
            "SELECT materia_prima_id, quantidade FROM fichas_tecnicas_itens WHERE ficha_tecnica_id = :ficha_id",
            ['ficha_id' => $ficha['id']]
        );

        $userId = $_SESSION['user_id'] ?? null;
        $stmtEstornar = $db->prepare(
            "UPDATE materias_primas SET estoque_atual = estoque_atual + :quantidade WHERE id = :id AND tenant_id = :tenant_id"
        );
        $stmtMov = $db->prepare(
            "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id)
             VALUES (:tenant_id, 'materia_prima', :item_id, :quantidade, 'entrada', :motivo, :usuario_id)"
        );

        foreach ($insumos as $ins) {
            $mId = (int)$ins['materia_prima_id'];
            $consumoTotal = (float)$ins['quantidade'] * $quantidadeOP;

            if ($mId > 0 && $consumoTotal > 0) {
                // Estorna estoque físico
                $stmtEstornar->execute([
                    'quantidade' => $consumoTotal,
                    'id' => $mId,
                    'tenant_id' => $tenantId
                ]);

                // Grava movimentação
                $stmtMov->execute([
                    'tenant_id' => $tenantId,
                    'item_id' => $mId,
                    'quantidade' => $consumoTotal,
                    'motivo' => "Estorno/Cancelamento da OP #{$opId}",
                    'usuario_id' => $userId
                ]);
            }
        }

        $db->prepare("UPDATE ordens_producao SET estoque_baixado = 0 WHERE id = :id AND tenant_id = :tenant_id")
           ->execute(['id' => $opId, 'tenant_id' => $tenantId]);
    }
}
