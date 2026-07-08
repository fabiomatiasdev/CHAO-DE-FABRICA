<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ChaoFabricaController extends Controller
{
    /**
     * Listar progresso do Chão de Fábrica.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // Buscar todas as OPs ativas (abertas ou em andamento) para mostrar o painel visual
        $ops = Database::fetchAll(
            "SELECT op.id, op.quantidade, op.status as op_status, pm.nome as modelo_nome, pm.referencia
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id AND op.status != 'cancelada'
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        // Para cada OP, buscar o status de suas 5 etapas de produção
        foreach ($ops as &$op) {
            $etapas = Database::fetchAll(
                "SELECT * FROM chao_fabrica_etapas 
                 WHERE ordem_producao_id = :op_id 
                 ORDER BY FIELD(etapa, 'corte', 'costura', 'acabamento', 'revisão', 'embalagem')",
                ['op_id' => $op['id']]
            );

            // Indexar etapas por nome para facilitar exibição na grid
            $op['etapas'] = [];
            foreach ($etapas as $et) {
                $op['etapas'][$et['etapa']] = [
                    'status' => $et['status'],
                    'responsavel' => $et['responsavel'],
                    'atualizado_em' => $et['atualizado_em']
                ];
            }
        }

        $this->render('chao_fabrica/index', [
            'title' => 'Chão de Fábrica',
            'subtitle' => 'Painel visual de acompanhamento das etapas produtivas de cada OP',
            'ops' => $ops
        ]);
    }

    /**
     * Apontamento de status de etapa de produção.
     */
    public function apontar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $opId = (int)($_POST['ordem_producao_id'] ?? 0);
        $etapa = $_POST['etapa'] ?? '';
        $status = $_POST['status'] ?? '';
        $responsavel = trim($_POST['responsavel'] ?? '');

        if ($opId <= 0 || empty($etapa) || empty($status) || empty($responsavel)) {
            $this->setFlash('error', 'Todos os campos são obrigatórios para apontar produção.');
            $this->redirect('/chao-fabrica');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Atualizar o status da etapa selecionada
            $stmt = $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = :status, responsavel = :responsavel 
                 WHERE tenant_id = :tenant_id AND ordem_producao_id = :op_id AND etapa = :etapa"
            );
            $stmt->execute([
                'status' => $status,
                'responsavel' => $responsavel,
                'tenant_id' => $tenantId,
                'op_id' => $opId,
                'etapa' => $etapa
            ]);

            // 2. Se a etapa foi marcada como "conclúido", desbloquear a próxima etapa produtiva
            if ($status === 'conclúido') {
                $proximaEtapa = null;
                if ($etapa === 'corte') $proximaEtapa = 'costura';
                elseif ($etapa === 'costura') $proximaEtapa = 'acabamento';
                elseif ($etapa === 'acabamento') $proximaEtapa = 'revisão';
                elseif ($etapa === 'revisão') $proximaEtapa = 'embalagem';

                if ($proximaEtapa) {
                    $db->prepare(
                        "UPDATE chao_fabrica_etapas 
                         SET status = 'em andamento' 
                         WHERE tenant_id = :tenant_id AND ordem_producao_id = :op_id AND etapa = :proxima AND status = 'pendente'"
                    )->execute([
                        'tenant_id' => $tenantId,
                        'op_id' => $opId,
                        'proxima' => $proximaEtapa
                    ]);
                } else {
                    // Se concluiu a última etapa ('embalagem'), marcar a OP principal como "concluída"
                    $db->prepare(
                        "UPDATE ordens_producao 
                         SET status = 'concluída' 
                         WHERE tenant_id = :tenant_id AND id = :op_id"
                    )->execute([
                        'tenant_id' => $tenantId,
                        'op_id' => $opId
                    ]);

                    // E se tiver pedido de venda vinculado, marcar pedido como entregue
                    $op = Database::fetch("SELECT pedido_venda_id FROM ordens_producao WHERE id = :id", ['id' => $opId]);
                    if ($op && $op['pedido_venda_id']) {
                        $db->prepare("UPDATE pedidos_venda SET status = 'entregue' WHERE id = :pedido_id")
                           ->execute(['pedido_id' => $op['pedido_venda_id']]);
                    }
                }
            }

            // 3. Garantir que a OP principal está como "em andamento" caso não esteja concluída/aberta
            if ($status === 'em andamento') {
                $db->prepare(
                    "UPDATE ordens_producao 
                     SET status = 'em andamento' 
                     WHERE id = :op_id AND status = 'aberta' AND tenant_id = :tenant_id"
                )->execute([
                    'op_id' => $opId,
                    'tenant_id' => $tenantId
                ]);
            }

            $db->commit();
            $this->setFlash('success', 'Apontamento registrado com sucesso.');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao apontar produção: ' . $e->getMessage());
        }

        $this->redirect('/chao-fabrica');
    }
}
