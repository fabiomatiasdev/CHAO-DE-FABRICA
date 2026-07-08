<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    /**
     * Registra rota GET.
     */
    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->routes['GET'][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Registra rota POST.
     */
    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->routes['POST'][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Despacha a requisição para o controller e método correspondente.
     */
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Normalizar URI (remover barra final se houver, exceto na raiz)
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        // Buscar rota correspondente
        if (!isset($this->routes[$method][$uri])) {
            $this->handleNotFound();
            return;
        }

        $route = $this->routes[$method][$uri];
        $handler = $route['handler'];
        $middlewares = $route['middlewares'];

        // Executar Middlewares
        foreach ($middlewares as $middlewareClass) {
            $middleware = new $middlewareClass();
            if (!$middleware->handle()) {
                // Se o middleware retornar false, a execução é interrompida
                return;
            }
        }

        // Executar Handler: [$controllerClass, $methodName]
        [$controllerClass, $methodName] = $handler;
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (method_exists($controller, $methodName)) {
                $controller->$methodName();
                return;
            }
        }

        $this->handleServerError("Controlador ou método não encontrado.");
    }

    private function handleNotFound(): void
    {
        http_response_code(404);
        echo "<h1>404 - Página Não Encontrada</h1>";
    }

    private function handleServerError(string $message): void
    {
        http_response_code(500);
        echo "<h1>500 - Erro Interno do Servidor</h1>";
        echo "<p>" . htmlspecialchars($message) . "</p>";
    }
}
