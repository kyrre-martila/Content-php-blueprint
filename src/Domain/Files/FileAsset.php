<?php

declare(strict_types=1);

namespace App\Domain\Files;

use DateTimeImmutable;
use InvalidArgumentException;

final class FileAsset
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $originalName,
        private readonly string $storedName,
        private readonly string $slug,
        private readonly string $mimeType,
        private readonly string $extension,
        private readonly int $sizeBytes,
        private readonly FileVisibility $visibility,
        private readonly string $storageDisk,
        private readonly string $storagePath,
        private readonly ?string $checksumSha256,
        private readonly ?int $uploadedByUserId,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        $this->assertInvariants();
    }

    public function id(): ?int { return $this->id; }
    public function originalName(): string { return $this->originalName; }
    public function storedName(): string { return $this->storedName; }
    public function slug(): string { return $this->slug; }
    public function mimeType(): string { return $this->mimeType; }
    public function extension(): string { return $this->extension; }
    public function sizeBytes(): int { return $this->sizeBytes; }
    public function visibility(): FileVisibility { return $this->visibility; }
    public function storageDisk(): string { return $this->storageDisk; }
    public function storagePath(): string { return $this->storagePath; }
    public function checksumSha256(): ?string { return $this->checksumSha256; }
    public function uploadedByUserId(): ?int { return $this->uploadedByUserId; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    private function assertInvariants(): void
    {
        if ($this->id !== null && $this->id < 1) {
            throw new InvalidArgumentException('File ID must be a positive integer when provided.');
        }

        if (trim($this->originalName) === '') {
            throw new InvalidArgumentException('Original file name cannot be empty.');
        }

        if (trim($this->storedName) === '' || str_contains($this->storedName, '/')) {
            throw new InvalidArgumentException('Stored file name must be non-empty and must not contain path separators.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->slug)) {
            throw new InvalidArgumentException('File slug must be lowercase kebab-case.');
        }

        if (!preg_match('/^[a-z0-9][a-z0-9.+-]*\/[a-z0-9][a-z0-9.+-]*$/i', $this->mimeType)) {
            throw new InvalidArgumentException('Mime type is invalid.');
        }

        if (!preg_match('/^[a-z0-9]+$/', $this->extension)) {
            throw new InvalidArgumentException('File extension must be lowercase alphanumeric without dot.');
        }

        if ($this->sizeBytes < 0) {
            throw new InvalidArgumentException('File size must be zero or positive.');
        }

        if (trim($this->storageDisk) === '') {
            throw new InvalidArgumentException('Storage disk cannot be empty.');
        }

        if (trim($this->storagePath) === '' || str_starts_with($this->storagePath, '/')) {
            throw new InvalidArgumentException('Storage path must be a non-empty relative path.');
        }

        if (str_contains($this->storagePath, '..')) {
            throw new InvalidArgumentException('Storage path traversal segments are not allowed.');
        }

        if ($this->checksumSha256 !== null && !preg_match('/^[a-f0-9]{64}$/', $this->checksumSha256)) {
            throw new InvalidArgumentException('Checksum must be a 64-character lowercase SHA-256 hex string.');
        }

        if ($this->uploadedByUserId !== null && $this->uploadedByUserId < 1) {
            throw new InvalidArgumentException('Uploader user ID must be a positive integer when provided.');
        }

        if ($this->updatedAt < $this->createdAt) {
            throw new InvalidArgumentException('Updated at must be greater than or equal to created at.');
        }
    }
}
