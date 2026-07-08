<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AuthController extends Controller
{
    /**
     * Exibe a tela de login.
     */
    public function showLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Se já estiver logado, redireciona
        if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === true && !isset($_SESSION['impersonate'])) {
            $this->redirect('/superadmin');
        } elseif (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $error = $this->getFlash('error');
        $success = $this->getFlash('success');

        $this->render('auth/login', [
            'error' => $error,
            'success' => $success
        ]);
    }

    /**
     * Processa a requisição de login.
     */
    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Por favor, preencha todos os campos.');
            $this->redirect('/login');
        }

        // 1. Verificar se é Superadmin (credenciais do .env)
        $superadminEmail = $_ENV['SUPERADMIN_EMAIL'] ?? 'superadmin@system.com';
        $superadminHash = $_ENV['SUPERADMIN_PASSWORD_HASH'] ?? '';

        if ($email === $superadminEmail) {
            if (password_verify($password, $superadminHash)) {
                // Registrar sessão de Superadmin
                $_SESSION['is_superadmin'] = true;
                $_SESSION['nome'] = 'Superadmin';
                $_SESSION['email'] = $email;
                
                $this->redirect('/superadmin');
            } else {
                $this->setFlash('error', 'Senha do Superadmin incorreta.');
                $this->redirect('/login');
            }
        }

        // 2. Verificar se é usuário comum ou admin do tenant no banco de dados
        try {
            $user = Database::fetch(
                "SELECT u.*, t.nome as tenant_nome, t.status_pagamento, t.cnpj 
                 FROM users u 
                 JOIN tenants t ON u.tenant_id = t.id 
                 WHERE u.email = :email AND u.status = 'ativo'",
                ['email' => $email]
            );

            if ($user && password_verify($password, $user['senha'])) {
                // Verificar se a empresa está ativa/cancelada
                if ($user['status_pagamento'] === 'cancelado') {
                    $this->setFlash('error', 'Sua empresa está desativada no sistema. Entre em contato com o suporte.');
                    $this->redirect('/login');
                }

                // Registrar sessão do usuário
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['tenant_id'] = $user['tenant_id'];
                $_SESSION['tenant_nome'] = $user['tenant_nome'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['status_pagamento'] = $user['status_pagamento'];

                $this->redirect('/dashboard');
            } else {
                $this->setFlash('error', 'E-mail ou senha incorretos, ou usuário inativo.');
                $this->redirect('/login');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao conectar ao banco de dados: ' . $e->getMessage());
            $this->redirect('/login');
        }
    }

    /**
     * Efetua o logout do usuário.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Se estiver sob impersonate, o logout do superadmin deve primeiro encerrar a impersonação
        if (isset($_SESSION['impersonate']) && $_SESSION['impersonate'] === true) {
            $this->redirect('/superadmin/tenants/stop-impersonate');
        }

        // Destruir todas as variáveis de sessão
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        
        $this->redirect('/login');
    }
}
