<?php
namespace App\Core;

class Router {
    protected $routes = [];

    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }

    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }

    public function resolve($requestUri, $method) {
        $action = $this->routes[$method][$requestUri] ?? null;
        if ($action) {
            [$controller, $method] = $action;
            (new $controller)->$method();
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }
}
