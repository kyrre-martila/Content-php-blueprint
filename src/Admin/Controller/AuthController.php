<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\Auth\LoginUser;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Security\LoginRateLimiter;
use App\Infrastructure\View\TemplateRenderer;

final class AuthController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly LoginUser $loginUser,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
        private readonly LoginRateLimiter $loginRateLimiter
    ) {
    }

    public function showLogin(Request $request): Response
    {
        if ($this->authSession->isAuthenticated()) {
            return Response::redirect('/admin');
        }

        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/login.php',
            [
                'request' => $request,
                'error' => $this->session->pullFlash('auth_error'),
            ]
        );

        return Response::html($html);
    }

    public function login(Request $request): Response
    {
        $ipAddress = $this->resolveIpAddress($request);

        if ($this->loginRateLimiter->isBlocked($ipAddress)) {
            $html = $this->templateRenderer->render(
                dirname(__DIR__, 3) . '/templates/errors/429.php',
                ['request' => $request]
            );

            return Response::html($html, 429);
        }

        $post = $request->postParams();
        $email = is_string($post['email'] ?? null) ? $post['email'] : '';
        $password = is_string($post['password'] ?? null) ? $post['password'] : '';

        if (!$this->loginUser->execute($email, $password)) {
            $this->loginRateLimiter->recordAttempt($ipAddress);
            $this->session->flash('auth_error', 'Invalid credentials. Please try again.');

            return Response::redirect('/admin/login', 302);
        }

        $this->loginRateLimiter->reset($ipAddress);

        return Response::redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        $this->authSession->logout();

        return Response::redirect('/admin/login');
    }

    private function resolveIpAddress(Request $request): string
    {
        $server = $request->serverParams();

        $forwarded = $server['HTTP_X_FORWARDED_FOR'] ?? null;

        if (is_string($forwarded) && trim($forwarded) !== '') {
            $parts = explode(',', $forwarded);
            $firstIp = trim($parts[0] ?? '');

            if ($firstIp !== '') {
                return $firstIp;
            }
        }

        $remoteAddress = $server['REMOTE_ADDR'] ?? null;

        return is_string($remoteAddress) && trim($remoteAddress) !== ''
            ? $remoteAddress
            : 'unknown';
    }
}
