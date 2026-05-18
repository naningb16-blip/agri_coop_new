<?php
class Router {
    private array $routes = [];

    public function get(string $path, string $controller, string $method): void {
        $this->routes['GET'][$path] = [$controller, $method];
    }

    public function post(string $path, string $controller, string $method): void {
        $this->routes['POST'][$path] = [$controller, $method];
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
        $uri = '/' . trim(str_replace($base, '', $uri), '/');
        if ($uri === '') $uri = '/';

        if (isset($this->routes[$method][$uri])) {
            [$controllerName, $action] = $this->routes[$method][$uri];
            $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $ctrl = new $controllerName();
                $ctrl->$action();
            } else {
                http_response_code(404);
                echo "Controller not found: $controllerName";
            }
        } else {
            http_response_code(404);
            require __DIR__ . '/../app/views/errors/404.php';
        }
    }
}
