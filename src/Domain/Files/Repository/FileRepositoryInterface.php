<?php

declare(strict_types=1);

namespace App\Domain\Files\Repository;

use App\Domain\Files\FileAsset;

interface FileRepositoryInterface
{
    public function save(FileAsset $fileAsset): FileAsset;

    public function findById(int $id): ?FileAsset;

    public function findBySlug(string $slug): ?FileAsset;
}
