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
            return $this->contentItems->save($contentItem->withTitle((string) $validated['value'], $updatedAt));
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
}
