<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;

/** @var callable(string): string $e */
/** @var callable(string, array<string, mixed>): string $editableText */
/** @var callable(string, array<string, mixed>): string $editableTextarea */
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

    <?php if (($editorModeActive ?? false) === true && ($editorCanUse ?? false) === true): ?>
        <section aria-label="SEO metadata">
            <h2>SEO metadata</h2>
            <p><strong>Meta title:</strong> <?= $editableText($contentItem->metaTitle() ?? '', [
                'data-edit-type' => 'content_item',
                'data-edit-field' => 'meta_title',
                'data-content-id' => (string) $contentItem->id(),
            ]) ?></p>
            <p><strong>Meta description:</strong> <?= $editableTextarea($contentItem->metaDescription() ?? '', [
                'data-edit-type' => 'content_item',
                'data-edit-field' => 'meta_description',
                'data-content-id' => (string) $contentItem->id(),
            ]) ?></p>
            <p><strong>OG image:</strong> <?= $editableText($contentItem->ogImage() ?? '', [
                'data-edit-type' => 'content_item',
                'data-edit-field' => 'og_image',
                'data-content-id' => (string) $contentItem->id(),
            ]) ?></p>
            <p><strong>Canonical URL:</strong> <?= $editableText($contentItem->canonicalUrl() ?? '', [
                'data-edit-type' => 'content_item',
                'data-edit-field' => 'canonical_url',
                'data-content-id' => (string) $contentItem->id(),
            ]) ?></p>
            <p><strong>Noindex:</strong> <?= $editableText($contentItem->noindex() ? 'true' : 'false', [
                'data-edit-type' => 'content_item',
                'data-edit-field' => 'noindex',
                'data-content-id' => (string) $contentItem->id(),
            ]) ?></p>
        </section>
    <?php endif; ?>
</article>
