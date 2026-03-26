<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\Auth\LoginUser;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplateRenderer;

final class AuthController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly LoginUser $loginUser,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session
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
        $post = $request->postParams();
        $email = is_string($post['email'] ?? null) ? $post['email'] : '';
        $password = is_string($post['password'] ?? null) ? $post['password'] : '';

        if (!$this->loginUser->execute($email, $password)) {
            $this->session->flash('auth_error', 'Invalid credentials. Please try again.');

            return Response::redirect('/admin/login', 302);
        }

        return Response::redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        $this->authSession->logout();

        return Response::redirect('/admin/login');
    }
}
