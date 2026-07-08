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

        $this->render('corte/form', [
            'title' => 'Lançar Ordem de Corte',
            'subtitle' => 'Registre a quantidade de peças cortadas e o tamanho correspondente',
            'ops' => $ops,
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
        $tamanho = trim($_POST['tamanho'] ?? '');
        $quantidade_cortada = (int)($_POST['quantidade_cortada'] ?? 0);
        $responsavel = trim($_POST['responsavel'] ?? '');
        $data_corte = $_POST['data_corte'] ?? date('Y-m-d');

        if ($ordem_producao_id <= 0 || empty($tamanho) || $quantidade_cortada <= 0 || empty($responsavel)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/corte/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir Ordem de Corte
            $stmt = $db->prepare(
                "INSERT INTO ordens_corte (tenant_id, ordem_producao_id, tamanho, quantidade_cortada, responsavel, data_corte) 
                 VALUES (:tenant_id, :ordem_producao_id, :tamanho, :quantidade_cortada, :responsavel, :data_corte)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'ordem_producao_id' => $ordem_producao_id,
                'tamanho' => $tamanho,
                'quantidade_cortada' => $quantidade_cortada,
                'responsavel' => $responsavel,
                'data_corte' => $data_corte
            ]);

            // 2. Atualizar a etapa "corte" para "conclúido" no Chão de Fábrica para esta OP
            $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = 'conclúido', responsavel = :responsavel 
                 WHERE ordem_producao_id = :op_id AND etapa = 'corte'"
            )->execute([
                'responsavel' => $responsavel,
                'op_id' => $ordem_producao_id
            ]);

            // 3. Atualizar a etapa "costura" para "em andamento" no Chão de Fábrica
            $db->prepare(
                "UPDATE chao_fabrica_etapas 
                 SET status = 'em andamento' 
                 WHERE ordem_producao_id = :op_id AND etapa = 'costura' AND status = 'pendente'"
            )->execute(['op_id' => $ordem_producao_id]);

            // 4. Mudar status da OP principal para "em andamento" se estava "aberta"
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
}
