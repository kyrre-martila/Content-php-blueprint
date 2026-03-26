<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;

final class RequireAuthMiddleware
{
    public function __construct(private readonly AuthSession $authSession)
    {
    }

    /**
     * @param callable(Request): Response $next
     */
    public function __invoke(Request $request, callable $next): Response
    {
        if (!$this->authSession->isAuthenticated()) {
            return Response::redirect('/admin/login');
        }

        return $next($request);
    }
}
