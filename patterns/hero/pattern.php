<?php

declare(strict_types=1);

/**
 * @var array<string, string> $fields
 * @var callable(string): string $e
 * @var callable(string, string): string $editableText
 * @var callable(string, string): string $editableTextarea
 */
?>
<section>
    <h1><?= $editableText('headline', $fields['headline'] ?? '') ?></h1>
    <p><?= $editableTextarea('subheadline', $fields['subheadline'] ?? '') ?></p>
    <button type="button"><?= $editableText('button_text', $fields['button_text'] ?? '') ?></button>
</section>
