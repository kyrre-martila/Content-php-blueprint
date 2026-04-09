<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\FileVisibility;

final class UploadedFileInput
{
    public function __construct(
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly string $extension,
        public readonly int $sizeBytes,
        public readonly string $contents,
        public readonly FileVisibility $visibility,
        public readonly ?int $uploadedByUserId = null
    ) {
    }
}
