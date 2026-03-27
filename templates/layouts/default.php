<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $content */
$meta = is_array($meta ?? null) ? $meta : [];
$metaTitle = is_string($meta['title'] ?? null) && trim((string) $meta['title']) !== '' ? trim((string) $meta['title']) : 'Content PHP Blueprint';
$metaDescription = is_string($meta['description'] ?? null) && trim((string) $meta['description']) !== '' ? trim((string) $meta['description']) : null;
$metaOgImage = is_string($meta['og_image'] ?? null) && trim((string) $meta['og_image']) !== '' ? trim((string) $meta['og_image']) : null;
$metaCanonical = is_string($meta['canonical'] ?? null) && trim((string) $meta['canonical']) !== '' ? trim((string) $meta['canonical']) : null;
$metaNoindex = ($meta['noindex'] ?? false) === true;
$editorModeEnabled = ($editorModeActive ?? false) === true && ($editorCanUse ?? false) === true;
$csrfToken = is_object($request ?? null) && method_exists($request, 'attribute')
    ? (string) ($request->attribute('csrf_token') ?? '')
    : '';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($metaTitle) ?></title>
    <?php if ($metaDescription !== null): ?>
        <meta name="description" content="<?= $e($metaDescription) ?>">
    <?php endif; ?>
    <?php if ($metaOgImage !== null): ?>
        <meta property="og:image" content="<?= $e($metaOgImage) ?>">
    <?php endif; ?>
    <?php if ($metaCanonical !== null): ?>
        <link rel="canonical" href="<?= $e($metaCanonical) ?>">
    <?php endif; ?>
    <?php if ($metaNoindex): ?>
        <meta name="robots" content="noindex">
    <?php endif; ?>
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
