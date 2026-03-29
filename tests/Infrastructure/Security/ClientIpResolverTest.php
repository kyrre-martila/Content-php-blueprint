<?php

declare(strict_types=1);

use App\Http\Request;
use App\Infrastructure\Security\ClientIpResolver;

it('uses remote address by default', function (): void {
    $resolver = new ClientIpResolver();

    $request = requestWithServer([
        'REMOTE_ADDR' => '203.0.113.9',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.4',
    ]);

    expect($resolver->resolve($request))->toBe('203.0.113.9');
});

it('uses forwarded header when request comes from trusted proxy', function (): void {
    $resolver = new ClientIpResolver(['192.0.2.10']);

    $request = requestWithServer([
        'REMOTE_ADDR' => '192.0.2.10',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.4, 192.0.2.10',
    ]);

    expect($resolver->resolve($request))->toBe('198.51.100.4');
});

it('falls back to remote address when forwarded value is invalid', function (): void {
    $resolver = new ClientIpResolver(['192.0.2.10']);

    $request = requestWithServer([
        'REMOTE_ADDR' => '192.0.2.10',
        'HTTP_X_FORWARDED_FOR' => 'not-an-ip, 198.51.100.4',
    ]);

    expect($resolver->resolve($request))->toBe('192.0.2.10');
});

it('falls back to unknown when remote address is missing', function (): void {
    $resolver = new ClientIpResolver(['192.0.2.10']);

    $request = requestWithServer([
        'HTTP_X_FORWARDED_FOR' => '198.51.100.4',
    ]);

    expect($resolver->resolve($request))->toBe('unknown');
});

function requestWithServer(array $server): Request
{
    return new Request('POST', '/admin/login', [], [], [], [], $server);
}
