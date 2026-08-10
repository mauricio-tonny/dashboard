<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function __construct(private App $app)
    {
    }

    public function get(string $path, array $action): void
    {
        $this->map('GET', $path, $action);
    }

    public function post(string $path, array $action): void
    {
        $this->map('POST', $path, $action);
    }

    public function dispatch(Request $request): Response
    {
        $key = $request->method() . ':' . $request->path();

        if ($request->method() === 'HEAD' && !isset($this->routes[$key])) {
            $key = 'GET:' . $request->path();
        }

        if (!isset($this->routes[$key])) {
            return new Response('Pagina não encontrada.', 404);
        }

        [$controllerClass, $method] = $this->routes[$key];
        $controller = new $controllerClass($this->app);

        return $controller->{$method}($request);
    }

    private function map(string $httpMethod, string $path, array $action): void
    {
        $this->routes[$httpMethod . ':' . $path] = $action;
    }
}
