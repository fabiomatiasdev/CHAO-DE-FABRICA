<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class OrdemProducaoController extends Controller
{
    /**
     * Listar ordens de produção.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $busca = trim($_GET['busca'] ?? '');

        if (!empty($busca)) {
            $ops = Database::fetchAll(
                "SELECT op.*, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome, pv.cliente as cliente_nome
                 FROM ordens_producao op
                 JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
                 LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
                 LEFT JOIN pedidos_venda pv ON op.pedido_venda_id = pv.id
                 WHERE op.tenant_id = :tenant_id 
                   AND (pm.nome LIKE :busca OR pm.referencia LIKE :busca OR of.nome LIKE :busca)
                 ORDER BY op.id DESC",
                [
                    'tenant_id' => $tenantId,
                    'busca' => '%' . $busca . '%'
                ]
            );
        } else {
            $ops = Database::fetchAll(
                "SELECT op.*, pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome, pv.cliente as cliente_nome
                 FROM ordens_producao op
                 JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
                 LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
                 LEFT JOIN pedidos_venda pv ON op.pedido_venda_id = pv.id
                 WHERE op.tenant_id = :tenant_id 
                 ORDER BY op.id DESC",
                ['tenant_id' => $tenantId]
            );
        }

        $this->render('ops/index', [
            'title' => 'Ordens de Produção (OP)',
            'subtitle' => 'Planeje, distribua para oficinas e acompanhe a fabricação de lotes de roupas',
            'ops' => $ops,
            'busca' => $busca
        ]);
    }

    /**
     * Formulário de criação de OP.
     */
    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia FROM produtos_modelos WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $oficinas = Database::fetchAll(
            "SELECT id, nome FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        // Pedidos pendentes de venda para vincular
        $pedidos = Database::fetchAll(
            "SELECT pv.id, pv.cliente, pv.quantidade, pv.tamanho, pm.referencia 
             FROM pedidos_venda pv
             JOIN produtos_modelos pm ON pv.produto_modelo_id = pm.id
             WHERE pv.tenant_id = :tenant_id AND pv.status = 'pendente'
             ORDER BY pv.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('ops/form', [
            'title' => 'Nova Ordem de Produção',
            'subtitle' => 'Crie uma nova OP vinculada a modelo ou pedido comercial',
            'op' => null,
            'modelos' => $modelos,
            'oficinas' => $oficinas,
            'pedidos' => $pedidos,
            'action' => '/ops/novo'
        ]);
    }

    /**
     * Gravar nova OP.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $pedido_venda_id = !empty($_POST['pedido_venda_id']) ? (int)$_POST['pedido_venda_id'] : null;
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $oficina_faccao_id = !empty($_POST['oficina_faccao_id']) ? (int)$_POST['oficina_faccao_id'] : null;
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $prazo = $_POST['prazo'] ?? '';
        $status = $_POST['status'] ?? 'aberta';

        if ($produto_modelo_id <= 0 || $quantidade <= 0 || empty($prazo)) {
            $this->setFlash('error', 'Preencha os campos obrigatórios (Modelo, Quantidade e Prazo).');
            $this->redirect('/ops/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir OP
            $stmt = $db->prepare(
                "INSERT INTO ordens_producao (tenant_id, pedido_venda_id, produto_modelo_id, oficina_faccao_id, quantidade, prazo, status) 
                 VALUES (:tenant_id, :pedido_venda_id, :produto_modelo_id, :oficina_faccao_id, :quantidade, :prazo, :status)"
            );
            $stmt->execute([
                'tenant_id' => $tenantId,
                'pedido_venda_id' => $pedido_venda_id,
                'produto_modelo_id' => $produto_modelo_id,
                'oficina_faccao_id' => $oficina_faccao_id,
                'quantidade' => $quantidade,
                'prazo' => $prazo,
                'status' => $status
            ]);
            $opId = $db->lastInsertId();

            // 2. Criar automaticamente as 5 etapas no Chão de Fábrica
            $stmtEtapa = $db->prepare(
                "INSERT INTO chao_fabrica_etapas (tenant_id, ordem_producao_id, etapa, status) 
                 VALUES (:tenant_id, :op_id, :etapa, 'pendente')"
            );

            $etapas = ['corte', 'costura', 'acabamento', 'revisão', 'embalagem'];
            foreach ($etapas as $et) {
                $stmtEtapa->execute([
                    'tenant_id' => $tenantId,
                    'op_id' => $opId,
                    'etapa' => $et
                ]);
            }

            // 3. Se estiver vinculada a um pedido de venda, atualizar o status do pedido para "em produção"
            if ($pedido_venda_id) {
                $db->prepare("UPDATE pedidos_venda SET status = 'em produção' WHERE id = :pedido_id")
                   ->execute(['pedido_id' => $pedido_venda_id]);
            }

            $db->commit();
            $this->setFlash('success', 'Ordem de Produção (OP) criada com sucesso.');
            $this->redirect('/ops');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao criar OP: ' . $e->getMessage());
            $this->redirect('/ops/novo');
        }
    }

    /**
     * Formulário de edição de OP.
     */
    public function edit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $op = Database::fetch(
            "SELECT * FROM ordens_producao WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$op) {
            $this->setFlash('error', 'Ordem de Produção não encontrada.');
            $this->redirect('/ops');
        }

        $modelos = Database::fetchAll(
            "SELECT id, nome, referencia FROM produtos_modelos WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $oficinas = Database::fetchAll(
            "SELECT id, nome FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND status = 'ativo' ORDER BY nome ASC",
            ['tenant_id' => $tenantId]
        );

        $pedidos = Database::fetchAll(
            "SELECT pv.id, pv.cliente, pv.quantidade, pv.tamanho, pm.referencia 
             FROM pedidos_venda pv
             JOIN produtos_modelos pm ON pv.produto_modelo_id = pm.id
             WHERE pv.tenant_id = :tenant_id 
               AND (pv.status = 'pendente' OR pv.id = :ped_id)
             ORDER BY pv.id DESC",
            ['tenant_id' => $tenantId, 'ped_id' => $op['pedido_venda_id']]
        );

        $this->render('ops/form', [
            'title' => 'Editar Ordem de Produção',
            'subtitle' => "Ajuste as definições da OP #{$id}",
            'op' => $op,
            'modelos' => $modelos,
            'oficinas' => $oficinas,
            'pedidos' => $pedidos,
            'action' => "/ops/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar OP.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $pedido_venda_id = !empty($_POST['pedido_venda_id']) ? (int)$_POST['pedido_venda_id'] : null;
        $produto_modelo_id = (int)($_POST['produto_modelo_id'] ?? 0);
        $oficina_faccao_id = !empty($_POST['oficina_faccao_id']) ? (int)$_POST['oficina_faccao_id'] : null;
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $prazo = $_POST['prazo'] ?? '';
        $status = $_POST['status'] ?? 'aberta';

        if ($produto_modelo_id <= 0 || $quantidade <= 0 || empty($prazo)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect("/ops/editar?id={$id}");
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Obter OP original para verificar mudanças de vinculo de pedido
            $opOriginal = Database::fetch(
                "SELECT pedido_venda_id FROM ordens_producao WHERE id = :id",
                ['id' => $id]
            );

            // 1. Atualizar OP
            $stmt = $db->prepare(
                "UPDATE ordens_producao 
                 SET pedido_venda_id = :pedido_venda_id, produto_modelo_id = :produto_modelo_id, 
                     oficina_faccao_id = :oficina_faccao_id, quantidade = :quantidade, prazo = :prazo, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id"
            );
            $stmt->execute([
                'pedido_venda_id' => $pedido_venda_id,
                'produto_modelo_id' => $produto_modelo_id,
                'oficina_faccao_id' => $oficina_faccao_id,
                'quantidade' => $quantidade,
                'prazo' => $prazo,
                'status' => $status,
                'tenant_id' => $tenantId,
                'id' => $id
            ]);

            // Se mudou o pedido de venda, atualizar os status do pedido
            if ($opOriginal && $opOriginal['pedido_venda_id'] != $pedido_venda_id) {
                // Voltar o antigo para pendente
                if ($opOriginal['pedido_venda_id']) {
                    $db->prepare("UPDATE pedidos_venda SET status = 'pendente' WHERE id = :pedido_id")
                       ->execute(['pedido_id' => $opOriginal['pedido_venda_id']]);
                }
                // Definir o novo para em produção
                if ($pedido_venda_id) {
                    $db->prepare("UPDATE pedidos_venda SET status = 'em produção' WHERE id = :pedido_id")
                       ->execute(['pedido_id' => $pedido_venda_id]);
                }
            }

            // Se OP foi concluída, e existe pedido vinculado, atualizar pedido para "entregue"
            if ($status === 'concluída' && $pedido_venda_id) {
                $db->prepare("UPDATE pedidos_venda SET status = 'entregue' WHERE id = :pedido_id")
                   ->execute(['pedido_id' => $pedido_venda_id]);
            }

            $db->commit();
            $this->setFlash('success', 'Ordem de Produção atualizada.');
            $this->redirect('/ops');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/ops/editar?id={$id}");
        }
    }

    /**
     * Excluir OP.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $op = Database::fetch(
                "SELECT pedido_venda_id FROM ordens_producao WHERE id = :id AND tenant_id = :tenant_id",
                ['id' => $id, 'tenant_id' => $tenantId]
            );

            if ($op) {
                // Deletar
                $db->prepare("DELETE FROM ordens_producao WHERE id = :id AND tenant_id = :tenant_id")
                   ->execute(['id' => $id, 'tenant_id' => $tenantId]);

                // Se tinha pedido de venda vinculado, voltar status para pendente
                if ($op['pedido_venda_id']) {
                    $db->prepare("UPDATE pedidos_venda SET status = 'pendente' WHERE id = :pedido_id")
                       ->execute(['pedido_id' => $op['pedido_venda_id']]);
                }

                $db->commit();
                $this->setFlash('success', 'OP excluída com sucesso.');
            } else {
                $db->rollBack();
                $this->setFlash('error', 'OP não encontrada.');
            }
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao excluir OP.');
        }

        $this->redirect('/ops');
    }
}
