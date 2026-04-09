<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class UpdateFileMetadataService
{
    public function __construct(private readonly FileRepositoryInterface $fileRepository)
    {
    }

    public function update(FileAsset $file, string $slug, FileVisibility $visibility): FileAsset
    {
        $normalizedSlug = $this->normalizeSlug($slug);
        $slugCollision = $this->fileRepository->findBySlug($normalizedSlug);

        if ($slugCollision !== null && $slugCollision->id() !== $file->id()) {
            throw new InvalidArgumentException('A file with this slug already exists.');
        }

        $updated = new FileAsset(
            id: $file->id(),
            originalName: $file->originalName(),
            storedName: $file->storedName(),
            slug: $normalizedSlug,
            mimeType: $file->mimeType(),
            extension: $file->extension(),
            sizeBytes: $file->sizeBytes(),
            visibility: $visibility,
            storageDisk: $file->storageDisk(),
            storagePath: $file->storagePath(),
            checksumSha256: $file->checksumSha256(),
            uploadedByUserId: $file->uploadedByUserId(),
            createdAt: $file->createdAt(),
            updatedAt: new DateTimeImmutable()
        );

        return $this->fileRepository->save($updated);
    }

    private function normalizeSlug(string $slug): string
    {
        $normalized = strtolower(trim($slug));

        if ($normalized === '') {
            throw new InvalidArgumentException('Slug is required.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized)) {
            throw new InvalidArgumentException('Slug must be lowercase kebab-case.');
        }

        return $normalized;
    }
}
