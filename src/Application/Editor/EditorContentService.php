<?php

declare(strict_types=1);

namespace App\Application\Editor;

use App\Domain\Content\ContentItem;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Infrastructure\Editor\EditableFieldValidationException;
use App\Infrastructure\Editor\EditableFieldValidator;
use DateTimeImmutable;

final class EditorContentService
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly EditableFieldValidator $editableFieldValidator
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function saveInlineEdit(array $payload): ContentItem
    {
        $validated = $this->editableFieldValidator->validate($payload);
        $contentItem = $this->loadContentItem((int) $validated['content_id']);
        $updatedAt = new DateTimeImmutable();

        if ($validated['type'] === 'content_item') {
            $field = (string) $validated['field'];
            $value = (string) $validated['value'];

            if ($field === 'title') {
                return $this->contentItems->save($contentItem->withTitle($value, $updatedAt));
            }

            return $this->contentItems->save($contentItem->withSeoMetadata(
                $field === 'meta_title' ? $this->normalizeNullableText($value) : $contentItem->metaTitle(),
                $field === 'meta_description' ? $this->normalizeNullableText($value) : $contentItem->metaDescription(),
                $field === 'og_image' ? $this->normalizeNullableText($value) : $contentItem->ogImage(),
                $field === 'canonical_url' ? $this->normalizeNullableText($value) : $contentItem->canonicalUrl(),
                $field === 'noindex' ? $this->toBoolean($value) : $contentItem->noindex(),
                $updatedAt
            ));
        }

        $patternBlocks = $contentItem->patternBlocks();
        $blockIndex = (int) $validated['block_index'];

        if (!isset($patternBlocks[$blockIndex])) {
            throw new EditableFieldValidationException('Pattern block index is invalid.');
        }

        $block = $patternBlocks[$blockIndex];
        $block['data'][(string) $validated['field']] = (string) $validated['value'];
        $patternBlocks[$blockIndex] = $block;

        return $this->contentItems->save($contentItem->withPatternBlocks($patternBlocks, $updatedAt));
    }

    private function loadContentItem(int $contentId): ContentItem
    {
        $contentItem = $this->contentItems->findById($contentId);

        if ($contentItem === null) {
            throw new EditableFieldValidationException('Content item was not found.');
        }

        return $contentItem;
    }

    private function normalizeNullableText(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function toBoolean(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }
}
