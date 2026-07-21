<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class MateriaPrimaController extends Controller
{
    /**
     * Listar matérias-primas.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $busca    = trim($_GET['busca'] ?? '');
        $perPage  = 10;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $whereClause = 'WHERE tenant_id = :tenant_id';
        $params      = ['tenant_id' => $tenantId];

        if (!empty($busca)) {
            $whereClause .= ' AND (nome LIKE :busca OR fornecedor LIKE :busca2)';
            $params['busca']  = '%' . $busca . '%';
            $params['busca2'] = '%' . $busca . '%';
        }

        $total      = (int)(Database::fetch("SELECT COUNT(*) as total FROM materias_primas $whereClause", $params)['total'] ?? 0);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        $itens = Database::fetchAll(
            "SELECT * FROM materias_primas $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset",
            $params
        );

        $this->render('materias/index', [
            'title'      => 'Matérias-Primas',
            'subtitle'   => 'Cadastre e acompanhe o estoque de tecidos, aviamentos, botões, zíperes, etc.',
            'itens'      => $itens,
            'busca'      => $busca,
            'pagination' => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }

    /**
     * Exibir formulário de cadastro.
     */
    public function create(): void
    {
        $this->render('materias/form', [
            'title' => 'Nova Matéria-Prima',
            'subtitle' => 'Insira os dados do insumo para controle de estoque e ficha técnica',
            'item' => null,
            'action' => '/materias/novo'
        ]);
    }

    /**
     * Gravar nova matéria-prima.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $userId = $_SESSION['user_id'] ?? null;

        $nome = trim($_POST['nome'] ?? '');
        $unidade_medida = trim($_POST['unidade_medida'] ?? 'M');
        $custo_unitario = (float)($_POST['custo_unitario'] ?? 0.0000);
        $fornecedor = trim($_POST['fornecedor'] ?? '');
        $estoque_atual = (float)($_POST['estoque_atual'] ?? 0.00);
        $estoque_minimo = (float)($_POST['estoque_minimo'] ?? 0.00);

        if (empty($nome) || empty($unidade_medida)) {
            $this->setFlash('error', 'Nome e Unidade de Medida são obrigatórios.');
            $this->redirect('/materias/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir Matéria-Prima
            $stmt = $db->prepare(
                "INSERT INTO materias_primas (tenant_id, nome, unidade_medida, custo_unitario, fornecedor, estoque_atual, estoque_minimo) 
                 VALUES (:tenant_id, :nome, :unidade_medida, :custo_unitario, :fornecedor, :estoque_atual, :estoque_minimo)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'unidade_medida' => $unidade_medida,
                'custo_unitario' => $custo_unitario,
                'fornecedor' => $fornecedor,
                'estoque_atual' => $estoque_atual,
                'estoque_minimo' => $estoque_minimo
            ]);
            $materiaId = $db->lastInsertId();

            // 2. Se estoque inicial for maior que zero, registrar movimentação
            if ($estoque_atual > 0) {
                $stmtMov = $db->prepare(
                    "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id) 
                     VALUES (:tenant_id, 'materia_prima', :item_id, :quantidade, 'entrada', 'Saldo inicial de cadastro', :usuario_id)"
                );
                $stmtMov->execute([
                    'tenant_id' => $tenantId,
                    'item_id' => $materiaId,
                    'quantidade' => $estoque_atual,
                    'usuario_id' => $userId
                ]);
            }

            $db->commit();
            $this->setFlash('success', 'Matéria-prima cadastrada com sucesso.');
            $this->redirect('/materias');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao cadastrar: ' . $e->getMessage());
            $this->redirect('/materias/novo');
        }
    }

    /**
     * Exibir formulário de edição.
     */
    public function edit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $item = Database::fetch(
            "SELECT * FROM materias_primas WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$item) {
            $this->setFlash('error', 'Insumo não encontrado.');
            $this->redirect('/materias');
        }

        $this->render('materias/form', [
            'title' => 'Editar Matéria-Prima',
            'subtitle' => "Modifique as especificações do insumo {$item['nome']}",
            'item' => $item,
            'action' => "/materias/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar matéria-prima.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $nome = trim($_POST['nome'] ?? '');
        $unidade_medida = trim($_POST['unidade_medida'] ?? 'M');
        $custo_unitario = (float)($_POST['custo_unitario'] ?? 0.0000);
        $fornecedor = trim($_POST['fornecedor'] ?? '');
        $estoque_minimo = (float)($_POST['estoque_minimo'] ?? 0.00);

        // Obs: Estoque atual não deve ser atualizado diretamente aqui para evitar furos de estoque.
        // Deve ser ajustado na tela de ajustes de estoque.

        if (empty($nome) || empty($unidade_medida)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect("/materias/editar?id={$id}");
        }

        try {
            Database::query(
                "UPDATE materias_primas 
                 SET nome = :nome, unidade_medida = :unidade_medida, custo_unitario = :custo_unitario, 
                     fornecedor = :fornecedor, estoque_minimo = :estoque_minimo 
                 WHERE tenant_id = :tenant_id AND id = :id",
                [
                    'nome' => $nome,
                    'unidade_medida' => $unidade_medida,
                    'custo_unitario' => $custo_unitario,
                    'fornecedor' => $fornecedor,
                    'estoque_minimo' => $estoque_minimo,
                    'tenant_id' => $tenantId,
                    'id' => $id
                ]
            );

            $this->setFlash('success', 'Insumo atualizado com sucesso.');
            $this->redirect('/materias');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/materias/editar?id={$id}");
        }
    }

    /**
     * Excluir matéria-prima.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        try {
            Database::query(
                "DELETE FROM materias_primas WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );
            $this->setFlash('success', 'Matéria-prima excluída com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao excluir insumo (verifique se está sendo utilizado em alguma Ficha Técnica).');
        }

        $this->redirect('/materias');
    }
}
