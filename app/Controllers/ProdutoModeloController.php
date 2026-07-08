<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ProdutoModeloController extends Controller
{
    /**
     * Listar modelos de produtos.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $busca = trim($_GET['busca'] ?? '');

        if (!empty($busca)) {
            $modelos = Database::fetchAll(
                "SELECT * FROM produtos_modelos 
                 WHERE tenant_id = :tenant_id 
                   AND (nome LIKE :busca OR referencia LIKE :busca OR categoria LIKE :busca)
                 ORDER BY id DESC",
                [
                    'tenant_id' => $tenantId,
                    'busca' => '%' . $busca . '%'
                ]
            );
        } else {
            $modelos = Database::fetchAll(
                "SELECT * FROM produtos_modelos WHERE tenant_id = :tenant_id ORDER BY id DESC",
                ['tenant_id' => $tenantId]
            );
        }

        $this->render('produtos/index', [
            'title' => 'Modelos de Produtos',
            'subtitle' => 'Cadastre e gerencie a grade de modelos de roupas e tamanhos',
            'modelos' => $modelos,
            'busca' => $busca
        ]);
    }

    /**
     * Exibir formulário de cadastro.
     */
    public function create(): void
    {
        $this->render('produtos/form', [
            'title' => 'Novo Modelo de Produto',
            'subtitle' => 'Cadastre as especificações básicas do novo modelo',
            'modelo' => null,
            'action' => '/produtos/novo'
        ]);
    }

    /**
     * Gravar novo modelo.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $nome = trim($_POST['nome'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $grade_tamanhos = trim($_POST['grade_tamanhos'] ?? 'P,M,G,GG');
        $cor = trim($_POST['cor'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if (empty($nome) || empty($referencia) || empty($categoria) || empty($cor)) {
            $this->setFlash('error', 'Nome, Referência, Categoria e Cor são obrigatórios.');
            $this->redirect('/produtos/novo');
        }

        // Processar upload de imagem se houver
        $imagemPath = null;
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('img_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadDir . $fileName)) {
                $imagemPath = '/assets/uploads/' . $fileName;
            }
        }

        try {
            Database::query(
                "INSERT INTO produtos_modelos (tenant_id, nome, referencia, categoria, imagem, grade_tamanhos, cor, status) 
                 VALUES (:tenant_id, :nome, :referencia, :categoria, :imagem, :grade_tamanhos, :cor, :status)",
                [
                    'tenant_id' => $tenantId,
                    'nome' => $nome,
                    'referencia' => $referencia,
                    'categoria' => $categoria,
                    'imagem' => $imagemPath,
                    'grade_tamanhos' => $grade_tamanhos,
                    'cor' => $cor,
                    'status' => $status
                ]
            );

            $this->setFlash('success', 'Modelo de produto cadastrado com sucesso.');
            $this->redirect('/produtos');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao cadastrar modelo: ' . $e->getMessage());
            $this->redirect('/produtos/novo');
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

        $modelo = Database::fetch(
            "SELECT * FROM produtos_modelos WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$modelo) {
            $this->setFlash('error', 'Modelo de produto não encontrado.');
            $this->redirect('/produtos');
        }

        $this->render('produtos/form', [
            'title' => 'Editar Modelo de Produto',
            'subtitle' => "Modifique os dados do modelo {$modelo['referencia']}",
            'modelo' => $modelo,
            'action' => "/produtos/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar modelo.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $nome = trim($_POST['nome'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $grade_tamanhos = trim($_POST['grade_tamanhos'] ?? 'P,M,G,GG');
        $cor = trim($_POST['cor'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if (empty($nome) || empty($referencia) || empty($categoria) || empty($cor)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect("/produtos/editar?id={$id}");
        }

        // Recuperar imagem antiga
        $modeloOriginal = Database::fetch(
            "SELECT imagem FROM produtos_modelos WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$modeloOriginal) {
            $this->setFlash('error', 'Modelo não encontrado.');
            $this->redirect('/produtos');
        }

        $imagemPath = $modeloOriginal['imagem'];

        // Processar nova imagem se houver
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('img_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadDir . $fileName)) {
                // Opcional: deletar arquivo antigo se existia
                if ($imagemPath && file_exists(__DIR__ . '/../../public' . $imagemPath)) {
                    @unlink(__DIR__ . '/../../public' . $imagemPath);
                }
                $imagemPath = '/assets/uploads/' . $fileName;
            }
        }

        try {
            Database::query(
                "UPDATE produtos_modelos 
                 SET nome = :nome, referencia = :referencia, categoria = :categoria, 
                     imagem = :imagem, grade_tamanhos = :grade_tamanhos, cor = :cor, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id",
                [
                    'nome' => $nome,
                    'referencia' => $referencia,
                    'categoria' => $categoria,
                    'imagem' => $imagemPath,
                    'grade_tamanhos' => $grade_tamanhos,
                    'cor' => $cor,
                    'status' => $status,
                    'tenant_id' => $tenantId,
                    'id' => $id
                ]
            );

            $this->setFlash('success', 'Modelo de produto atualizado com sucesso.');
            $this->redirect('/produtos');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/produtos/editar?id={$id}");
        }
    }

    /**
     * Excluir modelo.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        try {
            // Deletar imagem associada se houver
            $modelo = Database::fetch(
                "SELECT imagem FROM produtos_modelos WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );

            if ($modelo) {
                if ($modelo['imagem'] && file_exists(__DIR__ . '/../../public' . $modelo['imagem'])) {
                    @unlink(__DIR__ . '/../../public' . $modelo['imagem']);
                }

                Database::query(
                    "DELETE FROM produtos_modelos WHERE tenant_id = :tenant_id AND id = :id",
                    ['tenant_id' => $tenantId, 'id' => $id]
                );

                $this->setFlash('success', 'Modelo excluído com sucesso.');
            } else {
                $this->setFlash('error', 'Modelo não encontrado.');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao excluir modelo (verifique se possui dependências como Ficha Técnica ou OP vinculadas).');
        }

        $this->redirect('/produtos');
    }
}
