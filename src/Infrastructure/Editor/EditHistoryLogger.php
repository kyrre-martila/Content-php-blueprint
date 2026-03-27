<?php

declare(strict_types=1);

namespace App\Infrastructure\Editor;

use DateTimeImmutable;
use RuntimeException;

final class EditHistoryLogger
{
    public function __construct(private readonly string $logFilePath)
    {
    }

    public function logUpdate(?array $user, string $filePath, ?string $hashBefore, string $hashAfter): void
    {
        $record = [
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'actor' => $this->actorIdentity($user),
            'file_path' => $filePath,
            'action' => 'updated',
            'hash_before' => $hashBefore,
            'hash_after' => $hashAfter,
        ];

        $directory = dirname($this->logFilePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create edit log directory: %s', $directory));
        }

        $encoded = json_encode($record, JSON_UNESCAPED_SLASHES);

        if (!is_string($encoded)) {
            throw new RuntimeException('Unable to encode edit history record.');
        }

        $result = file_put_contents($this->logFilePath, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            throw new RuntimeException(sprintf('Unable to append edit history log: %s', $this->logFilePath));
        }
    }

    /**
     * @param array{id:int,email:string,display_name:string,role:string}|null $user
     */
    private function actorIdentity(?array $user): string
    {
        if (is_array($user) && isset($user['email']) && is_string($user['email']) && trim($user['email']) !== '') {
            return $user['email'];
        }

        if (is_array($user) && isset($user['id']) && is_int($user['id'])) {
            return 'user#' . $user['id'];
        }

        return 'unknown';
    }
}
