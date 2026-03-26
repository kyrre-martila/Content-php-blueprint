<?php

declare(strict_types=1);

use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\SessionManager;

it('rejects admin post requests with invalid csrf token', function (): void {
    $session = new SessionManager(['name' => 'csrf_test_session']);
    $session->set('_csrf_token', 'known-token');

    $middleware = new CsrfMiddleware($session);

    $request = new Request(
        'POST',
        '/admin/content/create',
        [],
        ['_csrf_token' => 'invalid'],
        [],
        [],
        []
    );

    $response = $middleware($request, static fn (Request $nextRequest): Response => Response::html('ok'));

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($output)->toContain('CSRF token mismatch');
});

it('allows admin post requests when csrf token is valid', function (): void {
    $session = new SessionManager(['name' => 'csrf_test_session_valid']);
    $session->set('_csrf_token', 'valid-token');

    $middleware = new CsrfMiddleware($session);

    $request = new Request(
        'POST',
        '/admin/content/create',
        [],
        ['_csrf_token' => 'valid-token'],
        [],
        [],
        []
    );

    $response = $middleware($request, static fn (Request $nextRequest): Response => Response::html('ok'));

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($output)->toBe('ok');
});
