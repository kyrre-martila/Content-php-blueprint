<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $content */
$editorModeEnabled = ($editorModeActive ?? false) === true && ($editorCanUse ?? false) === true;
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
    <?php include dirname(__DIR__) . '/partials/editor-mode-banner.php'; ?>
    <script src="/assets/js/editor-mode.js" defer></script>
<?php endif; ?>
<main>
    <?= $content ?>
</main>
</body>
</html>
