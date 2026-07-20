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

        $db = Database::getConnection();
        foreach ($ops as &$op) {
            // Buscar as variações cadastradas nesta OP
            $opVars = Database::fetchAll(
                "SELECT opv.produto_variante_id as variante_id, opv.quantidade as quantidade_op, pv.cor, pv.tamanho 
                 FROM ordens_producao_variantes opv
                 JOIN produtos_variantes pv ON opv.produto_variante_id = pv.id
                 WHERE opv.ordem_producao_id = :op_id AND opv.tenant_id = :tenant_id",
                ['op_id' => $op['id'], 'tenant_id' => $tenantId]
            );

            // Fallback para OPs antigas (sem múltiplos registros na pivot)
            if (empty($opVars)) {
                $opVars = Database::fetchAll(
                    "SELECT op.produto_variante_id as variante_id, op.quantidade as quantidade_op, pv.cor, pv.tamanho 
                     FROM ordens_producao op
                     JOIN produtos_variantes pv ON op.produto_variante_id = pv.id
                     WHERE op.id = :op_id AND op.tenant_id = :tenant_id AND op.produto_variante_id IS NOT NULL",
                    ['op_id' => $op['id'], 'tenant_id' => $tenantId]
                );
            }

            // Caso legado super antigo sem qualquer variante
            if (empty($opVars)) {
                $opVars = [[
                    'variante_id' => null,
                    'quantidade_op' => $op['quantidade'],
                    'cor' => 'Única',
                    'tamanho' => 'Padrão'
                ]];
            }

            $op['variantes'] = [];
            foreach ($opVars as $v) {
                // Garantir que as etapas existam para a variação
                $etapaExistente = Database::fetch(
                    "SELECT id FROM chao_fabrica_etapas WHERE ordem_producao_id = :op_id AND (produto_variante_id = :var_id OR (produto_variante_id IS NULL AND :var_id2 IS NULL)) LIMIT 1",
                    ['op_id' => $op['id'], 'var_id' => $v['variante_id'], 'var_id2' => $v['variante_id']]
                );
                if (!$etapaExistente) {
                    $stmtEtapa = $db->prepare(
                        "INSERT INTO chao_fabrica_etapas (tenant_id, ordem_producao_id, produto_variante_id, etapa, status) 
                         VALUES (:tenant_id, :op_id, :variante_id, :etapa, 'pendente')"
                    );
                    $todasEtapas = ['corte', 'costura', 'acabamento', 'revisão', 'embalagem'];
                    foreach ($todasEtapas as $et) {
                        $stmtEtapa->execute([
                            'tenant_id' => $tenantId,
                            'op_id' => $op['id'],
                            'variante_id' => $v['variante_id'],
                            'etapa' => $et
                        ]);
                    }
                }

                $etapas = Database::fetchAll(
                    "SELECT * FROM chao_fabrica_etapas 
                     WHERE ordem_producao_id = :op_id AND (produto_variante_id = :var_id OR (produto_variante_id IS NULL AND :var_id2 IS NULL))
                     ORDER BY CASE etapa
                        WHEN 'corte' THEN 1
                        WHEN 'costura' THEN 2
                        WHEN 'acabamento' THEN 3
                        WHEN 'revisão' THEN 4
                        WHEN 'embalagem' THEN 5
                        ELSE 6
                     END ASC",
                    ['op_id' => $op['id'], 'var_id' => $v['variante_id'], 'var_id2' => $v['variante_id']]
                );

                $etapasIndexadas = [];
                foreach ($etapas as $et) {
                    $etapasIndexadas[$et['etapa']] = [
                        'status' => $et['status'],
                        'responsavel' => $et['responsavel'],
                        'atualizado_em' => $et['atualizado_em']
                    ];
                }

                // Calcular total cortado
                if ($v['variante_id'] === null) {
                    $corteInfo = Database::fetch(
                        "SELECT SUM(quantidade_corte) as total_cortado 
                         FROM (
                             SELECT SUM(oc.quantidade_cortada) as quantidade_corte 
                             FROM ordens_corte oc 
                             WHERE oc.ordem_producao_id = :op_id AND oc.produto_variante_id IS NULL AND oc.tenant_id = :tenant_id
                         )",
                        ['op_id' => $op['id'], 'tenant_id' => $tenantId]
                    );
                } else {
                    $corteInfo = Database::fetch(
                        "SELECT (
                            COALESCE((
                                SELECT SUM(ocv.quantidade_cortada) 
                                FROM ordens_corte_variantes ocv 
                                WHERE ocv.produto_variante_id = :variante_id1 
                                  AND ocv.tenant_id = :tenant_id1
                                  AND ocv.ordem_corte_id IN (
                                      SELECT id FROM ordens_corte WHERE ordem_producao_id = :op_id1
                                  )
                            ), 0) + 
                            COALESCE((
                                SELECT SUM(oc.quantidade_cortada) 
                                FROM ordens_corte oc 
                                WHERE oc.ordem_producao_id = :op_id2 
                                  AND oc.produto_variante_id = :variante_id2 
                                  AND oc.tenant_id = :tenant_id2
                                  AND NOT EXISTS (
                                      SELECT 1 
                                      FROM ordens_corte_variantes ocv 
                                      WHERE ocv.ordem_corte_id = oc.id
                                  )
                            ), 0)
                         ) as total_cortado",
                        [
                            'op_id1' => $op['id'],
                            'variante_id1' => $v['variante_id'],
                            'tenant_id1' => $tenantId,
                            'op_id2' => $op['id'],
                            'variante_id2' => $v['variante_id'],
                            'tenant_id2' => $tenantId
                        ]
                    );
                }

                $qtdCortada = (int)($corteInfo['total_cortado'] ?? 0);

                $op['variantes'][] = [
                    'variante_id' => $v['variante_id'],
                    'cor' => $v['cor'],
                    'tamanho' => $v['tamanho'],
                    'quantidade_op' => $v['quantidade_op'],
                    'quantidade_cortada' => $qtdCortada,
                    'quantidade_pendente' => max(0, $v['quantidade_op'] - $qtdCortada),
                    'etapas' => $etapasIndexadas
                ];
            }
        }

        $this->render('chao_fabrica/index', [
            'title' => 'Chão de Fábrica',
            'subtitle' => 'Painel visual de acompanhamento das etapas produtivas de cada OP',
            'ops' => $ops
        ]);
    }

    public function apontar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $opId = (int) ($_POST['ordem_producao_id'] ?? 0);
        $etapa = $_POST['etapa'] ?? '';
        $status = $_POST['status'] ?? '';
        $responsavel = trim($_POST['responsavel'] ?? '');
        $variante_ids = $_POST['variante_ids'] ?? [];

        if ($opId <= 0 || empty($etapa) || empty($status) || empty($responsavel)) {
            $this->setFlash('error', 'Todos os campos são obrigatórios para apontar produção.');
            $this->redirect('/chao-fabrica');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Atualizar o status da etapa selecionada para as variações selecionadas
            $stmt = $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = :status, responsavel = :responsavel 
                 WHERE tenant_id = :tenant_id 
                   AND ordem_producao_id = :op_id 
                   AND etapa = :etapa 
                   AND (produto_variante_id = :variante_id OR (produto_variante_id IS NULL AND :variante_id2 IS NULL))"
            );

            if (empty($variante_ids)) {
                $stmt->execute([
                    'status' => $status,
                    'responsavel' => $responsavel,
                    'tenant_id' => $tenantId,
                    'op_id' => $opId,
                    'etapa' => $etapa,
                    'variante_id' => null,
                    'variante_id2' => null
                ]);
            } else {
                foreach ($variante_ids as $varId) {
                    $stmt->execute([
                        'status' => $status,
                        'responsavel' => $responsavel,
                        'tenant_id' => $tenantId,
                        'op_id' => $opId,
                        'etapa' => $etapa,
                        'variante_id' => $varId,
                        'variante_id2' => $varId
                    ]);
                }
            }

            // 2. Se a etapa foi marcada como "concluído" ou "conclúido"
            if ($status === 'conclúido' || $status === 'concluído') {
                $proximaEtapa = null;
                if ($etapa === 'corte')
                    $proximaEtapa = 'costura';
                elseif ($etapa === 'costura')
                    $proximaEtapa = 'acabamento';
                elseif ($etapa === 'acabamento')
                    $proximaEtapa = 'revisão';
                elseif ($etapa === 'revisão')
                    $proximaEtapa = 'embalagem';

                if ($proximaEtapa) {
                    $stmtProx = $db->prepare(
                        "UPDATE chao_fabrica_etapas 
                         SET status = 'em andamento' 
                         WHERE tenant_id = :tenant_id 
                           AND ordem_producao_id = :op_id 
                           AND etapa = :proxima 
                           AND status = 'pendente'
                           AND (produto_variante_id = :variante_id OR (produto_variante_id IS NULL AND :variante_id2 IS NULL))"
                    );
                    if (empty($variante_ids)) {
                        $stmtProx->execute([
                            'tenant_id' => $tenantId,
                            'op_id' => $opId,
                            'proxima' => $proximaEtapa,
                            'variante_id' => null,
                            'variante_id2' => null
                        ]);
                    } else {
                        foreach ($variante_ids as $varId) {
                            $stmtProx->execute([
                                'tenant_id' => $tenantId,
                                'op_id' => $opId,
                                'proxima' => $proximaEtapa,
                                'variante_id' => $varId,
                                'variante_id2' => $varId
                            ]);
                        }
                    }
                } else {
                    // Se concluiu a última etapa ('embalagem'), verificar se todas as variações da OP foram concluídas
                    $etapasIncompletas = Database::fetch(
                        "SELECT COUNT(*) as total FROM chao_fabrica_etapas 
                         WHERE ordem_producao_id = :op_id AND etapa = 'embalagem' AND status NOT IN ('conclúido', 'concluído')",
                        ['op_id' => $opId]
                    )['total'] ?? 0;

                    if ($etapasIncompletas == 0) {
                        // 1. Obter dados da OP
                        $opData = Database::fetch(
                            "SELECT * FROM ordens_producao WHERE id = :id AND tenant_id = :tenant_id",
                            ['id' => $opId, 'tenant_id' => $tenantId]
                        );

                        if ($opData && $opData['status'] !== 'concluída') {
                            // Marcar a OP principal como "concluída"
                            $db->prepare(
                                "UPDATE ordens_producao 
                                 SET status = 'concluída' 
                                 WHERE tenant_id = :tenant_id AND id = :op_id"
                            )->execute([
                                'tenant_id' => $tenantId,
                                'op_id' => $opId
                            ]);

                            // 2. Apurar total de perdas registradas na facção ou controle de qualidade para a OP
                            $perdasFaccao = (int)(Database::fetch(
                                "SELECT SUM(quantidade_defeito_perda) as total FROM retornos_faccao WHERE ordem_producao_id = :op_id AND tenant_id = :tenant_id",
                                ['op_id' => $opId, 'tenant_id' => $tenantId]
                            )['total'] ?? 0);

                            $perdasQualidade = (int)(Database::fetch(
                                "SELECT SUM(quantidade_reprovada) as total FROM controle_qualidade WHERE ordem_producao_id = :op_id AND tenant_id = :tenant_id",
                                ['op_id' => $opId, 'tenant_id' => $tenantId]
                            )['total'] ?? 0);

                            $totalPerdas = max($perdasFaccao, $perdasQualidade);

                            // 3. Apurar variações da OP e creditar estoque de produtos acabados descontando perdas
                            $opVariantes = Database::fetchAll(
                                "SELECT opv.produto_variante_id, opv.quantidade 
                                 FROM ordens_producao_variantes opv
                                 WHERE opv.ordem_producao_id = :op_id AND opv.tenant_id = :tenant_id",
                                ['op_id' => $opId, 'tenant_id' => $tenantId]
                            );

                            if (empty($opVariantes) && $opData['produto_variante_id']) {
                                $opVariantes = [[
                                    'produto_variante_id' => $opData['produto_variante_id'],
                                    'quantidade' => $opData['quantidade']
                                ]];
                            }

                            $totalQtdOP = array_sum(array_column($opVariantes, 'quantidade')) ?: $opData['quantidade'];

                            foreach ($opVariantes as $v) {
                                $varId = $v['produto_variante_id'];
                                $qtdBase = $v['quantidade'];
                                
                                // Proporcionalizar perdas se houver variação
                                $proporcao = $totalQtdOP > 0 ? ($qtdBase / $totalQtdOP) : 1;
                                $perdaVar = (int)round($totalPerdas * $proporcao);
                                $qtdBoaFinal = max(0, $qtdBase - $perdaVar);

                                if ($varId && $qtdBoaFinal > 0) {
                                    // Creditar na tabela produtos_variantes
                                    $db->prepare(
                                        "UPDATE produtos_variantes 
                                         SET estoque_atual = estoque_atual + :qtd 
                                         WHERE id = :var_id AND tenant_id = :tenant_id"
                                    )->execute([
                                        'qtd' => $qtdBoaFinal,
                                        'var_id' => $varId,
                                        'tenant_id' => $tenantId
                                    ]);

                                    // Gravar em movimentação de estoque
                                    $db->prepare(
                                        "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id, local_estoque_id) 
                                         VALUES (:tenant_id, 'produto_acabado', :item_id, :qtd, 'entrada', :motivo, :user_id, :local_id)"
                                    )->execute([
                                        'tenant_id' => $tenantId,
                                        'item_id' => $varId,
                                        'qtd' => $qtdBoaFinal,
                                        'motivo' => "Entrada de Produção Finalizada OP #{$opId} (Descontado {$perdaVar} perdas)",
                                        'user_id' => $_SESSION['user_id'] ?? null,
                                        'local_id' => $opData['local_estoque_id'] ?? null
                                    ]);
                                }
                            }

                            // 4. Se tiver pedido de venda vinculado, marcar pedido como entregue
                            if ($opData['pedido_venda_id']) {
                                $db->prepare("UPDATE pedidos_venda SET status = 'entregue' WHERE id = :pedido_id")
                                    ->execute(['pedido_id' => $opData['pedido_venda_id']]);
                            }
                        }
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
