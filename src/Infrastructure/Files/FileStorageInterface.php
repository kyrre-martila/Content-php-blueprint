<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

interface FileStorageInterface
{
    public function write(string $storagePath, string $contents): void;

    public function read(string $storagePath): string;

    public function exists(string $storagePath): bool;

    public function delete(string $storagePath): void;

    public function absolutePath(string $storagePath): string;
}
