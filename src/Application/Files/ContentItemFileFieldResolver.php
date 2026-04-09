<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Content\ContentItem;
use App\Domain\Files\FileAsset;
use App\Domain\Files\Repository\FileRepositoryInterface;

final class ContentItemFileFieldResolver
{
    public function __construct(private readonly FileRepositoryInterface $files)
    {
    }

    public function resolveField(ContentItem $contentItem, string $fieldName): ?FileAsset
    {
        return $this->resolveStoredValue($contentItem->fieldValue($fieldName));
    }

    public function resolveStoredValue(mixed $storedValue): ?FileAsset
    {
        $id = $this->toFileId($storedValue);

        if ($id === null) {
            return null;
        }

        return $this->files->findById($id);
    }

    public function isLegacyValue(mixed $storedValue): bool
    {
        return is_string($storedValue) && trim($storedValue) !== '' && !ctype_digit(trim($storedValue));
    }

    private function toFileId(mixed $storedValue): ?int
    {
        if (is_int($storedValue)) {
            return $storedValue > 0 ? $storedValue : null;
        }

        if (is_float($storedValue) && floor($storedValue) === $storedValue) {
            $normalized = (int) $storedValue;

            return $normalized > 0 ? $normalized : null;
        }

        if (!is_scalar($storedValue)) {
            return null;
        }

        $normalized = trim((string) $storedValue);

        if ($normalized === '' || !ctype_digit($normalized)) {
            return null;
        }

        $id = (int) $normalized;

        return $id > 0 ? $id : null;
    }
}
