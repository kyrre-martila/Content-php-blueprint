<?php

declare(strict_types=1);

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Security\LoginRateLimiter;

it('records attempts and blocks only at the configured threshold', function (): void {
    $connection = loginRateLimiterConnection();
    $limiter = new LoginRateLimiter($connection, maxAttempts: 3, windowMinutes: 5);

    $ip = '198.51.100.41';

    expect($limiter->isBlocked($ip))->toBeFalse();

    $limiter->recordAttempt($ip);
    expect(loginAttemptCount($connection, $ip))->toBe(1)
        ->and($limiter->isBlocked($ip))->toBeFalse();

    $limiter->recordAttempt($ip);
    expect(loginAttemptCount($connection, $ip))->toBe(2)
        ->and($limiter->isBlocked($ip))->toBeFalse();

    $limiter->recordAttempt($ip);
    expect(loginAttemptCount($connection, $ip))->toBe(3)
        ->and($limiter->isBlocked($ip))->toBeTrue();
});

it('resets an expired window before counting a new attempt', function (): void {
    $connection = loginRateLimiterConnection();
    $limiter = new LoginRateLimiter($connection, maxAttempts: 3, windowMinutes: 5);

    $ip = '198.51.100.42';
    $expired = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-10 minutes')->format('Y-m-d H:i:s');

    $connection->execute(
        'INSERT INTO login_attempts (ip_address, attempt_count, window_start, last_attempt_at)
         VALUES (:ip_address, :attempt_count, :window_start, :last_attempt_at)',
        [
            'ip_address' => $ip,
            'attempt_count' => 3,
            'window_start' => $expired,
            'last_attempt_at' => $expired,
        ]
    );

    expect($limiter->isBlocked($ip))->toBeFalse();

    $limiter->recordAttempt($ip);

    expect(loginAttemptCount($connection, $ip))->toBe(1)
        ->and($limiter->isBlocked($ip))->toBeFalse();
});

it('reset clears attempts so subsequent checks are unblocked', function (): void {
    $connection = loginRateLimiterConnection();
    $limiter = new LoginRateLimiter($connection, maxAttempts: 2, windowMinutes: 5);

    $ip = '198.51.100.43';
    $limiter->recordAttempt($ip);
    $limiter->recordAttempt($ip);

    expect($limiter->isBlocked($ip))->toBeTrue();

    $limiter->reset($ip);

    expect($limiter->isBlocked($ip))->toBeFalse()
        ->and(loginAttemptCount($connection, $ip))->toBe(0);
});

function loginRateLimiterConnection(): Connection
{
    $pdo = new class ('sqlite::memory:') extends PDO {
        public function __construct(string $dsn)
        {
            parent::__construct($dsn);
        }

        public function prepare(string $query, array $options = []): PDOStatement|false
        {
            $normalized = preg_replace('/\s+FOR\s+UPDATE\s*$/i', '', $query) ?? $query;

            return parent::prepare($normalized, $options);
        }
    };

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(
        'CREATE TABLE login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL UNIQUE,
            attempt_count INTEGER NOT NULL,
            window_start TEXT NOT NULL,
            last_attempt_at TEXT NOT NULL
        )'
    );

    return new Connection($pdo);
}

function loginAttemptCount(Connection $connection, string $ipAddress): int
{
    $row = $connection->fetchOne(
        'SELECT attempt_count FROM login_attempts WHERE ip_address = :ip_address LIMIT 1',
        ['ip_address' => $ipAddress]
    );

    if ($row === null) {
        return 0;
    }

    return (int) ($row['attempt_count'] ?? 0);
}
