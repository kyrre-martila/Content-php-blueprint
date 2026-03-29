<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Database\Connection;

final class LoginRateLimiter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly int $maxAttempts,
        private readonly int $windowMinutes
    ) {
    }

    public function isBlocked(string $ipAddress): bool
    {
        $ipKey = $this->ipKey($ipAddress);
        $attemptState = $this->loadAttemptState($ipKey);

        return $attemptState['attemptCount'] >= $this->maxAttempts;
    }

    public function recordAttempt(string $ipAddress): void
    {
        $ipKey = $this->ipKey($ipAddress);

        $this->connection->transaction(function (Connection $connection) use ($ipKey): void {
            $row = $connection->fetchOne(
                'SELECT id, attempt_count, window_start
                 FROM login_attempts
                 WHERE ip_address = :ip_address
                 LIMIT 1
                 FOR UPDATE',
                ['ip_address' => $ipKey]
            );

            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            if ($row === null) {
                $connection->execute(
                    'INSERT INTO login_attempts (ip_address, attempt_count, window_start, last_attempt_at)
                     VALUES (:ip_address, :attempt_count, :window_start, :last_attempt_at)',
                    [
                        'ip_address' => $ipKey,
                        'attempt_count' => 1,
                        'window_start' => $now->format('Y-m-d H:i:s'),
                        'last_attempt_at' => $now->format('Y-m-d H:i:s'),
                    ]
                );

                return;
            }

            $windowStart = $this->parseTimestamp($row['window_start'] ?? null);
            $attemptCount = $this->toPositiveInt($row['attempt_count'] ?? null);
            $windowExpired = $windowStart === null || $this->isOutsideCurrentWindow($windowStart, $now);

            if ($windowExpired) {
                $attemptCount = 0;
                $windowStart = $now;
            }

            $connection->execute(
                'UPDATE login_attempts
                 SET attempt_count = :attempt_count,
                     window_start = :window_start,
                     last_attempt_at = :last_attempt_at
                 WHERE id = :id',
                [
                    'id' => $row['id'],
                    'attempt_count' => $attemptCount + 1,
                    'window_start' => ($windowStart ?? $now)->format('Y-m-d H:i:s'),
                    'last_attempt_at' => $now->format('Y-m-d H:i:s'),
                ]
            );
        });
    }

    public function reset(string $ipAddress): void
    {
        $this->connection->execute(
            'DELETE FROM login_attempts WHERE ip_address = :ip_address',
            ['ip_address' => $this->ipKey($ipAddress)]
        );
    }

    /**
     * @return array{attemptCount: int, windowStart: ?\DateTimeImmutable}
     */
    private function loadAttemptState(string $ipAddress): array
    {
        return $this->connection->transaction(function (Connection $connection) use ($ipAddress): array {
            $row = $connection->fetchOne(
                'SELECT id, attempt_count, window_start
                 FROM login_attempts
                 WHERE ip_address = :ip_address
                 LIMIT 1
                 FOR UPDATE',
                ['ip_address' => $ipAddress]
            );

            if ($row === null) {
                return ['attemptCount' => 0, 'windowStart' => null];
            }

            $windowStart = $this->parseTimestamp($row['window_start'] ?? null);
            $attemptCount = $this->toPositiveInt($row['attempt_count'] ?? null);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            // Row-level lock keeps read/reset/write atomic so concurrent requests cannot perform duplicate window resets.
            if ($windowStart === null || $this->isOutsideCurrentWindow($windowStart, $now)) {
                $connection->execute(
                    'UPDATE login_attempts
                     SET attempt_count = 0,
                         window_start = :window_start
                     WHERE id = :id',
                    [
                        'id' => $row['id'],
                        'window_start' => $now->format('Y-m-d H:i:s'),
                    ]
                );

                return ['attemptCount' => 0, 'windowStart' => $now];
            }

            return ['attemptCount' => $attemptCount, 'windowStart' => $windowStart];
        });
    }

    private function parseTimestamp(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }

    private function isOutsideCurrentWindow(\DateTimeImmutable $windowStart, \DateTimeImmutable $now): bool
    {
        $windowSeconds = max($this->windowMinutes, 1) * 60;

        return ($windowStart->getTimestamp() + $windowSeconds) < $now->getTimestamp();
    }

    private function toPositiveInt(mixed $value): int
    {
        return is_int($value) && $value > 0 ? $value : 0;
    }

    private function ipKey(string $ipAddress): string
    {
        return trim($ipAddress) !== '' ? $ipAddress : 'unknown';
    }
}
