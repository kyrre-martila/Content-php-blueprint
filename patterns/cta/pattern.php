<?php

declare(strict_types=1);

/** @var array<string, string> $fields */
/** @var callable(string): string $e */
?>
<section>
    <p><?= $e($fields['message'] ?? '') ?></p>
    <button type="button"><?= $e($fields['button_text'] ?? '') ?></button>
    <?php if (($fields['image'] ?? '') !== ''): ?>
        <p><img src="<?= $e($fields['image']) ?>" alt=""></p>
    <?php endif; ?>
</section>
