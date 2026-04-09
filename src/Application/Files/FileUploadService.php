<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\FileAsset;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Infrastructure\Files\FileStorageInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class FileUploadService
{
    public function __construct(
        private readonly FileRepositoryInterface $fileRepository,
        private readonly FileStorageInterface $fileStorage,
        private readonly string $storageDisk = 'local'
    ) {
    }

    public function upload(UploadedFileInput $input): FileAsset
    {
        $this->validateInput($input);

        $checksum = hash('sha256', $input->contents);
        $baseSlug = $this->slugify(pathinfo($input->originalName, PATHINFO_FILENAME));
        $storedName = sprintf('%s-%s.%s', $baseSlug, substr($checksum, 0, 12), $input->extension);
        $storagePath = sprintf('%s/%s/%s', substr($checksum, 0, 2), substr($checksum, 2, 2), $storedName);
        $now = new DateTimeImmutable();

        $fileAsset = new FileAsset(
            id: null,
            originalName: $input->originalName,
            storedName: $storedName,
            slug: $baseSlug,
            mimeType: $input->mimeType,
            extension: $input->extension,
            sizeBytes: $input->sizeBytes,
            visibility: $input->visibility,
            storageDisk: $this->storageDisk,
            storagePath: $storagePath,
            checksumSha256: $checksum,
            uploadedByUserId: $input->uploadedByUserId,
            createdAt: $now,
            updatedAt: $now
        );

        $this->fileStorage->write($storagePath, $input->contents);

        return $this->fileRepository->save($fileAsset);
    }

    private function validateInput(UploadedFileInput $input): void
    {
        if (trim($input->originalName) === '') {
            throw new InvalidArgumentException('Original name is required.');
        }

        if (!preg_match('/^[a-z0-9][a-z0-9.+-]*\/[a-z0-9][a-z0-9.+-]*$/i', $input->mimeType)) {
            throw new InvalidArgumentException('Mime type is invalid.');
        }

        if (!preg_match('/^[a-z0-9]+$/', $input->extension)) {
            throw new InvalidArgumentException('Extension must be lowercase alphanumeric without dot.');
        }

        if ($input->sizeBytes < 0) {
            throw new InvalidArgumentException('Size bytes must be zero or greater.');
        }

        if (strlen($input->contents) !== $input->sizeBytes) {
            throw new InvalidArgumentException('Size bytes does not match uploaded content length.');
        }

        if ($input->uploadedByUserId !== null && $input->uploadedByUserId < 1) {
            throw new InvalidArgumentException('Uploaded by user ID must be positive when provided.');
        }
    }

    private function slugify(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        if ($normalized === '') {
            return 'file';
        }

        return $normalized;
    }
}
