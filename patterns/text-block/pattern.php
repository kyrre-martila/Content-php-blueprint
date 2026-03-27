<?php

declare(strict_types=1);

/** @var array<string, string> $fields */
/** @var callable(string): string $e */
/** @var callable(string, string): string $editableText */
/** @var callable(string, string): string $editableTextarea */
?>
<section>
    <h2><?= $editableText('title', $fields['title'] ?? '') ?></h2>
    <p><?= $editableTextarea('body', $fields['body'] ?? '') ?></p>
</section>
