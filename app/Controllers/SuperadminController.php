<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class SuperadminController extends Controller
{
    /**
     * Dashboard do Superadmin.
     */
    public function dashboard(): void
    {
        // Se estiver em impersonation, o superadmin visualiza as páginas operacionais.
        // Se veio para cá direto, listamos tenants, logs e resumo financeiro.
        $tenants = [];
        $logs = [];
        $financeiro = [];
        $totalReceitas = 0;
        $totalPendente = 0;
        $totalClientes = 0;

        try {
            $tenants = Database::fetchAll("SELECT * FROM tenants ORDER BY id DESC");
            
            $logs = Database::fetchAll(
                "SELECT l.*, t.nome as tenant_nome 
                 FROM logs_auditoria l 
                 JOIN tenants t ON l.tenant_id = t.id 
                 ORDER BY l.id DESC LIMIT 15"
            );

            $financeiro = Database::fetchAll(
                "SELECT tf.*, t.nome as tenant_nome 
                 FROM tenants_financeiro tf 
                 JOIN tenants t ON tf.tenant_id = t.id 
                 ORDER BY tf.id DESC LIMIT 15"
            );

            $totalReceitas = Database::fetch(
                "SELECT SUM(valor) as total FROM tenants_financeiro WHERE status_pagamento = 'pago'"
            )['total'] ?? 0;

            $totalPendente = Database::fetch(
                "SELECT SUM(valor) as total FROM tenants_financeiro WHERE status_pagamento = 'pendente'"
            )['total'] ?? 0;

            $totalClientes = count($tenants);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Aviso: Banco de dados inacessível. Por favor, certifique-se de que o MySQL está rodando e configurado corretamente no arquivo .env.');
        }

        $this->render('superadmin/dashboard', [
            'tenants' => $tenants,
            'logs' => $logs,
            'financeiro' => $financeiro,
            'totalReceitas' => $totalReceitas,
            'totalPendente' => $totalPendente,
            'totalClientes' => $totalClientes
        ]);
    }

    /**
     * Tela de novo tenant.
     */
    public function newTenant(): void
    {
        $this->render('superadmin/tenant_form', [
            'tenant' => null,
            'action' => '/superadmin/tenants/novo',
            'title' => 'Criar Nova Empresa Cliente'
        ]);
    }

    /**
     * Criação de novo tenant e seu admin inicial.
     */
    public function createTenant(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $plano = trim($_POST['plano'] ?? 'Básico');
        $mensalidade = (float)($_POST['mensalidade'] ?? 0.00);
        $status_pagamento = $_POST['status_pagamento'] ?? 'em dia';

        // Dados do Admin
        $admin_nome = trim($_POST['admin_nome'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');
        $admin_senha = $_POST['admin_senha'] ?? '';

        if (empty($nome) || empty($cnpj) || empty($admin_nome) || empty($admin_email) || empty($admin_senha)) {
            $this->setFlash('error', 'Todos os campos obrigatórios devem ser preenchidos.');
            $this->redirect('/superadmin/tenants/novo');
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Inserir Tenant
            $stmt = $db->prepare(
                "INSERT INTO tenants (nome, cnpj, plano, mensalidade, status_pagamento) 
                 VALUES (:nome, :cnpj, :plano, :mensalidade, :status_pagamento)"
            );
            $stmt->execute([
                'nome' => $nome,
                'cnpj' => $cnpj,
                'plano' => $plano,
                'mensalidade' => $mensalidade,
                'status_pagamento' => $status_pagamento
            ]);
            $tenantId = $db->lastInsertId();

            // 2. Inserir Usuário Admin do Tenant
            $senhaHash = password_hash($admin_senha, PASSWORD_DEFAULT);
            $stmtUser = $db->prepare(
                "INSERT INTO users (tenant_id, nome, email, senha, role, status) 
                 VALUES (:tenant_id, :nome, :email, :senha, 'admin', 'ativo')"
            );
            $stmtUser->execute([
                'tenant_id' => $tenantId,
                'nome' => $admin_nome,
                'email' => $admin_email,
                'senha' => $senhaHash
            ]);

            // 3. Gerar Cobrança Inicial (Mês Atual)
            $stmtFinanceiro = $db->prepare(
                "INSERT INTO tenants_financeiro (tenant_id, valor, mes_referencia, status_pagamento) 
                 VALUES (:tenant_id, :valor, :mes_referencia, 'pendente')"
            );
            $stmtFinanceiro->execute([
                'tenant_id' => $tenantId,
                'valor' => $mensalidade,
                'mes_referencia' => date('Y-m')
            ]);

            $db->commit();
            $this->setFlash('success', "Empresa '{$nome}' e admin criados com sucesso.");
            $this->redirect('/superadmin');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $this->setFlash('error', 'Erro ao criar cliente: ' . $e->getMessage());
            $this->redirect('/superadmin/tenants/novo');
        }
    }

    /**
     * Tela de edição de tenant.
     */
    public function editTenant(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $tenant = Database::fetch("SELECT * FROM tenants WHERE id = :id", ['id' => $id]);

        if (!$tenant) {
            $this->setFlash('error', 'Empresa não encontrada.');
            $this->redirect('/superadmin');
        }

        $this->render('superadmin/tenant_form', [
            'tenant' => $tenant,
            'action' => "/superadmin/tenants/editar?id={$id}",
            'title' => "Editar Empresa: {$tenant['nome']}"
        ]);
    }

    /**
     * Atualização do tenant.
     */
    public function updateTenant(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $plano = trim($_POST['plano'] ?? 'Básico');
        $mensalidade = (float)($_POST['mensalidade'] ?? 0.00);
        $status_pagamento = $_POST['status_pagamento'] ?? 'em dia';

        if (empty($nome) || empty($cnpj)) {
            $this->setFlash('error', 'Nome e CNPJ são campos obrigatórios.');
            $this->redirect("/superadmin/tenants/editar?id={$id}");
        }

        try {
            Database::query(
                "UPDATE tenants 
                 SET nome = :nome, cnpj = :cnpj, plano = :plano, mensalidade = :mensalidade, status_pagamento = :status_pagamento 
                 WHERE id = :id",
                [
                    'nome' => $nome,
                    'cnpj' => $cnpj,
                    'plano' => $plano,
                    'mensalidade' => $mensalidade,
                    'status_pagamento' => $status_pagamento,
                    'id' => $id
                ]
            );

            $this->setFlash('success', 'Cadastro atualizado com sucesso.');
            $this->redirect('/superadmin');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/superadmin/tenants/editar?id={$id}");
        }
    }

    /**
     * Impersonation: Superadmin acessa como cliente.
     */
    public function impersonate(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = (int)($_GET['id'] ?? 0);
        $tenant = Database::fetch("SELECT * FROM tenants WHERE id = :id", ['id' => $tenantId]);

        if (!$tenant) {
            $this->setFlash('error', 'Empresa cliente não encontrada.');
            $this->redirect('/superadmin');
        }

        // Buscar um usuário administrador do tenant
        $user = Database::fetch(
            "SELECT * FROM users WHERE tenant_id = :tenant_id AND role = 'admin' LIMIT 1",
            ['tenant_id' => $tenantId]
        );

        // Guardar estado original do Superadmin para poder retornar
        $_SESSION['superadmin_email'] = $_SESSION['email'];
        $_SESSION['impersonate'] = true;

        // Sobrescrever variáveis operacionais na sessão
        $_SESSION['user_id'] = $user['id'] ?? 9999; // Fallback caso não tenha usuário ativo
        $_SESSION['tenant_id'] = $tenant['id'];
        $_SESSION['tenant_nome'] = $tenant['nome'];
        $_SESSION['nome'] = $user['nome'] ?? 'Acesso Impersonate';
        $_SESSION['email'] = $user['email'] ?? 'impersonate@system.com';
        $_SESSION['role'] = 'admin';
        $_SESSION['status_pagamento'] = $tenant['status_pagamento'];

        // Registrar no log de auditoria
        try {
            Database::query(
                "INSERT INTO logs_auditoria (superadmin_email, tenant_id, acao) 
                 VALUES (:superadmin, :tenant_id, 'Início de Impersonation (Acesso como Cliente)')",
                [
                    'superadmin' => $_SESSION['superadmin_email'],
                    'tenant_id' => $tenantId
                ]
            );
        } catch (\Exception $e) {
            // Ignorar falha no log para não quebrar fluxo, mas registrar se possível
        }

        $this->redirect('/dashboard');
    }

    /**
     * Encerra impersonação e volta a ser Superadmin normal.
     */
    public function stopImpersonate(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['impersonate']) || $_SESSION['impersonate'] !== true) {
            $this->redirect('/dashboard');
        }

        $superadminEmail = $_SESSION['superadmin_email'];
        $tenantId = $_SESSION['tenant_id'];

        // Registrar encerramento no log de auditoria
        try {
            Database::query(
                "INSERT INTO logs_auditoria (superadmin_email, tenant_id, acao) 
                 VALUES (:superadmin, :tenant_id, 'Fim de Impersonation')",
                [
                    'superadmin' => $superadminEmail,
                    'tenant_id' => $tenantId
                ]
            );
        } catch (\Exception $e) {
        }

        // Limpar variáveis operacionais e restaurar as do Superadmin
        unset($_SESSION['impersonate'], $_SESSION['tenant_id'], $_SESSION['tenant_nome'], $_SESSION['user_id'], $_SESSION['role'], $_SESSION['status_pagamento']);
        
        $_SESSION['is_superadmin'] = true;
        $_SESSION['nome'] = 'Superadmin';
        $_SESSION['email'] = $superadminEmail;

        $this->setFlash('success', 'Você retornou ao modo Superadmin.');
        $this->redirect('/superadmin');
    }

    /**
     * Tela financeira do Superadmin.
     */
    public function financeiro(): void
    {
        $tenants = [];
        $historico = [];

        try {
            $tenants = Database::fetchAll("SELECT * FROM tenants ORDER BY nome ASC");
            
            $historico = Database::fetchAll(
                "SELECT tf.*, t.nome as tenant_nome 
                 FROM tenants_financeiro tf 
                 JOIN tenants t ON tf.tenant_id = t.id 
                 ORDER BY tf.mes_referencia DESC, tf.id DESC"
            );
        } catch (\Exception $e) {
            $this->setFlash('error', 'Aviso: Banco de dados inacessível. Certifique-se de que o MySQL está rodando.');
        }

        $this->render('superadmin/financeiro', [
            'tenants' => $tenants,
            'historico' => $historico
        ]);
    }

    /**
     * Registra pagamento de cobrança de mensalidade.
     */
    public function registrarPagamento(): void
    {
        $id = (int)($_POST['cobranca_id'] ?? 0);
        $status = $_POST['status'] ?? 'pago';

        try {
            Database::query(
                "UPDATE tenants_financeiro SET status_pagamento = :status WHERE id = :id",
                ['status' => $status, 'id' => $id]
            );

            // Se mudou para pago, pode-se reativar a empresa se ela estava atrasada
            $cobranca = Database::fetch("SELECT tenant_id FROM tenants_financeiro WHERE id = :id", ['id' => $id]);
            if ($cobranca && $status === 'pago') {
                Database::query(
                    "UPDATE tenants SET status_pagamento = 'em dia' WHERE id = :tenant_id",
                    ['tenant_id' => $cobranca['tenant_id']]
                );
            }

            $this->setFlash('success', 'Status de pagamento atualizado.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao atualizar pagamento: ' . $e->getMessage());
        }

        $this->redirect('/superadmin/financeiro');
    }
}
