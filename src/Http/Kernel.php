<?php

declare(strict_types=1);

namespace App\Http;

final class Kernel
{
    public function handle(Request $request): Response
    {
        $body = sprintf(
            '<h1>Content PHP Blueprint</h1><p>Bootstrapped successfully.</p><p>%s %s</p>',
            htmlspecialchars($request->method(), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($request->uri(), ENT_QUOTES, 'UTF-8')
        );

        return new Response($body);
    }
}
