<?php

declare(strict_types=1);

/**
 * Pattern templates are presentation-only.
 * They receive only sanitized scalar values from PatternRenderer via $fields.
 * No application service objects are exposed to pattern scope.
 *
 * @var array<string, string> $fields
 * @var callable(string): string $e
 */
?>
<section>
    <h1><?= $e($fields['headline'] ?? '') ?></h1>
    <p><?= $e($fields['subheadline'] ?? '') ?></p>
    <button type="button"><?= $e($fields['button_text'] ?? '') ?></button>
</section>
