<?php

declare(strict_types=1);

namespace App\Domain\Logging;

interface LoggerInterface
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function info(string $message, array $context = [], string $channel = 'application'): void;

    /**
     * @param array<string, scalar|null> $context
     */
    public function warning(string $message, array $context = [], string $channel = 'application'): void;

    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $message, array $context = [], string $channel = 'errors'): void;

    /**
     * @param array<string, scalar|null> $context
     */
    public function debug(string $message, array $context = [], string $channel = 'application'): void;
}
