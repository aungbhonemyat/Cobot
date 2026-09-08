<?php
namespace App\Core;

class Router
{
    protected array $routes = [];

    public function register(string $method, string $path, $handler): void
    {
        $this->routes[strtolower($method)][rtrim($path, '/')] = $handler;
    }

    public function dispatch(): void
    {
        $path = Request::path();
        $method = Request::method();

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            echo "Not Found";
            return;
        }

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        // handler like Controller@method
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler, 2);
            $class = '\\App\\Controllers\\' . $class;
            if (class_exists($class)) {
                $ctrl = new $class();
                if (method_exists($ctrl, $method)) {
                    $ctrl->{$method}();
                    return;
                }
            }
        }

        http_response_code(500);
        echo "Handler error";
    }
}
