<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\SessionManager;

final class CsrfMiddleware
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly SessionManager $session)
    {
    }

    /**
     * @param callable(Request): Response $next
     */
    public function __invoke(Request $request, callable $next): Response
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        if ($request->method() === 'POST' && str_starts_with($request->path(), '/admin')) {
            $submittedToken = $request->postParams()['_csrf_token'] ?? null;

            if (!is_string($submittedToken) || !hash_equals($token, $submittedToken)) {
                return Response::html('<h1>CSRF token mismatch</h1>', 419);
            }
        }

        return $next($request->withAddedAttributes(['csrf_token' => $token]));
    }
}
