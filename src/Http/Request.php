<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     * @param array<string, string> $attributes
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $cookies,
        private readonly array $files,
        private readonly array $server,
        private readonly array $attributes = []
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;
        $method = isset($server['REQUEST_METHOD']) ? (string) $server['REQUEST_METHOD'] : 'GET';
        $uri = isset($server['REQUEST_URI']) ? (string) $server['REQUEST_URI'] : '/';

        return new self(
            strtoupper($method),
            self::normalizePath($uri),
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            $server
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function postParams(): array
    {
        return $this->post;
    }

    /**
     * @return array<string, mixed>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * @return array<string, mixed>
     */
    public function serverParams(): array
    {
        return $this->server;
    }


    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function attribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->post,
            $this->cookies,
            $this->files,
            $this->server,
            $attributes
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function withAddedAttributes(array $attributes): self
    {
        return $this->withAttributes([
            ...$this->attributes,
            ...$attributes,
        ]);
    }

    private static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $normalizedPath = is_string($path) && $path !== '' ? $path : '/';

        if (!str_starts_with($normalizedPath, '/')) {
            $normalizedPath = '/' . $normalizedPath;
        }

        $normalizedPath = preg_replace('#/+#', '/', $normalizedPath) ?? $normalizedPath;

        if (strlen($normalizedPath) > 1) {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        return $normalizedPath === '' ? '/' : $normalizedPath;
    }
}
