<?php

declare(strict_types=1);

/** @var array<string, string> $fields */
/** @var callable(string): string $e */
?>
<section>
    <h2><?= $e($fields['title'] ?? '') ?></h2>
    <p><?= nl2br($e($fields['body'] ?? ''), false) ?></p>
</section>
