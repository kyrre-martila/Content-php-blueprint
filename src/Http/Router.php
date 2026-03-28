<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @param callable(Request): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = Route::create($method, $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            $parameters = $route->match($request->method(), $request->path());

            if ($parameters === null) {
                continue;
            }

            return $route->run($request->withAttributes($parameters));
        }

        return Response::html('<h1>404 Not Found</h1>', 404);
    }
}
