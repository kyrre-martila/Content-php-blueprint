<?php

declare(strict_types=1);

/** @var array<string, string> $fields */
/** @var callable(string): string $e */
/** @var callable(string, string): string $editableText */
/** @var callable(string, string): string $editableTextarea */
?>
<section>
    <p><?= $editableTextarea('message', $fields['message'] ?? '') ?></p>
    <button type="button"><?= $editableText('button_text', $fields['button_text'] ?? '') ?></button>
    <?php if (($fields['image'] ?? '') !== ''): ?>
        <p><img src="<?= $e($fields['image']) ?>" alt=""></p>
    <?php endif; ?>
</section>
