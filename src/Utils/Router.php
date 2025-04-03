<?php

namespace GardenSensors\Utils;

use Exception;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, string $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        $path = $this->basePath . '/' . trim($path, '/');
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rawurldecode($uri);

        // Run middleware stack
        foreach ($this->middlewares as $middleware) {
            $middleware();
        }

        // Find matching route
        $handler = null;
        $params = [];

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $routeHandler) {
                $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
                $pattern = str_replace('/', '\/', $pattern);
                if (preg_match('/^' . $pattern . '$/', $uri, $matches)) {
                    $handler = $routeHandler;
                    array_shift($matches);
                    $params = $matches;
                    break;
                }
            }
        }

        if ($handler === null) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Not Found',
                'message' => 'The requested resource was not found'
            ]);
            return;
        }

        // Parse handler string
        [$controller, $method] = explode('@', $handler);
        $controllerClass = "GardenSensors\\Controllers\\$controller";

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class '$controllerClass' not found");
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $method)) {
            throw new Exception("Method '$method' not found in controller '$controllerClass'");
        }

        // Call the handler with parameters
        $response = $controller->$method(...$params);

        // Send response
        if (is_array($response) || is_object($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            echo $response;
        }
    }
} 