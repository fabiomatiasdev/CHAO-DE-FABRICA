<?php

namespace App\Middlewares;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Se estiver logado como usuário comum, admin ou superadmin (inclusive sob impersonation)
        if (isset($_SESSION['user_id']) || isset($_SESSION['is_superadmin'])) {
            return true;
        }

        // Caso contrário, redireciona para login
        header('Location: /login');
        return false;
    }
}
