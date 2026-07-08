<?php

namespace App\Middlewares;

class SuperadminMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Se for superadmin (mesmo que esteja em impersonation, ele pode acessar as áreas do superadmin)
        // Mas se estiver em impersonation, geralmente ele quer voltar ao painel de superadmin primeiro.
        if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === true) {
            return true;
        }

        // Acesso negado
        http_response_code(403);
        echo "<h1>403 - Acesso Proibido</h1><p>Esta área é exclusiva para o Superadmin do sistema.</p>";
        return false;
    }
}
