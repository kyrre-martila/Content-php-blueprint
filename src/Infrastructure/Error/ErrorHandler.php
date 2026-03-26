<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

use App\Infrastructure\Logging\Logger;
use ErrorException;
use Throwable;

final class ErrorHandler
{
    public function __construct(
        private readonly bool $debug,
        private readonly Logger $logger
    ) {
    }

    public function register(): void
    {
        set_error_handler([$this, 'handlePhpError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handlePhpError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(Throwable $throwable): void
    {
        $this->logger->error(
            $throwable->getMessage(),
            [
                'exception' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]
        );

        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');

        if ($this->debug) {
            echo sprintf(
                "Unhandled exception: %s\n%s:%d\n\n%s",
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine(),
                $throwable->getTraceAsString()
            );

            return;
        }

        echo 'Internal Server Error';
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if (!is_array($error)) {
            return;
        }

        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $message = isset($error['message']) && is_string($error['message']) ? $error['message'] : 'Fatal error';

        $this->logger->error($message, [
            'type' => (string) ($error['type'] ?? ''),
            'file' => is_string($error['file'] ?? null) ? $error['file'] : '',
            'line' => is_int($error['line'] ?? null) ? (string) $error['line'] : '',
        ]);

        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->debug ? $message : 'Internal Server Error';
    }
}
