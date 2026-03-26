<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query,
        private readonly array $request,
        private readonly array $server
    ) {
    }

    public static function capture(): self
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

        return new self($method, $uri, $_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
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
    public function bodyParams(): array
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function serverParams(): array
    {
        return $this->server;
    }
}
