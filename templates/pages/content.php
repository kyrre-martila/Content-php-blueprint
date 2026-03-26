<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;

/** @var callable(string): string $e */
/** @var ContentItem $contentItem */

$layout = 'layouts/default.php';
?>
<article>
    <header>
        <h1><?= $e($contentItem->title()) ?></h1>
    </header>

    <p><strong>Slug:</strong> <?= $e($contentItem->slug()->value()) ?></p>
    <p><strong>Status:</strong> <?= $e($contentItem->status()->value) ?></p>
    <p><strong>Type:</strong> <?= $e($contentItem->type()->label()) ?></p>
</article>
