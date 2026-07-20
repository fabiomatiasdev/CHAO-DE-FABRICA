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
                   AND (nome LIKE :busca OR referencia LIKE :busca2 OR categoria LIKE :busca3)
                 ORDER BY id DESC",
                [
                    'tenant_id' => $tenantId,
                    'busca'     => '%' . $busca . '%',
                    'busca2'    => '%' . $busca . '%',
                    'busca3'    => '%' . $busca . '%',
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
        $status = $_POST['status'] ?? 'ativo';

        // Decodificar JSON de variantes
        $variantes = json_decode($_POST['variantes_json'] ?? '[]', true);

        if (empty($nome) || empty($referencia) || empty($categoria) || empty($variantes)) {
            $this->setFlash('error', 'Nome, Referência, Categoria e pelo menos uma variante de Cor/Tamanho são obrigatórios.');
            $this->redirect('/produtos/novo');
        }

        // Processar Cor e Grade de Tamanhos concatenadas para compatibilidade legada
        $coresList = array_unique(array_filter(array_column($variantes, 'cor')));
        $coresStr = implode(', ', $coresList);
        if (empty($coresStr)) {
            $coresStr = 'Geral';
        }

        $todosTamanhos = [];
        foreach ($variantes as $v) {
            if (!empty($v['tamanhos'])) {
                $todosTamanhos = array_merge($todosTamanhos, $v['tamanhos']);
            }
        }
        $todosTamanhos = array_unique(array_filter($todosTamanhos));
        $gradeTamanhosStr = implode(',', $todosTamanhos);
        if (empty($gradeTamanhosStr)) {
            $gradeTamanhosStr = 'P,M,G,GG';
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
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir Modelo
            $stmt = $db->prepare(
                "INSERT INTO produtos_modelos (tenant_id, nome, referencia, categoria, imagem, grade_tamanhos, cor, status) 
                 VALUES (:tenant_id, :nome, :referencia, :categoria, :imagem, :grade_tamanhos, :cor, :status)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'referencia' => $referencia,
                'categoria' => $categoria,
                'imagem' => $imagemPath,
                'grade_tamanhos' => $gradeTamanhosStr,
                'cor' => $coresStr,
                'status' => $status
            ]);
            $modeloId = $db->lastInsertId();

            // 2. Inserir Variantes
            $stmtVariante = $db->prepare(
                "INSERT INTO produtos_variantes (tenant_id, produto_modelo_id, cor, tamanho, estoque_atual, estoque_minimo)
                 VALUES (:tenant_id, :produto_modelo_id, :cor, :tamanho, 0, 0)"
            );

            foreach ($variantes as $v) {
                $cName = trim($v['cor']);
                if (empty($cName)) continue;
                foreach ($v['tamanhos'] as $t) {
                    $tName = trim($t);
                    if (empty($tName)) continue;

                    $stmtVariante->execute([
                        'tenant_id' => $tenantId,
                        'produto_modelo_id' => $modeloId,
                        'cor' => $cName,
                        'tamanho' => $tName
                    ]);
                }
            }

            $db->commit();
            $this->setFlash('success', 'Modelo de produto e suas variantes cadastrados com sucesso.');
            $this->redirect('/produtos');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
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

        // Buscar variantes cadastradas
        $variantes = Database::fetchAll(
            "SELECT id, cor, tamanho, estoque_atual, estoque_minimo 
             FROM produtos_variantes 
             WHERE tenant_id = :tenant_id AND produto_modelo_id = :modelo_id 
             ORDER BY id ASC",
            ['tenant_id' => $tenantId, 'modelo_id' => $id]
        );

        $this->render('produtos/form', [
            'title' => 'Editar Modelo de Produto',
            'subtitle' => "Modifique os dados do modelo {$modelo['referencia']}",
            'modelo' => $modelo,
            'variantes' => $variantes,
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
        $status = $_POST['status'] ?? 'ativo';

        // Decodificar JSON de variantes
        $variantes = json_decode($_POST['variantes_json'] ?? '[]', true);

        if (empty($nome) || empty($referencia) || empty($categoria) || empty($variantes)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios e defina pelo menos uma variante de Cor/Tamanho.');
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

        // Processar Cor e Grade de Tamanhos concatenadas para compatibilidade legada
        $coresList = array_unique(array_filter(array_column($variantes, 'cor')));
        $coresStr = implode(', ', $coresList);
        if (empty($coresStr)) {
            $coresStr = 'Geral';
        }

        $todosTamanhos = [];
        foreach ($variantes as $v) {
            if (!empty($v['tamanhos'])) {
                $todosTamanhos = array_merge($todosTamanhos, $v['tamanhos']);
            }
        }
        $todosTamanhos = array_unique(array_filter($todosTamanhos));
        $gradeTamanhosStr = implode(',', $todosTamanhos);
        if (empty($gradeTamanhosStr)) {
            $gradeTamanhosStr = 'P,M,G,GG';
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
                if ($imagemPath && file_exists(__DIR__ . '/../../public' . $imagemPath)) {
                    @unlink(__DIR__ . '/../../public' . $imagemPath);
                }
                $imagemPath = '/assets/uploads/' . $fileName;
            }
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Atualizar Modelo
            $stmt = $db->prepare(
                "UPDATE produtos_modelos 
                 SET nome = :nome, referencia = :referencia, categoria = :categoria, 
                     imagem = :imagem, grade_tamanhos = :grade_tamanhos, cor = :cor, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id"
            );
            $stmt->execute([
                'nome' => $nome,
                'referencia' => $referencia,
                'categoria' => $categoria,
                'imagem' => $imagemPath,
                'grade_tamanhos' => $gradeTamanhosStr,
                'cor' => $coresStr,
                'status' => $status,
                'tenant_id' => $tenantId,
                'id' => $id
            ]);

            // 2. Sincronizar Variantes
            $variantesExistentes = Database::fetchAll(
                "SELECT id, cor, tamanho, estoque_atual FROM produtos_variantes WHERE produto_modelo_id = :modelo_id AND tenant_id = :tenant_id",
                ['modelo_id' => $id, 'tenant_id' => $tenantId]
            );

            $variantesExistentesMap = [];
            foreach ($variantesExistentes as $ve) {
                $key = "{$ve['cor']}-{$ve['tamanho']}";
                $variantesExistentesMap[$key] = $ve;
            }

            $chavesEnviadas = [];
            $stmtInsertVar = $db->prepare(
                "INSERT INTO produtos_variantes (tenant_id, produto_modelo_id, cor, tamanho, estoque_atual, estoque_minimo)
                 VALUES (:tenant_id, :produto_modelo_id, :cor, :tamanho, 0, 0)"
            );

            foreach ($variantes as $v) {
                $cName = trim($v['cor']);
                if (empty($cName)) continue;
                foreach ($v['tamanhos'] as $t) {
                    $tName = trim($t);
                    if (empty($tName)) continue;

                    $key = "{$cName}-{$tName}";
                    $chavesEnviadas[] = $key;

                    if (!isset($variantesExistentesMap[$key])) {
                        $stmtInsertVar->execute([
                            'tenant_id' => $tenantId,
                            'produto_modelo_id' => $id,
                            'cor' => $cName,
                            'tamanho' => $tName
                        ]);
                    }
                }
            }

            // Excluir variantes antigas removidas que têm estoque zero
            $stmtDeleteVar = $db->prepare(
                "DELETE FROM produtos_variantes WHERE id = :id AND tenant_id = :tenant_id"
            );

            foreach ($variantesExistentesMap as $key => $ve) {
                if (!in_array($key, $chavesEnviadas)) {
                    if ((int)$ve['estoque_atual'] === 0) {
                        $stmtDeleteVar->execute([
                            'id' => $ve['id'],
                            'tenant_id' => $tenantId
                        ]);
                    }
                }
            }

            $db->commit();
            $this->setFlash('success', 'Modelo de produto e suas variantes atualizados com sucesso.');
            $this->redirect('/produtos');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
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

    /**
     * Exibir listagem de estoque de produtos acabados (variantes).
     */
    public function estoque(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // Buscar todas as variantes cadastradas agrupadas pelo produto modelo
        $variantes = Database::fetchAll(
            "SELECT pv.*, pm.nome as modelo_nome, pm.referencia, pm.categoria
             FROM produtos_variantes pv
             JOIN produtos_modelos pm ON pv.produto_modelo_id = pm.id
             WHERE pv.tenant_id = :tenant_id
             ORDER BY pm.nome ASC, pv.cor ASC, pv.tamanho ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('produtos/estoque', [
            'title' => 'Estoque de Produtos Acabados',
            'subtitle' => 'Monitore e controle a quantidade física de peças prontas por cor e tamanho',
            'variantes' => $variantes
        ]);
    }

    /**
     * Lançar ajuste manual de estoque de uma variante de produto.
     */
    public function ajustarEstoque(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $varianteId = (int)($_POST['variante_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tipo = $_POST['tipo_movimentacao'] ?? 'entrada';
        $motivo = trim($_POST['motivo'] ?? '');
        $userId = $_SESSION['user_id'] ?? null;

        if ($varianteId <= 0 || $quantidade <= 0 || empty($motivo)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios para o ajuste de estoque.');
            $this->redirect('/produtos/estoque');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Buscar a variante correspondente
            $variante = Database::fetch(
                "SELECT * FROM produtos_variantes WHERE id = :id AND tenant_id = :tenant_id",
                ['id' => $varianteId, 'tenant_id' => $tenantId]
            );

            if (!$variante) {
                throw new \Exception("Variante de produto não encontrada.");
            }

            // Calcular novo estoque
            $novoEstoque = $variante['estoque_atual'];
            if ($tipo === 'entrada') {
                $novoEstoque += $quantidade;
            } else {
                $novoEstoque -= $quantidade;
                if ($novoEstoque < 0) {
                    throw new \Exception("Saldo insuficiente em estoque para esta saída (Estoque atual: {$variante['estoque_atual']}).");
                }
            }

            // 1. Atualizar estoque
            $db->prepare("UPDATE produtos_variantes SET estoque_atual = :estoque WHERE id = :id AND tenant_id = :tenant_id")
               ->execute(['estoque' => $novoEstoque, 'id' => $varianteId, 'tenant_id' => $tenantId]);

            // 2. Gravar movimentação de estoque
            $db->prepare(
                "INSERT INTO estoque_movimentacoes (tenant_id, tipo_item, item_id, quantidade, tipo_movimentacao, motivo, usuario_id) 
                 VALUES (:tenant_id, 'produto_variante', :item_id, :quantidade, :tipo, :motivo, :usuario_id)"
            )->execute([
                'tenant_id' => $tenantId,
                'item_id' => $varianteId,
                'quantidade' => $quantidade,
                'tipo' => $tipo,
                'motivo' => "Ajuste Manual: " . $motivo,
                'usuario_id' => $userId
            ]);

            $db->commit();
            $this->setFlash('success', 'Estoque de produto acabado ajustado com sucesso.');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao ajustar estoque: ' . $e->getMessage());
        }

        $this->redirect('/produtos/estoque');
    }
}
