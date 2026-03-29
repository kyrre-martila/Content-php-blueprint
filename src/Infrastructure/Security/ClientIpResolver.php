<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Http\Request;

final class ClientIpResolver
{
    /** @var array<string, true> */
    private readonly array $trustedProxies;

    /**
     * @param array<int, string> $trustedProxies
     */
    public function __construct(array $trustedProxies = [])
    {
        $normalized = [];

        foreach ($trustedProxies as $proxyIp) {
            if (!is_string($proxyIp)) {
                continue;
            }

            $candidate = trim($proxyIp);

            if ($candidate === '' || filter_var($candidate, FILTER_VALIDATE_IP) === false) {
                continue;
            }

            $normalized[$candidate] = true;
        }

        $this->trustedProxies = $normalized;
    }

    public function resolve(Request $request): string
    {
        $server = $request->serverParams();
        $remoteAddress = $this->resolveRemoteAddress($server['REMOTE_ADDR'] ?? null);

        if (!$this->isTrustedProxy($remoteAddress)) {
            return $remoteAddress;
        }

        $forwardedFor = $server['HTTP_X_FORWARDED_FOR'] ?? null;

        if (!is_string($forwardedFor) || trim($forwardedFor) === '') {
            return $remoteAddress;
        }

        $parts = explode(',', $forwardedFor);
        $firstIp = trim($parts[0] ?? '');

        if ($firstIp === '') {
            return $remoteAddress;
        }

        return filter_var($firstIp, FILTER_VALIDATE_IP) !== false
            ? $firstIp
            : $remoteAddress;
    }

    private function isTrustedProxy(string $remoteAddress): bool
    {
        return isset($this->trustedProxies[$remoteAddress]);
    }

    private function resolveRemoteAddress(mixed $remoteAddress): string
    {
        if (!is_string($remoteAddress)) {
            return 'unknown';
        }

        $candidate = trim($remoteAddress);

        return $candidate !== '' ? $candidate : 'unknown';
    }
}
