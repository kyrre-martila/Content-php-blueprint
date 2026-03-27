<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $content */
$editorModeEnabled = ($editorModeActive ?? false) === true && ($editorCanEdit ?? false) === true;
$csrfToken = is_object($request ?? null) && method_exists($request, 'attribute')
    ? (string) ($request->attribute('csrf_token') ?? '')
    : '';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Content PHP Blueprint</title>
    <?php if ($editorModeEnabled): ?>
        <link rel="stylesheet" href="/assets/css/editor-mode.css">
    <?php endif; ?>
</head>
<body>
<?php if ($editorModeEnabled): ?>
    <aside class="editor-mode-banner" aria-label="Editor mode status">
        <strong>Editor Mode Active</strong>
        <form method="post" action="/admin/editor-mode/disable">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
            <button type="submit">Disable</button>
        </form>
    </aside>
    <script>
        window.__EDITOR_MODE = { csrfToken: <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> };
    </script>
    <script src="/assets/js/editor-mode.js" defer></script>
<?php endif; ?>
<main>
    <?= $content ?>
</main>
</body>
</html>
