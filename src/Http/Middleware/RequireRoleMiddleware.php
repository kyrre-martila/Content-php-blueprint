<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;

final class RequireRoleMiddleware
{
    /**
     * @param list<string> $allowedRoles
     */
    public function __construct(
        private readonly AuthSession $authSession,
        private readonly array $allowedRoles,
    ) {
    }

    /**
     * @param callable(Request): Response $next
     */
    public function __invoke(Request $request, callable $next): Response
    {
        if (!$this->authSession->isAuthenticated()) {
            return Response::redirect('/admin/login');
        }

        $user = $this->authSession->user();
        $role = $user['role'] ?? null;

        if (!is_string($role) || !in_array($role, $this->allowedRoles, true)) {
            return Response::html('Forbidden', 403);
        }

        return $next($request);
    }
}
