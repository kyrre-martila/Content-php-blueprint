<?php

declare(strict_types=1);

use App\Application\Files\UpdateFileMetadataService;
use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;

it('rejects slug collisions when updating file metadata', function (): void {
    $repository = new class() implements FileRepositoryInterface {
        public function save(FileAsset $fileAsset): FileAsset
        {
            return $fileAsset;
        }

        public function findById(int $id): ?FileAsset
        {
            return null;
        }

        public function findBySlug(string $slug): ?FileAsset
        {
            if ($slug !== 'taken-slug') {
                return null;
            }

            return new FileAsset(
                id: 99,
                originalName: 'existing.pdf',
                storedName: 'existing-aaaa.pdf',
                slug: 'taken-slug',
                mimeType: 'application/pdf',
                extension: 'pdf',
                sizeBytes: 10,
                visibility: FileVisibility::Private,
                storageDisk: 'local',
                storagePath: 'aa/bb/existing-aaaa.pdf',
                checksumSha256: str_repeat('a', 64),
                uploadedByUserId: 1,
                createdAt: new \DateTimeImmutable('2026-04-09 00:00:00'),
                updatedAt: new \DateTimeImmutable('2026-04-09 00:00:00')
            );
        }

        public function findAll(): array
        {
            return [];
        }

        public function delete(FileAsset $fileAsset): void
        {
        }
    };

    $file = new FileAsset(
        id: 1,
        originalName: 'manual.pdf',
        storedName: 'manual-bbbb.pdf',
        slug: 'manual',
        mimeType: 'application/pdf',
        extension: 'pdf',
        sizeBytes: 100,
        visibility: FileVisibility::Private,
        storageDisk: 'local',
        storagePath: 'aa/bb/manual-bbbb.pdf',
        checksumSha256: str_repeat('b', 64),
        uploadedByUserId: 1,
        createdAt: new \DateTimeImmutable('2026-04-09 00:00:00'),
        updatedAt: new \DateTimeImmutable('2026-04-09 00:00:00')
    );

    (new UpdateFileMetadataService($repository))->update($file, 'taken-slug', FileVisibility::Public);
})->throws(InvalidArgumentException::class, 'A file with this slug already exists.');
