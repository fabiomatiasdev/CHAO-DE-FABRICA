<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class EstoqueLocalController extends Controller
{
    /**
     * Listar locais de estoque (Armazenadores).
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $locais = Database::fetchAll(
            "SELECT * FROM locais_estoque WHERE tenant_id = :tenant_id ORDER BY id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('estoque_locais/index', [
            'title' => 'Locais de Estoque (Armazenadores)',
            'subtitle' => 'Cadastre e gerencie os depósitos e locais de armazenagem para insumos e produtos acabados',
            'locais' => $locais
        ]);
    }

    /**
     * Cadastrar novo local de estoque.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $nome = trim($_POST['nome'] ?? '');
        $tipo = trim($_POST['tipo'] ?? 'acabados');
        $descricao = trim($_POST['descricao'] ?? '');

        if (empty($nome)) {
            $_SESSION['error'] = 'O nome do local de estoque é obrigatório.';
            header('Location: /estoque/locais');
            exit;
        }

        Database::query(
            "INSERT INTO locais_estoque (tenant_id, nome, tipo, descricao, status) 
             VALUES (:tenant_id, :nome, :tipo, :descricao, 'ativo')",
            [
                'tenant_id' => $tenantId,
                'nome'      => $nome,
                'tipo'      => $tipo,
                'descricao' => $descricao
            ]
        );

        $_SESSION['success'] = 'Local de estoque cadastrado com sucesso!';
        header('Location: /estoque/locais');
        exit;
    }

    /**
     * Atualizar local de estoque.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $tipo = trim($_POST['tipo'] ?? 'acabados');
        $descricao = trim($_POST['descricao'] ?? '');
        $status = trim($_POST['status'] ?? 'ativo');

        if ($id <= 0 || empty($nome)) {
            $_SESSION['error'] = 'Dados inválidos para atualização.';
            header('Location: /estoque/locais');
            exit;
        }

        Database::query(
            "UPDATE locais_estoque 
             SET nome = :nome, tipo = :tipo, descricao = :descricao, status = :status 
             WHERE id = :id AND tenant_id = :tenant_id",
            [
                'id'        => $id,
                'tenant_id' => $tenantId,
                'nome'      => $nome,
                'tipo'      => $tipo,
                'descricao' => $descricao,
                'status'    => $status
            ]
        );

        $_SESSION['success'] = 'Local de estoque atualizado com sucesso!';
        header('Location: /estoque/locais');
        exit;
    }

    /**
     * Excluir/Desativar local de estoque.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            Database::query(
                "UPDATE locais_estoque SET status = 'inativo' WHERE id = :id AND tenant_id = :tenant_id",
                ['id' => $id, 'tenant_id' => $tenantId]
            );
            $_SESSION['success'] = 'Local de estoque inativado com sucesso!';
        }

        header('Location: /estoque/locais');
        exit;
    }
}
