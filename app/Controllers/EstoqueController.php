<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class EstoqueController extends Controller
{
    /**
     * Listar movimentações e formulário de ajuste.
     */
    public function index(): void
    {
        $this->ajuste();
    }

    /**
     * Tela de ajuste de estoque (Insumos e Produtos Acabados por Local).
     */
    public function ajuste(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $perPage  = 10;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $total = (int)(Database::fetch(
            "SELECT COUNT(*) as total FROM estoque_movimentacoes WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        // Buscar histórico de ajustes paginado
        $ajustes = Database::fetchAll(
            "SELECT em.*, u.nome as usuario_nome, le.nome as local_nome,
                    COALESCE(mp.nome, pm.nome) as item_nome
             FROM estoque_movimentacoes em
             LEFT JOIN users u ON em.usuario_id = u.id
             LEFT JOIN locais_estoque le ON em.local_estoque_id = le.id
             LEFT JOIN materias_primas mp ON em.tipo_item = 'materia_prima' AND em.item_id = mp.id
             LEFT JOIN produtos_modelos pm ON em.tipo_item = 'produto_acabado' AND em.item_id = pm.id
             WHERE em.tenant_id = :tenant_id
             ORDER BY em.id DESC LIMIT :limit OFFSET :offset",
            ['tenant_id' => $tenantId, 'limit' => $perPage, 'offset' => $offset]
        );

        // Carregar matérias-primas e produtos acabados
        $materias = Database::fetchAll(
            "SELECT id, nome, unidade_medida, estoque_atual FROM materias_primas WHERE tenant_id = :tenant_id ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $produtos = Database::fetchAll(
            "SELECT id, nome, referencia FROM produtos_modelos WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        // Saldo calculado para produtos acabados
        foreach ($produtos as &$prod) {
            $saldo = Database::fetch(
                "SELECT SUM(CASE WHEN tipo_movimentacao = 'entrada' THEN quantidade ELSE -quantidade END) as total 
                 FROM estoque_movimentacoes 
                 WHERE tenant_id = :tenant_id AND tipo_item = 'produto_acabado' AND item_id = :id",
                ['tenant_id' => $tenantId, 'id' => $prod['id']]
            )['total'] ?? 0;
            $prod['estoque_atual'] = $saldo;
        }

        // Carregar locais de estoque (armazenadores)
        $locaisEstoque = Database::fetchAll(
            "SELECT * FROM locais_estoque WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('estoque/ajuste', [
            'title'         => 'Ajuste de Estoque (Insumos e Acabados)',
            'subtitle'      => 'Realize entradas e saídas manuais selecionando o local de armazenamento',
            'ajustes'       => $ajustes,
            'materias'      => $materias,
            'produtos'      => $produtos,
            'locaisEstoque' => $locaisEstoque,
            'pagination'    => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }

    /**
     * Alias para processar ajuste.
     */
    public function ajustar(): void
    {
        $this->processarAjuste();
    }

    /**
     * Processar ajuste manual de estoque com local de armazenagem.
     */
    public function processarAjuste(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $userId = $_SESSION['user_id'] ?? null;

        $tipo_item = $_POST['tipo_item'] ?? '';
        $item_id = (int)($_POST['item_id'] ?? 0);
        $local_estoque_id = !empty($_POST['local_estoque_id']) ? (int)$_POST['local_estoque_id'] : null;
        $quantidade = (float)($_POST['quantidade'] ?? 0.00);
        $tipo_movimentacao = $_POST['tipo_movimentacao'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');

        if (empty($tipo_item) || $item_id <= 0 || $quantidade <= 0 || empty($tipo_movimentacao) || empty($motivo)) {
            $this->setFlash('error', 'Preencha todos os campos do formulário de ajuste corretamente.');
            $this->redirect('/estoque/ajuste');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Gravar a movimentação de estoque
            $stmt = $db->prepare(
                "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id, local_estoque_id) 
                 VALUES (:tenant_id, :tipo_item, :item_id, :quantidade, :tipo_movimentacao, :motivo, :usuario_id, :local_id)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'tipo_item' => $tipo_item,
                'item_id' => $item_id,
                'quantidade' => $quantidade,
                'tipo_movimentacao' => $tipo_movimentacao,
                'motivo' => $motivo,
                'usuario_id' => $userId,
                'local_id' => $local_estoque_id
            ]);

            // 2. Se for matéria-prima, atualizar fisicamente o estoque na tabela materias_primas
            if ($tipo_item === 'materia_prima') {
                $op = ($tipo_movimentacao === 'entrada') ? '+' : '-';
                
                $db->prepare(
                    "UPDATE materias_primas 
                     SET estoque_atual = estoque_atual {$op} :quantidade 
                     WHERE tenant_id = :tenant_id AND id = :id"
                )->execute([
                    'quantidade' => $quantidade,
                    'tenant_id' => $tenantId,
                    'id' => $item_id
                ]);
            }

            $db->commit();
            $this->setFlash('success', 'Ajuste de estoque lançado com sucesso.');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao salvar ajuste: ' . $e->getMessage());
        }

        $this->redirect('/estoque/ajuste');
    }
}
