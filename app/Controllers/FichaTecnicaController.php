<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class FichaTecnicaController extends Controller
{
    /**
     * Listar fichas técnicas.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $fichas = Database::fetchAll(
            "SELECT ft.*, pm.nome as modelo_nome, pm.referencia, pm.cor
             FROM fichas_tecnicas ft 
             JOIN produtos_modelos pm ON ft.produto_modelo_id = pm.id
             WHERE ft.tenant_id = :tenant_id
             ORDER BY ft.id DESC",
            ['tenant_id' => $tenantId]
        );

        // Para cada ficha técnica, calcular o custo estimado de matéria-prima
        foreach ($fichas as &$f) {
            $custoMP = Database::fetch(
                "SELECT SUM(fti.quantidade * mp.custo_unitario) as total 
                 FROM fichas_tecnicas_itens fti 
                 JOIN materias_primas mp ON fti.materia_prima_id = mp.id 
                 WHERE fti.ficha_tecnica_id = :ficha_id",
                ['ficha_id' => $f['id']]
            )['total'] ?? 0;

            $f['custo_materia_prima'] = $custoMP;
            $f['custo_total_estimado'] = $custoMP + $f['custo_mao_obra'];
        }

        $this->render('fichas/index', [
            'title' => 'Fichas Técnicas (BOM)',
            'subtitle' => 'Gerencie o consumo de matérias-primas e custos operacionais de cada modelo',
            'fichas' => $fichas
        ]);
    }

    /**
     * Exibir formulário de criação.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // Modelos de produtos que ainda não possuem ficha técnica cadastrada
        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia, cor 
             FROM produtos_modelos 
             WHERE tenant_id = :tenant_id 
               AND id NOT IN (SELECT produto_modelo_id FROM fichas_tecnicas WHERE tenant_id = :tenant_id)
               AND status = 'ativo'
             ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $materias = Database::fetchAll(
            "SELECT id, nome, unidade_medida, custo_unitario 
             FROM materias_primas 
             WHERE tenant_id = :tenant_id 
             ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $this->render('fichas/form', [
            'title' => 'Criar Ficha Técnica',
            'subtitle' => 'Vincule matérias-primas e custos industriais a um modelo de produto',
            'ficha' => null,
            'modelos' => $modelos,
            'materias' => $materias,
            'fichaItens' => [],
            'action' => '/fichas/novo'
        ]);
    }

    /**
     * Gravar nova ficha técnica.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $tempo_padrao = (int)($_POST['tempo_padrao'] ?? 0);
        $custo_mao_obra = (float)($_POST['custo_mao_obra'] ?? 0.00);

        // Consumos
        $materiasInput = $_POST['materias'] ?? [];
        $quantidadesInput = $_POST['quantidades'] ?? [];

        if ($produto_modelo_id <= 0 || empty($materiasInput)) {
            $this->setFlash('error', 'Selecione um modelo de produto e pelo menos uma matéria-prima.');
            $this->redirect('/fichas/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Criar Ficha Técnica
            $stmt = $db->prepare(
                "INSERT INTO fichas_tecnicas (tenant_id, produto_modelo_id, tempo_padrao, custo_mao_obra) 
                 VALUES (:tenant_id, :produto_modelo_id, :tempo_padrao, :custo_mao_obra)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_modelo_id' => $produto_modelo_id,
                'tempo_padrao' => $tempo_padrao,
                'custo_mao_obra' => $custo_mao_obra
            ]);
            $fichaId = $db->lastInsertId();

            // 2. Inserir itens
            $stmtItem = $db->prepare(
                "INSERT INTO fichas_tecnicas_itens (tenant_id, ficha_tecnica_id, materia_prima_id, quantidade) 
                 VALUES (:tenant_id, :ficha_tecnica_id, :materia_prima_id, :quantidade)"
            );

            foreach ($materiasInput as $index => $mId) {
                $qty = (float)($quantidadesInput[$index] ?? 0.0000);
                if ($mId > 0 && $qty > 0) {
                    $stmtItem->execute([
                        'tenant_id' => $tenantId,
                        'ficha_tecnica_id' => $fichaId,
                        'materia_prima_id' => $mId,
                        'quantidade' => $qty
                    ]);
                }
            }

            $db->commit();
            $this->setFlash('success', 'Ficha técnica criada com sucesso.');
            $this->redirect('/fichas');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao criar: ' . $e->getMessage());
            $this->redirect('/fichas/novo');
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

        $ficha = Database::fetch(
            "SELECT ft.*, pm.nome as modelo_nome, pm.referencia, pm.cor
             FROM fichas_tecnicas ft 
             JOIN produtos_modelos pm ON ft.produto_modelo_id = pm.id
             WHERE ft.tenant_id = :tenant_id AND ft.id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$ficha) {
            $this->setFlash('error', 'Ficha técnica não encontrada.');
            $this->redirect('/fichas');
        }

        // Listar todos os modelos ativos (inclusive o selecionado na ficha técnica)
        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia, cor 
             FROM produtos_modelos 
             WHERE tenant_id = :tenant_id 
               AND (id NOT IN (SELECT produto_modelo_id FROM fichas_tecnicas WHERE tenant_id = :tenant_id) OR id = :modelo_id)
               AND status = 'ativo'
             ORDER BY nome ASC",
            [
                'tenant_id' => $tenantId,
                'modelo_id' => $ficha['produto_modelo_id']
            ]
        );

        $materias = Database::fetchAll(
            "SELECT id, nome, unidade_medida, custo_unitario 
             FROM materias_primas 
             WHERE tenant_id = :tenant_id 
             ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $fichaItens = Database::fetchAll(
            "SELECT * FROM fichas_tecnicas_itens WHERE ficha_tecnica_id = :ficha_id",
            ['ficha_id' => $id]
        );

        $this->render('fichas/form', [
            'title' => 'Editar Ficha Técnica',
            'subtitle' => "Modifique os consumos da ficha do modelo {$ficha['referencia']}",
            'ficha' => $ficha,
            'modelos' => $modelos,
            'materias' => $materias,
            'fichaItens' => $fichaItens,
            'action' => "/fichas/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar ficha técnica.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $tempo_padrao = (int)($_POST['tempo_padrao'] ?? 0);
        $custo_mao_obra = (float)($_POST['custo_mao_obra'] ?? 0.00);

        // Consumos
        $materiasInput = $_POST['materias'] ?? [];
        $quantidadesInput = $_POST['quantidades'] ?? [];

        if ($produto_modelo_id <= 0 || empty($materiasInput)) {
            $this->setFlash('error', 'Selecione um modelo de produto e pelo menos uma matéria-prima.');
            $this->redirect("/fichas/editar?id={$id}");
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Atualizar Ficha Técnica principal
            $stmt = $db->prepare(
                "UPDATE fichas_tecnicas 
                 SET produto_modelo_id = :produto_modelo_id, tempo_padrao = :tempo_padrao, custo_mao_obra = :custo_mao_obra 
                 WHERE tenant_id = :tenant_id AND id = :id"
            );
            $stmt->execute([
                'produto_modelo_id' => $produto_modelo_id,
                'tempo_padrao' => $tempo_padrao,
                'custo_mao_obra' => $custo_mao_obra,
                'tenant_id' => $tenantId,
                'id' => $id
            ]);

            // 2. Limpar consumos antigos
            $db->prepare("DELETE FROM fichas_tecnicas_itens WHERE ficha_tecnica_id = :ficha_id")
               ->execute(['ficha_id' => $id]);

            // 3. Reinserir novos consumos
            $stmtItem = $db->prepare(
                "INSERT INTO fichas_tecnicas_itens (tenant_id, ficha_tecnica_id, materia_prima_id, quantidade) 
                 VALUES (:tenant_id, :ficha_tecnica_id, :materia_prima_id, :quantidade)"
            );

            foreach ($materiasInput as $index => $mId) {
                $qty = (float)($quantidadesInput[$index] ?? 0.0000);
                if ($mId > 0 && $qty > 0) {
                    $stmtItem->execute([
                        'tenant_id' => $tenantId,
                        'ficha_tecnica_id' => $id,
                        'materia_prima_id' => $mId,
                        'quantidade' => $qty
                    ]);
                }
            }

            $db->commit();
            $this->setFlash('success', 'Ficha técnica atualizada com sucesso.');
            $this->redirect('/fichas');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/fichas/editar?id={$id}");
        }
    }

    /**
     * Excluir ficha técnica.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        try {
            // Nota: Devido a restrições ON DELETE CASCADE no schema,
            // deletar a ficha técnica limpará automaticamente os itens associados em fichas_tecnicas_itens.
            Database::query(
                "DELETE FROM fichas_tecnicas WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );
            $this->setFlash('success', 'Ficha técnica excluída com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao excluir ficha técnica: ' . $e->getMessage());
        }

        $this->redirect('/fichas');
    }
}
