<?php

declare(strict_types=1);

use App\Application\Files\FileUploadService;
use App\Application\Files\UploadedFileInput;
use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Infrastructure\Files\FileStorageInterface;

it('rejects invalid uploaded metadata when size does not match contents', function (): void {
    $repository = new class() implements FileRepositoryInterface {
        public function save(FileAsset $fileAsset): FileAsset { return $fileAsset; }
        public function findById(int $id): ?FileAsset { return null; }
        public function findBySlug(string $slug): ?FileAsset { return null; }
    };

    $storage = new class() implements FileStorageInterface {
        public function write(string $storagePath, string $contents): void {}
        public function read(string $storagePath): string { return ''; }
        public function exists(string $storagePath): bool { return false; }
        public function absolutePath(string $storagePath): string { return $storagePath; }
    };

    $service = new FileUploadService($repository, $storage);

    $service->upload(new UploadedFileInput(
        originalName: 'document.pdf',
        mimeType: 'application/pdf',
        extension: 'pdf',
        sizeBytes: 999,
        contents: 'actual-bytes',
        visibility: FileVisibility::Private,
        uploadedByUserId: 1
    ));
})->throws(InvalidArgumentException::class, 'Size bytes does not match uploaded content length.');
