<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

$trustedProxies = array_values(array_filter(array_map(
    static fn (string $proxy): string => trim($proxy),
    explode(',', (string) Env::get('TRUSTED_PROXIES', ''))
), static fn (string $proxy): bool => $proxy !== ''));

return [
    'login_rate_limit_attempts' => 5,
    'login_rate_limit_window_minutes' => 10,

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated values from TRUSTED_PROXIES are used to determine which
    | proxy IPs/subnets are trusted to provide X-Forwarded-For. Leave empty
    | to use REMOTE_ADDR directly.
    |
    | Example:
    | TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
    |
    */
    'trusted_proxies' => $trustedProxies,
];
