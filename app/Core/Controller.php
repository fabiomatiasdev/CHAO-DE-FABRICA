<?php

namespace App\Core;

abstract class Controller
{
    /**
     * Renderiza uma view e passa dados para ela.
     */
    protected function render(string $view, array $data = []): void
    {
        // Tornar dados disponíveis como variáveis locais
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (file_exists($viewPath)) {
            // Capturar conteúdo da view principal ou incluir direto
            require $viewPath;
        } else {
            http_response_code(500);
            echo "Erro: A view '{$view}' não foi encontrada no caminho '{$viewPath}'.";
        }
    }

    /**
     * Retorna uma resposta JSON.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Redireciona para uma URL específica.
     */
    protected function redirect(string $url): void
    {
        header("Location: " . $url);
        exit;
    }

    /**
     * Define uma mensagem de feedback temporária (flash message).
     */
    protected function setFlash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Recupera e limpa mensagens de feedback temporárias.
     */
    protected function getFlash(string $type): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'][$type])) {
            $msg = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $msg;
        }
        return null;
    }
}
