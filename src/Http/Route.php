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

    /**
     * @return array<string, string>|null
     */
    public function match(string $method, string $path): ?array
    {
        if ($this->method !== self::normalizeMethod($method)) {
            return null;
        }

        $matches = [];
        $result = preg_match($this->toRegex(), self::normalizePath($path), $matches);

        if ($result !== 1) {
            return null;
        }

        $parameters = [];

        foreach ($matches as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $parameters[$key] = $value;
        }

        return $parameters;
    }

    public function run(Request $request): Response
    {
        $response = ($this->handler)($request);

        if (!$response instanceof Response) {
            throw new RuntimeException('Route handlers must return a Response instance.');
        }

        return $response;
    }

    private function toRegex(): string
    {
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $this->path
        ) ?? $this->path;

        return '#^' . $pattern . '$#';
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
