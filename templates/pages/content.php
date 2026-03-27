<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;

/** @var callable(string): string $e */
/** @var callable(string, array<string, mixed>): string $editableText */
/** @var ContentItem $contentItem */
/** @var list<array{pattern: string, data: array<string, string>}> $patternBlocks */

$layout = 'layouts/default.php';
?>
<article>
    <header>
        <h1><?= $editableText($contentItem->title(), [
            'data-edit-type' => 'content_item',
            'data-edit-field' => 'title',
            'data-content-id' => (string) $contentItem->id(),
        ]) ?></h1>
    </header>

    <?php if (($patternBlocks ?? []) !== []): ?>
        <?php foreach ($patternBlocks as $blockIndex => $block): ?>
            <?= $renderer->renderPattern($block['pattern'], [
                ...$block['data'],
                '_editor' => [
                    'content_id' => $contentItem->id(),
                    'block_index' => $blockIndex,
                    'active' => ($editorModeActive ?? false) === true && ($editorCanUse ?? false) === true,
                ],
            ]) ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p><strong>Slug:</strong> <?= $e($contentItem->slug()->value()) ?></p>
        <p><strong>Status:</strong> <?= $e($contentItem->status()->value) ?></p>
        <p><strong>Type:</strong> <?= $e($contentItem->type()->label()) ?></p>
    <?php endif; ?>
</article>
