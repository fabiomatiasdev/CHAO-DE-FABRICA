<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class PedidoVendaController extends Controller
{
    /**
     * Listar pedidos de venda.
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

        $joinClause  = 'JOIN produtos_modelos pm ON pv.produto_modelo_id = pm.id';
        $whereClause = 'WHERE pv.tenant_id = :tenant_id';
        $params      = ['tenant_id' => $tenantId];

        if (!empty($busca)) {
            $whereClause .= ' AND (pv.cliente LIKE :busca OR pm.nome LIKE :busca2 OR pm.referencia LIKE :busca3)';
            $params['busca']  = '%' . $busca . '%';
            $params['busca2'] = '%' . $busca . '%';
            $params['busca3'] = '%' . $busca . '%';
        }

        $total      = (int)(Database::fetch("SELECT COUNT(*) as total FROM pedidos_venda pv $joinClause $whereClause", $params)['total'] ?? 0);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        $pedidos = Database::fetchAll(
            "SELECT pv.*, pm.nome as modelo_nome, pm.referencia FROM pedidos_venda pv $joinClause $whereClause ORDER BY pv.id DESC LIMIT :limit OFFSET :offset",
            $params
        );

        $this->render('pedidos/index', [
            'title'      => 'Pedidos de Venda',
            'subtitle'   => 'Cadastre e acompanhe os pedidos comerciais e prazos de entrega acordados',
            'pedidos'    => $pedidos,
            'busca'      => $busca,
            'pagination' => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }

    /**
     * Exibir formulário de cadastro.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia, grade_tamanhos 
             FROM produtos_modelos 
             WHERE tenant_id = :tenant_id AND status = 'ativo' 
             ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('pedidos/form', [
            'title' => 'Novo Pedido de Venda',
            'subtitle' => 'Cadastre as especificações do pedido comercial',
            'pedido' => null,
            'modelos' => $modelos,
            'action' => '/pedidos/novo'
        ]);
    }

    /**
     * Gravar novo pedido.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $cliente = trim($_POST['cliente'] ?? '');
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tamanho = trim($_POST['tamanho'] ?? '');
        $prazo_entrega = $_POST['prazo_entrega'] ?? '';
        $status = $_POST['status'] ?? 'pendente';

        if (empty($cliente) || $produto_modelo_id <= 0 || $quantidade <= 0 || empty($tamanho) || empty($prazo_entrega)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/pedidos/novo');
        }

        try {
            Database::query(
                "INSERT INTO pedidos_venda (tenant_id, cliente, produto_modelo_id, quantidade, tamanho, prazo_entrega, status) 
                 VALUES (:tenant_id, :cliente, :produto_modelo_id, :quantidade, :tamanho, :prazo_entrega, :status)",
                [
                    'tenant_id' => $tenantId,
                    'cliente' => $cliente,
                    'produto_modelo_id' => $produto_modelo_id,
                    'quantidade' => $quantidade,
                    'tamanho' => $tamanho,
                    'prazo_entrega' => $prazo_entrega,
                    'status' => $status
                ]
            );

            $this->setFlash('success', 'Pedido cadastrado com sucesso.');
            $this->redirect('/pedidos');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao cadastrar: ' . $e->getMessage());
            $this->redirect('/pedidos/novo');
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

        $pedido = Database::fetch(
            "SELECT * FROM pedidos_venda WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$pedido) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/pedidos');
        }

        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia, grade_tamanhos 
             FROM produtos_modelos 
             WHERE tenant_id = :tenant_id AND status = 'ativo' 
             ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('pedidos/form', [
            'title' => 'Editar Pedido de Venda',
            'subtitle' => "Modifique os dados do pedido #{$id}",
            'pedido' => $pedido,
            'modelos' => $modelos,
            'action' => "/pedidos/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar pedido.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $cliente = trim($_POST['cliente'] ?? '');
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tamanho = trim($_POST['tamanho'] ?? '');
        $prazo_entrega = $_POST['prazo_entrega'] ?? '';
        $status = $_POST['status'] ?? 'pendente';

        if (empty($cliente) || $produto_modelo_id <= 0 || $quantidade <= 0 || empty($tamanho) || empty($prazo_entrega)) {
            $this->setFlash('error', 'Todos os campos obrigatórios devem ser informados.');
            $this->redirect("/pedidos/editar?id={$id}");
        }

        try {
            Database::query(
                "UPDATE pedidos_venda 
                 SET cliente = :cliente, produto_modelo_id = :produto_modelo_id, quantidade = :quantidade, 
                     tamanho = :tamanho, prazo_entrega = :prazo_entrega, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id",
                [
                    'cliente' => $cliente,
                    'produto_modelo_id' => $produto_modelo_id,
                    'quantidade' => $quantidade,
                    'tamanho' => $tamanho,
                    'prazo_entrega' => $prazo_entrega,
                    'status' => $status,
                    'tenant_id' => $tenantId,
                    'id' => $id
                ]
            );

            // Atualização opcional: Se o pedido for cancelado ou entregue, pode-se atualizar status de OP vinculada.
            // Para manter simples, a OP tem fluxo próprio de controle.

            $this->setFlash('success', 'Pedido de venda atualizado.');
            $this->redirect('/pedidos');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/pedidos/editar?id={$id}");
        }
    }

    /**
     * Excluir pedido.
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
                "DELETE FROM pedidos_venda WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );
            $this->setFlash('success', 'Pedido excluído com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao excluir pedido (verifique se já possui Ordem de Produção gerada para ele).');
        }

        $this->redirect('/pedidos');
    }
}
