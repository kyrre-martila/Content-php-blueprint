<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Role;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;

final class RequireRoleMiddleware
{
    /**
     * @var list<Role>
     */
    private array $allowedRoles;

    public function __construct(
        private readonly AuthSession $authSession,
        Role ...$allowedRoles
    ) {
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * @param callable(Request): Response $next
     */
    public function __invoke(Request $request, callable $next): Response
    {
        if (!$this->authSession->isAuthenticated()) {
            return Response::redirect('/admin/login');
        }

        $role = $this->authSession->role();

        if ($role === null) {
            return Response::html('Forbidden', 403);
        }

        foreach ($this->allowedRoles as $allowedRole) {
            if ($role->equals($allowedRole)) {
                return $next($request);
            }
        }

        return Response::html('Forbidden', 403);
    }
}
