<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

final class MiddlewareStackBuilder
{
    /**
     * @param callable(Request): Response $controller
     * @param list<callable(Request, callable(Request): Response): Response> $middleware
     * @return callable(Request): Response
     */
    public function wrap(callable $controller, array $middleware): callable
    {
        $pipeline = $controller;

        foreach (array_reverse($middleware) as $layer) {
            $next = $pipeline;
            $pipeline = static fn (Request $request): Response => $layer($request, $next);
        }

        return $pipeline;
    }
}
