<?php

declare(strict_types=1);

namespace App\Http;

use Closure;
use InvalidArgumentException;
use RuntimeException;

final class Route
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly Closure $handler
    ) {
    }

    /**
     * @param callable(Request): Response $handler
     */
    public static function create(string $method, string $path, callable $handler): self
    {
        return new self(
            self::normalizeMethod($method),
            self::normalizePath($path),
            Closure::fromCallable($handler)
        );
    }

    public function matches(string $method, string $path): bool
    {
        return $this->method === self::normalizeMethod($method)
            && $this->path === self::normalizePath($path);
    }

    public function run(Request $request): Response
    {
        $response = ($this->handler)($request);

        if (!$response instanceof Response) {
            throw new RuntimeException('Route handlers must return a Response instance.');
        }

        return $response;
    }

    private static function normalizeMethod(string $method): string
    {
        return strtoupper(trim($method));
    }

    private static function normalizePath(string $path): string
    {
        $normalizedPath = trim($path);

        if ($normalizedPath === '') {
            return '/';
        }

        if (!str_starts_with($normalizedPath, '/')) {
            $normalizedPath = '/' . $normalizedPath;
        }

        $normalizedPath = preg_replace('#/+#', '/', $normalizedPath) ?? $normalizedPath;

        if (strlen($normalizedPath) > 1) {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        if ($normalizedPath === '') {
            throw new InvalidArgumentException('Route path cannot be empty.');
        }

        return $normalizedPath;
    }
}
