<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $content */
$meta = is_array($meta ?? null) ? $meta : [];
$metaTitle = is_string($meta['title'] ?? null) && trim((string) $meta['title']) !== ''
    ? trim((string) $meta['title'])
    : 'Admin · Content PHP Blueprint';
$metaDescription = is_string($meta['description'] ?? null) && trim((string) $meta['description']) !== ''
    ? trim((string) $meta['description'])
    : null;

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($metaTitle) ?></title>
    <?php if ($metaDescription !== null): ?>
        <meta name="description" content="<?= $e($metaDescription) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/admin/admin-tokens.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-shell.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-components.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-screens.css">
</head>
<body class="admin">
<div class="admin__layout">
    <?php include dirname(__DIR__) . '/partials/admin/sidebar.php'; ?>

    <div class="admin__workspace">
        <?php include dirname(__DIR__) . '/partials/admin/topbar.php'; ?>

        <main class="admin__content" id="admin-main-content">
            <div class="admin-page">
                <?= $content ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
