<?php

namespace App\Middlewares;

class AdminMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Permitir se for superadmin, admin comum, ou se o superadmin estiver em impersonação
        if (isset($_SESSION['is_superadmin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
            return true;
        }

        // Acesso negado
        http_response_code(403);
        echo "<h1>403 - Acesso Proibido</h1><p>Esta área requer privilégios de Administrador.</p><p><a href='/dashboard'>Voltar ao Painel</a></p>";
        return false;
    }
}
