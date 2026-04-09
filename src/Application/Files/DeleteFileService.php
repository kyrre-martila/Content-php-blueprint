<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Infrastructure\Files\FileStorageInterface;

final class DeleteFileService
{
    public function __construct(
        private readonly FileRepositoryInterface $fileRepository,
        private readonly FileStorageInterface $fileStorage
    ) {
    }

    public function deleteById(int $fileId): bool
    {
        $file = $this->fileRepository->findById($fileId);

        if ($file === null) {
            return false;
        }

        $this->fileStorage->delete($file->storagePath());
        $this->fileRepository->delete($file);

        return true;
    }
}
