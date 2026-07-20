<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class OrdemCorteController extends Controller
{
    /**
     * Listar ordens de corte.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $cortes = Database::fetchAll(
            "SELECT oc.*, op.id as op_id, pm.nome as modelo_nome, pm.referencia
             FROM ordens_corte oc
             JOIN ordens_producao op ON oc.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE oc.tenant_id = :tenant_id
             ORDER BY oc.id DESC",
            ['tenant_id' => $tenantId]
        );

        foreach ($cortes as &$c) {
            $c['variantes'] = Database::fetchAll(
                "SELECT ocv.ordem_corte_id as corte_id, ocv.quantidade_cortada, pv.cor, pv.tamanho 
                 FROM ordens_corte_variantes ocv
                 JOIN produtos_variantes pv ON ocv.produto_variante_id = pv.id
                 WHERE ocv.ordem_corte_id = :corte_id AND ocv.tenant_id = :tenant_id",
                ['corte_id' => $c['id'], 'tenant_id' => $tenantId]
            );

            if (empty($c['variantes']) && !empty($c['produto_variante_id'])) {
                $c['variantes'] = Database::fetchAll(
                    "SELECT oc.id as corte_id, oc.quantidade_cortada, pv.cor, pv.tamanho 
                     FROM ordens_corte oc
                     JOIN produtos_variantes pv ON oc.produto_variante_id = pv.id
                     WHERE oc.id = :corte_id AND oc.tenant_id = :tenant_id",
                    ['corte_id' => $c['id'], 'tenant_id' => $tenantId]
                );
            }
        }

        $this->render('corte/index', [
            'title' => 'Ordens de Corte',
            'subtitle' => 'Controle do enfesto e corte físico dos tecidos para as Ordens de Produção',
            'cortes' => $cortes
        ]);
    }

    /**
     * Formulário de novo corte.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // OPs abertas ou em andamento
        $ops = Database::fetchAll(
            "SELECT op.id, op.quantidade, pm.nome as modelo_nome, pm.referencia, pm.grade_tamanhos
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id AND op.status IN ('aberta', 'em andamento')
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        foreach ($ops as &$op) {
            $op['variantes'] = Database::fetchAll(
                "SELECT opv.produto_variante_id as variante_id, opv.quantidade as quantidade_op, pv.cor, pv.tamanho 
                 FROM ordens_producao_variantes opv
                 JOIN produtos_variantes pv ON opv.produto_variante_id = pv.id
                 WHERE opv.ordem_producao_id = :op_id AND opv.tenant_id = :tenant_id",
                ['op_id' => $op['id'], 'tenant_id' => $tenantId]
            );

            if (empty($op['variantes']) && !empty($op['produto_variante_id'])) {
                $op['variantes'] = Database::fetchAll(
                    "SELECT op.produto_variante_id as variante_id, op.quantidade as quantidade_op, pv.cor, pv.tamanho 
                     FROM ordens_producao op
                     JOIN produtos_variantes pv ON op.produto_variante_id = pv.id
                     WHERE op.id = :op_id AND op.tenant_id = :tenant_id",
                    ['op_id' => $op['id'], 'tenant_id' => $tenantId]
                );
            }

            foreach ($op['variantes'] as &$v) {
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
                
                $v['quantidade_cortada'] = (int)($corteInfo['total_cortado'] ?? 0);
            }
        }

        $materiasPrimas = Database::fetchAll(
            "SELECT id, nome, unidade_medida, estoque_atual FROM materias_primas WHERE tenant_id = :tenant_id ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('corte/form', [
            'title' => 'Lançar Ordem de Corte',
            'subtitle' => 'Registre a quantidade de peças cortadas e o consumo real de insumos/tecido',
            'ops' => $ops,
            'materiasPrimas' => $materiasPrimas,
            'action' => '/corte/novo'
        ]);
    }


    /**
     * Salvar ordem de corte.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $ordem_producao_id = (int)($_POST['ordem_producao_id'] ?? 0);
        $responsavel = trim($_POST['responsavel'] ?? '');
        $data_corte = $_POST['data_corte'] ?? date('Y-m-d');
        $finalizar_corte = !empty($_POST['finalizar_corte']);

        $materia_prima_id = !empty($_POST['materia_prima_id']) ? (int)$_POST['materia_prima_id'] : null;
        $quantidade_real_utilizada = !empty($_POST['quantidade_real_utilizada']) ? (float)$_POST['quantidade_real_utilizada'] : 0.0;
        $unidade_medida = trim($_POST['unidade_medida'] ?? '');
        
        $variante_ids = $_POST['variante_ids'] ?? [];
        $quantidades_cortadas = $_POST['quantidades_cortadas'] ?? [];

        $quantidade_total_cortada = 0;
        $variantesCorte = [];
        foreach ($variante_ids as $index => $vId) {
            $vId = (int)$vId;
            $qtd = (int)($quantidades_cortadas[$index] ?? 0);
            if ($vId > 0 && $qtd > 0) {
                $quantidade_total_cortada += $qtd;
                $variantesCorte[] = [
                    'variante_id' => $vId,
                    'quantidade' => $qtd
                ];
            }
        }

        if ($ordem_producao_id <= 0 || $quantidade_total_cortada <= 0 || empty($responsavel)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios e informe a quantidade cortada de ao menos uma variação.');
            $this->redirect('/corte/novo');
        }

        // Buscar tamanho e ID da primeira variante para retrocompatibilidade na tabela principal
        $produto_variante_id = !empty($variantesCorte) ? $variantesCorte[0]['variante_id'] : null;
        $tamanho = '';
        if ($produto_variante_id) {
            $variante = Database::fetch("SELECT tamanho FROM produtos_variantes WHERE id = :id", ['id' => $produto_variante_id]);
            if ($variante) {
                $tamanho = $variante['tamanho'];
            }
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir Ordem de Corte na tabela principal
            $stmt = $db->prepare(
                "INSERT INTO ordens_corte (tenant_id, ordem_producao_id, produto_variante_id, tamanho, quantidade_cortada, responsavel, data_corte, materia_prima_id, quantidade_real_utilizada, unidade_medida, status) 
                 VALUES (:tenant_id, :ordem_producao_id, :produto_variante_id, :tamanho, :quantidade_cortada, :responsavel, :data_corte, :materia_prima_id, :quantidade_real_utilizada, :unidade_medida, :status)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'ordem_producao_id' => $ordem_producao_id,
                'produto_variante_id' => $produto_variante_id,
                'tamanho' => $tamanho,
                'quantidade_cortada' => $quantidade_total_cortada,
                'responsavel' => $responsavel,
                'data_corte' => $data_corte,
                'materia_prima_id' => $materia_prima_id,
                'quantidade_real_utilizada' => $quantidade_real_utilizada,
                'unidade_medida' => $unidade_medida,
                'status' => $finalizar_corte ? 'concluido' : 'em andamento'
            ]);
            $ordemCorteId = $db->lastInsertId();

            // 1.1 Baixa no estoque de matéria-prima real utilizada (se informado)
            if ($materia_prima_id && $quantidade_real_utilizada > 0) {
                $db->prepare(
                    "UPDATE materias_primas 
                     SET estoque_atual = GREATEST(0, estoque_atual - :qtd) 
                     WHERE id = :mp_id AND tenant_id = :tenant_id"
                )->execute([
                    'qtd' => $quantidade_real_utilizada,
                    'mp_id' => $materia_prima_id,
                    'tenant_id' => $tenantId
                ]);

                $db->prepare(
                    "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id) 
                     VALUES (:tenant_id, 'materia_prima', :mp_id, :qtd, 'saida', :motivo, :user_id)"
                )->execute([
                    'tenant_id' => $tenantId,
                    'mp_id' => $materia_prima_id,
                    'qtd' => $quantidade_real_utilizada,
                    'motivo' => "Consumo real no Corte da OP #{$ordem_producao_id}",
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);
            }

            // 2. Inserir variações cortadas na tabela pivot ordens_corte_variantes
            $stmtVar = $db->prepare(
                "INSERT INTO ordens_corte_variantes (tenant_id, ordem_corte_id, produto_variante_id, quantidade_cortada) 
                 VALUES (:tenant_id, :corte_id, :variante_id, :quantidade)"
            );
            foreach ($variantesCorte as $v) {
                $stmtVar->execute([
                    'tenant_id' => $tenantId,
                    'corte_id' => $ordemCorteId,
                    'variante_id' => $v['variante_id'],
                    'quantidade' => $v['quantidade']
                ]);
            }

            // 3. Preparar statements para atualização do Chão de Fábrica
            $stmtEtapaCorte = $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = :status, responsavel = :responsavel 
                 WHERE ordem_producao_id = :op_id 
                   AND (produto_variante_id = :variante_id1 OR (produto_variante_id IS NULL AND :variante_id2 IS NULL)) 
                   AND etapa = 'corte'"
            );
            
            $stmtEtapaCostura = $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = 'em andamento' 
                 WHERE ordem_producao_id = :op_id 
                   AND (produto_variante_id = :variante_id1 OR (produto_variante_id IS NULL AND :variante_id2 IS NULL)) 
                   AND etapa = 'costura' 
                   AND status = 'pendente'"
            );

            foreach ($variantesCorte as $v) {
                // 3.1 Buscar meta da variação
                $metaQtd = 0;
                if ($v['variante_id'] !== null) {
                    $metaInfo = Database::fetch(
                        "SELECT quantidade FROM ordens_producao_variantes WHERE ordem_producao_id = :op_id AND produto_variante_id = :variante_id",
                        ['op_id' => $ordem_producao_id, 'variante_id' => $v['variante_id']]
                    );
                    if ($metaInfo) {
                        $metaQtd = (int)$metaInfo['quantidade'];
                    }
                }
                if ($metaQtd <= 0) {
                    $opInfo = Database::fetch("SELECT quantidade FROM ordens_producao WHERE id = :op_id", ['op_id' => $ordem_producao_id]);
                    $metaQtd = $opInfo ? (int)$opInfo['quantidade'] : 0;
                }

                // 3.2 Buscar total acumulado já cortado (incluindo o lançamento atual)
                if ($v['variante_id'] === null) {
                    $corteInfo = Database::fetch(
                        "SELECT SUM(quantidade_cortada) as total_cortado FROM ordens_corte WHERE ordem_producao_id = :op_id AND produto_variante_id IS NULL AND tenant_id = :tenant_id",
                        ['op_id' => $ordem_producao_id, 'tenant_id' => $tenantId]
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
                            'op_id1' => $ordem_producao_id,
                            'variante_id1' => $v['variante_id'],
                            'tenant_id1' => $tenantId,
                            'op_id2' => $ordem_producao_id,
                            'variante_id2' => $v['variante_id'],
                            'tenant_id2' => $tenantId
                        ]
                    );
                }
                
                $totalCortado = (int)($corteInfo['total_cortado'] ?? 0);

                // 3.3 Se o usuário marcou para finalizar o corte ou se atingiu a meta estimada
                $novoStatusCorte = ($finalizar_corte || $totalCortado >= $metaQtd) ? 'concluído' : 'em andamento';

                $stmtEtapaCorte->execute([
                    'status' => $novoStatusCorte,
                    'responsavel' => $responsavel,
                    'op_id' => $ordem_producao_id,
                    'variante_id1' => $v['variante_id'],
                    'variante_id2' => $v['variante_id']
                ]);

                $stmtEtapaCostura->execute([
                    'op_id' => $ordem_producao_id,
                    'variante_id1' => $v['variante_id'],
                    'variante_id2' => $v['variante_id']
                ]);
            }

            // 5. Mudar status da OP principal para "em andamento" se estava "aberta"
            $db->prepare(
                "UPDATE ordens_producao 
                 SET status = 'em andamento' 
                 WHERE id = :op_id AND status = 'aberta'"
            )->execute(['op_id' => $ordem_producao_id]);


            $db->commit();
            $this->setFlash('success', 'Ordem de Corte registrada e status do Chão de Fábrica atualizado.');
            $this->redirect('/corte');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao salvar corte: ' . $e->getMessage());
            $this->redirect('/corte/novo');
        }
    }

    /**
     * Excluir ordem de corte.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $isAdmin = isset($_SESSION['is_superadmin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
        $podeExcluir = isset($_SESSION['pode_excluir']) && $_SESSION['pode_excluir'] === 1;

        if (!$isAdmin && !$podeExcluir) {
            $this->setFlash('error', 'Você não tem permissão para excluir lançamentos de corte.');
            $this->redirect('/corte');
            return;
        }

        if ($id <= 0) {
            $this->setFlash('error', 'Corte inválido.');
            $this->redirect('/corte');
        }

        // 1. Obter informações do corte antes de excluir
        $corte = Database::fetch(
            "SELECT * FROM ordens_corte WHERE id = :id AND tenant_id = :tenant_id",
            ['id' => $id, 'tenant_id' => $tenantId]
        );

        if (!$corte) {
            $this->setFlash('error', 'Corte não encontrado.');
            $this->redirect('/corte');
        }

        $ordem_producao_id = (int)$corte['ordem_producao_id'];

        // 2. Descobrir quais variações estavam ligadas a este corte
        $variantesDoCorte = Database::fetchAll(
            "SELECT produto_variante_id FROM ordens_corte_variantes WHERE ordem_corte_id = :corte_id AND tenant_id = :tenant_id",
            ['corte_id' => $id, 'tenant_id' => $tenantId]
        );

        // Se estiver vazio, trata a variante direta da tabela ordens_corte (caso legado)
        if (empty($variantesDoCorte) && !empty($corte['produto_variante_id'])) {
            $variantesDoCorte = [[
                'produto_variante_id' => $corte['produto_variante_id']
            ]];
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 3. Deletar da pivot ordens_corte_variantes
            $db->prepare("DELETE FROM ordens_corte_variantes WHERE ordem_corte_id = :corte_id AND tenant_id = :tenant_id")
               ->execute(['corte_id' => $id, 'tenant_id' => $tenantId]);

            // 4. Deletar da tabela principal ordens_corte
            $db->prepare("DELETE FROM ordens_corte WHERE id = :id AND tenant_id = :tenant_id")
               ->execute(['id' => $id, 'tenant_id' => $tenantId]);

            // 5. Recalcular e atualizar o Chão de Fábrica para cada variação afetada
            $stmtUpdateCorte = $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = :status, responsavel = :responsavel 
                 WHERE ordem_producao_id = :op_id 
                   AND (produto_variante_id = :variante_id1 OR (produto_variante_id IS NULL AND :variante_id2 IS NULL)) 
                   AND etapa = 'corte'"
            );

            $stmtUpdateCostura = $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = :status 
                 WHERE ordem_producao_id = :op_id 
                   AND (produto_variante_id = :variante_id1 OR (produto_variante_id IS NULL AND :variante_id2 IS NULL)) 
                   AND etapa = 'costura' 
                   AND status = 'em andamento'"
            );

            foreach ($variantesDoCorte as $v) {
                $varId = $v['produto_variante_id'];

                // 5.1 Buscar meta da variação
                $metaQtd = 0;
                if ($varId !== null) {
                    $metaInfo = Database::fetch(
                        "SELECT quantidade FROM ordens_producao_variantes WHERE ordem_producao_id = :op_id AND produto_variante_id = :variante_id",
                        ['op_id' => $ordem_producao_id, 'variante_id' => $varId]
                    );
                    if ($metaInfo) {
                        $metaQtd = (int)$metaInfo['quantidade'];
                    }
                }
                if ($metaQtd <= 0) {
                    $opInfo = Database::fetch("SELECT quantidade FROM ordens_producao WHERE id = :op_id", ['op_id' => $ordem_producao_id]);
                    $metaQtd = $opInfo ? (int)$opInfo['quantidade'] : 0;
                }

                // 5.2 Buscar total acumulado já cortado após a exclusão
                if ($varId === null) {
                    $corteInfo = Database::fetch(
                        "SELECT SUM(quantidade_cortada) as total_cortado FROM ordens_corte WHERE ordem_producao_id = :op_id AND produto_variante_id IS NULL AND tenant_id = :tenant_id",
                        ['op_id' => $ordem_producao_id, 'tenant_id' => $tenantId]
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
                            'op_id1' => $ordem_producao_id,
                            'variante_id1' => $varId,
                            'tenant_id1' => $tenantId,
                            'op_id2' => $ordem_producao_id,
                            'variante_id2' => $varId,
                            'tenant_id2' => $tenantId
                        ]
                    );
                }

                $totalCortado = (int)($corteInfo['total_cortado'] ?? 0);

                // 5.3 Definir novo status do corte e costura
                if ($totalCortado <= 0) {
                    $novoStatusCorte = 'pendente';
                    $novoStatusCostura = 'pendente';
                    $responsavelCorte = null;
                } else {
                    $novoStatusCorte = ($totalCortado >= $metaQtd) ? 'conclúido' : 'em andamento';
                    $novoStatusCostura = 'em andamento';
                    $responsavelCorte = $corte['responsavel'];
                }

                $stmtUpdateCorte->execute([
                    'status' => $novoStatusCorte,
                    'responsavel' => $responsavelCorte,
                    'op_id' => $ordem_producao_id,
                    'variante_id1' => $varId,
                    'variante_id2' => $varId
                ]);

                if ($novoStatusCostura === 'pendente') {
                    $stmtUpdateCostura->execute([
                        'status' => 'pendente',
                        'op_id' => $ordem_producao_id,
                        'variante_id1' => $varId,
                        'variante_id2' => $varId
                    ]);
                }
            }

            $db->commit();
            $this->setFlash('success', 'Ordem de Corte excluída e status do Chão de Fábrica atualizado.');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao excluir ordem de corte: ' . $e->getMessage());
        }

        $this->redirect('/corte');
    }
}
