<?php

namespace App\Routing;

class Router
{
    private array $routes = [];

    public function get(string $uri, string $action) { $this->register('GET', $uri, $action); }
    public function post(string $uri, string $action) { $this->register('POST', $uri, $action); }
    public function put(string $uri, string $action) { $this->register('PUT', $uri, $action); }

    private function register(string $method, string $uri, string $action)
    {
        $this->routes[$method][$uri] = $action;
    }

    public function dispatch(string $uri, string $method)
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes[$method] as $route => $action) {
            $pattern = preg_replace('#\{[^}]+\}#', '([^/]+)', $route);

            if (preg_match("#^{$pattern}$#", $uri, $matches)) {
                array_shift($matches);
                return $this->callAction($action, $matches);
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }

    private function callAction(string $action, array $params)
    {
        [$class, $method] = explode('@', $action);
        $controller = new $class;
        return $controller->$method(...$params);
    }
}
