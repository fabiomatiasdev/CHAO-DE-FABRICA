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
            'title' => 'Fichas Técnicas',
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
               AND id NOT IN (SELECT produto_modelo_id FROM fichas_tecnicas WHERE tenant_id = :tenant_id2)
               AND status = 'ativo'
             ORDER BY nome ASC",
            ['tenant_id' => $tenantId, 'tenant_id2' => $tenantId]
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
            'operacoes' => [],
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
        $produto_modelo_id = (int) ($_POST['produto_modelo_id'] ?? 0);
        $tempo_padrao = (float) ($_POST['tempo_padrao'] ?? 0);
        $custo_mao_obra = (float) ($_POST['custo_mao_obra'] ?? 0.00);

        // Folgas
        $folga_necessidades = (float) ($_POST['folga_necessidades'] ?? 5.0);
        $folga_fadiga = (float) ($_POST['folga_fadiga'] ?? 5.0);
        $folga_atrasos = (float) ($_POST['folga_atrasos'] ?? 5.0);
        $folga_total = (float) ($_POST['folga_total'] ?? 15.0);

        // Operações cronometradas
        $op_operadores = $_POST['op_operadores'] ?? [];
        $op_descricoes = $_POST['op_descricoes'] ?? [];
        $op_tempo1 = $_POST['op_tempo1'] ?? [];
        $op_tempo2 = $_POST['op_tempo2'] ?? [];
        $op_tempo3 = $_POST['op_tempo3'] ?? [];
        $op_observacoes = $_POST['op_observacoes'] ?? [];

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
                "INSERT INTO fichas_tecnicas (tenant_id, produto_modelo_id, tempo_padrao, custo_mao_obra, folga_necessidades, folga_fadiga, folga_atrasos, folga_total) 
                 VALUES (:tenant_id, :produto_modelo_id, :tempo_padrao, :custo_mao_obra, :folga_necessidades, :folga_fadiga, :folga_atrasos, :folga_total)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_modelo_id' => $produto_modelo_id,
                'tempo_padrao' => $tempo_padrao,
                'custo_mao_obra' => $custo_mao_obra,
                'folga_necessidades' => $folga_necessidades,
                'folga_fadiga' => $folga_fadiga,
                'folga_atrasos' => $folga_atrasos,
                'folga_total' => $folga_total
            ]);
            $fichaId = $db->lastInsertId();

            // 1b. Inserir operações cronometradas
            if (!empty($op_descricoes)) {
                $stmtOp = $db->prepare(
                    "INSERT INTO fichas_tecnicas_operacoes (tenant_id, ficha_tecnica_id, operador, descricao_operacao, tempo_1, tempo_2, tempo_3, media, observacoes)
                     VALUES (:tenant_id, :ficha_tecnica_id, :operador, :descricao_operacao, :tempo_1, :tempo_2, :tempo_3, :media, :observacoes)"
                );

                foreach ($op_descricoes as $index => $desc) {
                    $desc = trim($desc);
                    if (!empty($desc)) {
                        $t1 = $this->parseTempoInput($op_tempo1[$index] ?? '0');
                        $t2 = $this->parseTempoInput($op_tempo2[$index] ?? '0');
                        $t3 = $this->parseTempoInput($op_tempo3[$index] ?? '0');
                        $media = ($t1 + $t2 + $t3) / 3;
                        $stmtOp->execute([
                            'tenant_id' => $tenantId,
                            'ficha_tecnica_id' => $fichaId,
                            'operador' => trim($op_operadores[$index] ?? ''),
                            'descricao_operacao' => $desc,
                            'tempo_1' => $t1,
                            'tempo_2' => $t2,
                            'tempo_3' => $t3,
                            'media' => $media,
                            'observacoes' => trim($op_observacoes[$index] ?? '')
                        ]);
                    }
                }
            }

            // 2. Inserir itens (BOM)
            $stmtItem = $db->prepare(
                "INSERT INTO fichas_tecnicas_itens (tenant_id, ficha_tecnica_id, materia_prima_id, quantidade) 
                 VALUES (:tenant_id, :ficha_tecnica_id, :materia_prima_id, :quantidade)"
            );

            foreach ($materiasInput as $index => $mId) {
                $qty = (float) ($quantidadesInput[$index] ?? 0.0000);
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
        $id = (int) ($_GET['id'] ?? 0);

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
               AND (id NOT IN (SELECT produto_modelo_id FROM fichas_tecnicas WHERE tenant_id = :tenant_id2) OR id = :modelo_id)
               AND status = 'ativo'
             ORDER BY nome ASC",
            [
                'tenant_id'  => $tenantId,
                'tenant_id2' => $tenantId,
                'modelo_id'  => $ficha['produto_modelo_id']
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

        // Buscar operações cronometradas
        $operacoes = Database::fetchAll(
            "SELECT * FROM fichas_tecnicas_operacoes WHERE ficha_tecnica_id = :ficha_id ORDER BY id ASC",
            ['ficha_id' => $id]
        );

        $this->render('fichas/form', [
            'title' => 'Editar Ficha Técnica',
            'subtitle' => "Modifique os consumos da ficha do modelo {$ficha['referencia']}",
            'ficha' => $ficha,
            'modelos' => $modelos,
            'materias' => $materias,
            'fichaItens' => $fichaItens,
            'operacoes' => $operacoes,
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
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

        $produto_modelo_id = (int) ($_POST['produto_modelo_id'] ?? 0);
        $tempo_padrao = (float) ($_POST['tempo_padrao'] ?? 0);
        $custo_mao_obra = (float) ($_POST['custo_mao_obra'] ?? 0.00);

        // Folgas
        $folga_necessidades = (float) ($_POST['folga_necessidades'] ?? 5.0);
        $folga_fadiga = (float) ($_POST['folga_fadiga'] ?? 5.0);
        $folga_atrasos = (float) ($_POST['folga_atrasos'] ?? 5.0);
        $folga_total = (float) ($_POST['folga_total'] ?? 15.0);

        // Operações cronometradas
        $op_operadores = $_POST['op_operadores'] ?? [];
        $op_descricoes = $_POST['op_descricoes'] ?? [];
        $op_tempo1 = $_POST['op_tempo1'] ?? [];
        $op_tempo2 = $_POST['op_tempo2'] ?? [];
        $op_tempo3 = $_POST['op_tempo3'] ?? [];
        $op_observacoes = $_POST['op_observacoes'] ?? [];

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
                 SET produto_modelo_id = :produto_modelo_id, tempo_padrao = :tempo_padrao, custo_mao_obra = :custo_mao_obra,
                     folga_necessidades = :folga_necessidades, folga_fadiga = :folga_fadiga, folga_atrasos = :folga_atrasos, folga_total = :folga_total
                 WHERE tenant_id = :tenant_id AND id = :id"
            );
            $stmt->execute([
                'produto_modelo_id' => $produto_modelo_id,
                'tempo_padrao' => $tempo_padrao,
                'custo_mao_obra' => $custo_mao_obra,
                'folga_necessidades' => $folga_necessidades,
                'folga_fadiga' => $folga_fadiga,
                'folga_atrasos' => $folga_atrasos,
                'folga_total' => $folga_total,
                'tenant_id' => $tenantId,
                'id' => $id
            ]);

            // 1b. Limpar e reinserir operações cronometradas
            $db->prepare("DELETE FROM fichas_tecnicas_operacoes WHERE ficha_tecnica_id = :ficha_id")
                ->execute(['ficha_id' => $id]);

            if (!empty($op_descricoes)) {
                $stmtOp = $db->prepare(
                    "INSERT INTO fichas_tecnicas_operacoes (tenant_id, ficha_tecnica_id, operador, descricao_operacao, tempo_1, tempo_2, tempo_3, media, observacoes)
                     VALUES (:tenant_id, :ficha_tecnica_id, :operador, :descricao_operacao, :tempo_1, :tempo_2, :tempo_3, :media, :observacoes)"
                );

                foreach ($op_descricoes as $index => $desc) {
                    $desc = trim($desc);
                    if (!empty($desc)) {
                        $t1 = $this->parseTempoInput($op_tempo1[$index] ?? '0');
                        $t2 = $this->parseTempoInput($op_tempo2[$index] ?? '0');
                        $t3 = $this->parseTempoInput($op_tempo3[$index] ?? '0');
                        $media = ($t1 + $t2 + $t3) / 3;
                        $stmtOp->execute([
                            'tenant_id' => $tenantId,
                            'ficha_tecnica_id' => $id,
                            'operador' => trim($op_operadores[$index] ?? ''),
                            'descricao_operacao' => $desc,
                            'tempo_1' => $t1,
                            'tempo_2' => $t2,
                            'tempo_3' => $t3,
                            'media' => $media,
                            'observacoes' => trim($op_observacoes[$index] ?? '')
                        ]);
                    }
                }
            }

            // 2. Limpar consumos antigos
            $db->prepare("DELETE FROM fichas_tecnicas_itens WHERE ficha_tecnica_id = :ficha_id")
                ->execute(['ficha_id' => $id]);

            // 3. Reinserir novos consumos
            $stmtItem = $db->prepare(
                "INSERT INTO fichas_tecnicas_itens (tenant_id, ficha_tecnica_id, materia_prima_id, quantidade) 
                 VALUES (:tenant_id, :ficha_tecnica_id, :materia_prima_id, :quantidade)"
            );

            foreach ($materiasInput as $index => $mId) {
                $qty = (float) ($quantidadesInput[$index] ?? 0.0000);
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
            $this->setFlash('success', 'Ficha técnica actualizada com sucesso.');
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
        $id = (int) ($_GET['id'] ?? 0);

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
    /**
     * Converte string de tempo "MM:SS" ou decimal para minutos decimais.
     */
    private function parseTempoInput(string $val): float
    {
        $val = trim($val);
        if (str_contains($val, ':')) {
            $parts = explode(':', $val);
            $m = (int)($parts[0] ?? 0);
            $s = (int)($parts[1] ?? 0);
            return round($m + $s / 60, 4);
        }
        return round((float)$val, 4);
    }
}
