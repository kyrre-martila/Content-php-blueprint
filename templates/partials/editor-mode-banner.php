<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $csrfToken */
?>
<aside class="editor-mode-banner" aria-label="Editor mode status">
    <strong>Editor Mode Active</strong>
    <form method="post" action="/editor-mode/disable">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <button type="submit">Disable</button>
    </form>
</aside>
