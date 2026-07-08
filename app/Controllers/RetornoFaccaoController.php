<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class RetornoFaccaoController extends Controller
{
    /**
     * Listar retornos de facção.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $retornos = Database::fetchAll(
            "SELECT rf.*, op.id as op_id, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome
             FROM retornos_faccao rf
             JOIN ordens_producao op ON rf.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
             WHERE rf.tenant_id = :tenant_id
             ORDER BY rf.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('retornos/index', [
            'title' => 'Retornos de Facção',
            'subtitle' => 'Registre a entrega das peças costuradas pelas oficinas e faça a apuração de perdas/defeitos',
            'retornos' => $retornos
        ]);
    }

    /**
     * Formulário de novo retorno.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // OPs que estão em andamento ou abertas e possuem oficina vinculada
        $ops = Database::fetchAll(
            "SELECT op.id, op.quantidade, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome, of.mao_obra_peca
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
             WHERE op.tenant_id = :tenant_id AND op.status IN ('aberta', 'em andamento')
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('retornos/form', [
            'title' => 'Lançar Retorno de Oficina',
            'subtitle' => 'Apure a quantidade de peças boas entregues e as perdas de fabricação',
            'ops' => $ops,
            'action' => '/retornos/novo'
        ]);
    }

    /**
     * Salvar retorno.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $userId = $_SESSION['user_id'] ?? null;

        $ordem_producao_id = (int)($_POST['ordem_producao_id'] ?? 0);
        $quantidade_enviada = (int)($_POST['quantidade_enviada'] ?? 0);
        $quantidade_retornada_boa = (int)($_POST['quantidade_retornada_boa'] ?? 0);
        $quantidade_defeito_perda = (int)($_POST['quantidade_defeito_perda'] ?? 0);
        $data_retorno = $_POST['data_retorno'] ?? date('Y-m-d');

        if ($ordem_producao_id <= 0 || $quantidade_retornada_boa < 0 || $quantidade_defeito_perda < 0) {
            $this->setFlash('error', 'Preencha os dados de quantidades de retorno corretamente.');
            $this->redirect('/retornos/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Obter dados da OP e da oficina
            $op = Database::fetch(
                "SELECT op.*, of.id as oficina_id, of.mao_obra_peca 
                 FROM ordens_producao op 
                 JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id 
                 WHERE op.id = :id AND op.tenant_id = :tenant_id",
                ['id' => $ordem_producao_id, 'tenant_id' => $tenantId]
            );

            if (!$op) {
                $this->setFlash('error', 'Ordem de Produção não encontrada ou não possui oficina vinculada.');
                $this->redirect('/retornos/novo');
            }

            // 1. Inserir retorno
            $stmt = $db->prepare(
                "INSERT INTO retornos_faccao (tenant_id, ordem_producao_id, quantidade_enviada, quantidade_retornada_boa, quantidade_defeito_perda, data_retorno) 
                 VALUES (:tenant_id, :ordem_producao_id, :quantidade_enviada, :quantidade_retornada_boa, :quantidade_defeito_perda, :data_retorno)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'ordem_producao_id' => $ordem_producao_id,
                'quantidade_enviada' => $quantidade_enviada,
                'quantidade_retornada_boa' => $quantidade_retornada_boa,
                'quantidade_defeito_perda' => $quantidade_defeito_perda,
                'data_retorno' => $data_retorno
            ]);
            $retornoId = $db->lastInsertId();

            // 2. Incrementar estoque de Produtos Acabados (peças boas retornadas)
            $stmtEstoque = $db->prepare(
                "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id) 
                 VALUES (:tenant_id, 'produto_acabado', :item_id, :quantidade, 'entrada', :motivo, :usuario_id)"
            );
            $stmtEstoque->execute([
                'tenant_id' => $tenantId,
                'item_id' => $op['produto_modelo_id'],
                'quantidade' => $quantidade_retornada_boa,
                'motivo' => "Retorno de costura OP #{$ordem_producao_id}",
                'usuario_id' => $userId
            ]);

            // 3. Gerar faturamento devido à Oficina (contas a pagar)
            // Valor devido = peças boas * valor costura por peça da oficina
            $valorDevido = $quantidade_retornada_boa * $op['mao_obra_peca'];
            
            $stmtFin = $db->prepare(
                "INSERT INTO financeiro_faccoes (tenant_id, oficina_faccao_id, retorno_faccao_id, valor_devido, valor_pago, status) 
                 VALUES (:tenant_id, :oficina_id, :retorno_id, :valor_devido, 0.00, 'pendente')"
            );
            $stmtFin->execute([
                'tenant_id' => $tenantId,
                'oficina_id' => $op['oficina_id'],
                'retorno_id' => $retornoId,
                'valor_devido' => $valorDevido
            ]);

            // 4. Concluir a OP (muda status para concluída)
            $db->prepare(
                "UPDATE ordens_producao SET status = 'concluída' WHERE id = :op_id"
            )->execute(['op_id' => $ordem_producao_id]);

            // 5. Atualizar as etapas de Chão de Fábrica de 'costura', 'acabamento', 'revisão' e 'embalagem' para 'conclúido'
            $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = 'conclúido' 
                 WHERE ordem_producao_id = :op_id"
            )->execute(['op_id' => $ordem_producao_id]);

            // 6. Se tiver pedido de venda vinculado, marcar pedido como entregue
            if ($op['pedido_venda_id']) {
                $db->prepare("UPDATE pedidos_venda SET status = 'entregue' WHERE id = :pedido_id")
                   ->execute(['pedido_id' => $op['pedido_venda_id']]);
            }

            $db->commit();
            $this->setFlash('success', 'Retorno de facção gravado. Estoque de produto acabado incrementado e lançamento de contas a pagar gerado.');
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
