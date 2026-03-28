<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Auth\SessionManager;

final class LoginRateLimiter
{
    private const SESSION_KEY = '_security.login_attempts';

    public function __construct(
        private readonly SessionManager $session,
        private readonly int $maxAttempts,
        private readonly int $windowMinutes
    ) {
    }

    public function isBlocked(string $ipAddress): bool
    {
        $attempts = $this->attemptsForIp($ipAddress);

        return count($attempts) >= $this->maxAttempts;
    }

    public function recordAttempt(string $ipAddress): void
    {
        $allAttempts = $this->allAttempts();
        $ipKey = $this->ipKey($ipAddress);

        $attempts = $this->sanitizeTimestamps($allAttempts[$ipKey] ?? []);
        $attempts = $this->pruneExpiredAttempts($attempts);
        $attempts[] = time();

        $allAttempts[$ipKey] = $attempts;
        $this->session->set(self::SESSION_KEY, $allAttempts);
    }

    public function reset(string $ipAddress): void
    {
        $allAttempts = $this->allAttempts();
        $ipKey = $this->ipKey($ipAddress);

        unset($allAttempts[$ipKey]);

        $this->session->set(self::SESSION_KEY, $allAttempts);
    }

    /**
     * @return list<int>
     */
    private function attemptsForIp(string $ipAddress): array
    {
        $allAttempts = $this->allAttempts();
        $ipKey = $this->ipKey($ipAddress);

        $attempts = $this->sanitizeTimestamps($allAttempts[$ipKey] ?? []);
        $attempts = $this->pruneExpiredAttempts($attempts);

        if ($attempts === []) {
            unset($allAttempts[$ipKey]);
        } else {
            $allAttempts[$ipKey] = $attempts;
        }

        $this->session->set(self::SESSION_KEY, $allAttempts);

        return $attempts;
    }

    /**
     * @param mixed $timestamps
     * @return list<int>
     */
    private function sanitizeTimestamps(mixed $timestamps): array
    {
        if (!is_array($timestamps)) {
            return [];
        }

        $sanitized = [];

        foreach ($timestamps as $timestamp) {
            if (is_int($timestamp)) {
                $sanitized[] = $timestamp;
            }
        }

        return $sanitized;
    }

    /**
     * @param list<int> $attempts
     * @return list<int>
     */
    private function pruneExpiredAttempts(array $attempts): array
    {
        $cutoff = time() - ($this->windowMinutes * 60);

        return array_values(array_filter(
            $attempts,
            static fn (int $attempt): bool => $attempt >= $cutoff
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function allAttempts(): array
    {
        $attempts = $this->session->get(self::SESSION_KEY, []);

        return is_array($attempts) ? $attempts : [];
    }

    private function ipKey(string $ipAddress): string
    {
        return trim($ipAddress) !== '' ? $ipAddress : 'unknown';
    }
}
