<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Http\Request;

final class ClientIpResolver
{
    /** @var array<string, true> */
    private readonly array $trustedProxyIps;

    /** @var array<int, array{network: string, prefix: int}> */
    private readonly array $trustedProxyCidrs;

    /**
     * @param array<int, string> $trustedProxies
     */
    public function __construct(array $trustedProxies = [])
    {
        $trustedIps = [];
        $trustedCidrs = [];

        foreach ($trustedProxies as $proxyIp) {
            if (!is_string($proxyIp)) {
                continue;
            }

            $candidate = trim($proxyIp);

            if ($candidate === '') {
                continue;
            }

            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                $trustedIps[$candidate] = true;
                continue;
            }

            $parsedCidr = $this->parseCidr($candidate);

            if ($parsedCidr !== null) {
                $trustedCidrs[] = $parsedCidr;
            }
        }

        $this->trustedProxyIps = $trustedIps;
        $this->trustedProxyCidrs = $trustedCidrs;
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
        if (isset($this->trustedProxyIps[$remoteAddress])) {
            return true;
        }

        foreach ($this->trustedProxyCidrs as $cidr) {
            if ($this->isIpInCidr($remoteAddress, $cidr['network'], $cidr['prefix'])) {
                return true;
            }
        }

        return false;
    }

    private function parseCidr(string $candidate): ?array
    {
        if (!str_contains($candidate, '/')) {
            return null;
        }

        [$network, $prefix] = array_pad(explode('/', $candidate, 2), 2, '');
        $network = trim($network);
        $prefix = trim($prefix);

        if (filter_var($network, FILTER_VALIDATE_IP) === false || !preg_match('/^\d+$/', $prefix)) {
            return null;
        }

        $maxPrefix = str_contains($network, ':') ? 128 : 32;
        $prefixValue = (int) $prefix;

        if ($prefixValue < 0 || $prefixValue > $maxPrefix) {
            return null;
        }

        return [
            'network' => $network,
            'prefix' => $prefixValue,
        ];
    }

    private function isIpInCidr(string $ipAddress, string $network, int $prefix): bool
    {
        $ipBinary = inet_pton($ipAddress);
        $networkBinary = inet_pton($network);

        if ($ipBinary === false || $networkBinary === false || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($networkBinary, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        $ipByte = ord($ipBinary[$fullBytes]);
        $networkByte = ord($networkBinary[$fullBytes]);

        return ($ipByte & $mask) === ($networkByte & $mask);
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
