<?php

declare(strict_types=1);

namespace Ploofoodle\Core;

use RuntimeException;

final class Router
{
    /** @var array<string,array<string,array{0:string,1:string}>> */
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $request = new Request();
        $response = new Response();

        $method = $request->method();
        $path = rtrim($request->path(), '/') ?: '/';

        $handler = $this->routes[$method][$path] ?? null;
        if (!is_array($handler)) {
            $response->json(['success' => false, 'error' => 'Not found'], 404);
            return;
        }

        try {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException('Controller class not found: ' . $class);
            }

            $controller = new $class($request, $response);
            if (!method_exists($controller, $action)) {
                throw new RuntimeException('Action not found: ' . $class . '::' . $action);
            }

            $controller->{$action}();
        } catch (\Throwable $e) {
            $response->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }
}
