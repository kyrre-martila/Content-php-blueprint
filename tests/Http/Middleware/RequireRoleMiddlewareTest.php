<?php

declare(strict_types=1);

use App\Domain\Auth\Role;
use App\Http\Middleware\RequireRoleMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;

it('redirects to login when no authenticated session exists', function (): void {
    $session = new SessionManager(['name' => 'require_role_guest_session']);
    $authSession = new AuthSession($session);
    $middleware = new RequireRoleMiddleware($authSession, Role::admin(), Role::superadmin());

    $request = new Request('GET', '/admin', [], [], [], [], []);

    $response = $middleware($request, static fn (Request $nextRequest): Response => Response::html('ok'));

    expect($response->status())->toBe(302)
        ->and($response->header('Location'))->toBe('/admin/login');
});

it('returns 403 when authenticated user is missing required role', function (): void {
    $session = new SessionManager(['name' => 'require_role_editor_session']);
    $session->set('auth.user', [
        'id' => 10,
        'email' => 'editor@example.com',
        'display_name' => 'Editor',
        'role' => Role::editor()->value(),
    ]);

    $authSession = new AuthSession($session);
    $middleware = new RequireRoleMiddleware($authSession, Role::admin(), Role::superadmin());

    $request = new Request('GET', '/admin', [], [], [], [], []);

    $response = $middleware($request, static fn (Request $nextRequest): Response => Response::html('ok'));

    expect($response->status())->toBe(403);
});

it('allows authenticated users with an allowed role', function (): void {
    $session = new SessionManager(['name' => 'require_role_superadmin_session']);
    $session->set('auth.user', [
        'id' => 1,
        'email' => 'superadmin@example.com',
        'display_name' => 'Super Admin',
        'role' => Role::superadmin()->value(),
    ]);

    $authSession = new AuthSession($session);
    $middleware = new RequireRoleMiddleware($authSession, Role::admin(), Role::superadmin());

    $request = new Request('GET', '/admin', [], [], [], [], []);

    $response = $middleware($request, static fn (Request $nextRequest): Response => Response::html('ok'));

    expect($response->status())->toBe(200);
});

it('allows editor when middleware explicitly includes editor role', function (): void {
    $session = new SessionManager(['name' => 'require_role_editor_allowed_session']);
    $session->set('auth.user', [
        'id' => 11,
        'email' => 'editor@example.com',
        'display_name' => 'Editor',
        'role' => Role::editor()->value(),
    ]);

    $authSession = new AuthSession($session);
    $middleware = new RequireRoleMiddleware($authSession, Role::editor(), Role::admin(), Role::superadmin());

    $request = new Request('GET', '/admin/content', [], [], [], [], []);

    $response = $middleware($request, static fn (Request $nextRequest): Response => Response::html('ok'));

    expect($response->status())->toBe(200);
});
