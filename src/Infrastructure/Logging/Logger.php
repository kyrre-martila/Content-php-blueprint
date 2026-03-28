<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Domain\Logging\LoggerInterface;
use DateTimeImmutable;
use RuntimeException;

final class Logger implements LoggerInterface
{
    public function __construct(private readonly string $logsDirectory)
    {
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function info(string $message, array $context = [], string $channel = 'application'): void
    {
        $this->log('INFO', $channel, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function warning(string $message, array $context = [], string $channel = 'application'): void
    {
        $this->log('WARNING', $channel, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $message, array $context = [], string $channel = 'errors'): void
    {
        $this->log('ERROR', $channel, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function debug(string $message, array $context = [], string $channel = 'application'): void
    {
        $this->log('DEBUG', $channel, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function log(string $level, string $channel, string $message, array $context = []): void
    {
        $this->ensureLogDirectoryExists();

        $record = [
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'level' => strtoupper($level),
            'channel' => $this->sanitizeChannel($channel),
            'message' => $message,
            'context' => $context,
        ];

        $encodedRecord = json_encode($record, JSON_UNESCAPED_SLASHES);

        if (!is_string($encodedRecord)) {
            throw new RuntimeException('Failed to encode log record as JSON.');
        }

        $logPath = sprintf('%s/%s.log', rtrim($this->logsDirectory, '/'), $record['channel']);
        $result = file_put_contents($logPath, $encodedRecord . PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            throw new RuntimeException(sprintf('Unable to write log file: %s', $logPath));
        }
    }

    private function ensureLogDirectoryExists(): void
    {
        if (is_dir($this->logsDirectory)) {
            return;
        }

        if (!mkdir($this->logsDirectory, 0775, true) && !is_dir($this->logsDirectory)) {
            throw new RuntimeException(sprintf('Unable to create logs directory: %s', $this->logsDirectory));
        }
    }

    private function sanitizeChannel(string $channel): string
    {
        $normalizedChannel = preg_replace('/[^a-z0-9_-]/i', '', strtolower($channel));

        if (!is_string($normalizedChannel) || $normalizedChannel === '') {
            return 'application';
        }

        return $normalizedChannel;
    }
}
