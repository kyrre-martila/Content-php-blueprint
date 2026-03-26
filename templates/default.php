<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;

/** @var callable(string): string $e */
/** @var ContentItem $contentItem */

$layout = 'layouts/default.php';
?>
<article>
    <h1><?= $e($contentItem->title()) ?></h1>
</article>
