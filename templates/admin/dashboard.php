<?php

declare(strict_types=1);

$layout = 'layouts/default.php';

$displayName = is_array($authUser) && is_string($authUser['display_name'] ?? null)
    ? $authUser['display_name']
    : 'Unknown';

$role = is_array($authUser) && is_string($authUser['role'] ?? null)
    ? $authUser['role']
    : 'unknown';

$editorModeEnabled = ($editorModeActive ?? false) === true && ($editorCanUse ?? false) === true;
?>
<section>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= $e($displayName) ?>.</p>
    <p>Role: <strong><?= $e($role) ?></strong></p>

    <p><a href="/admin/content">Manage content items</a></p>

    <?php if (($editorCanUse ?? false) === true): ?>
        <?php if ($editorModeEnabled): ?>
            <form method="post" action="/editor-mode/disable">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                <button type="submit">Disable editor mode</button>
            </form>
        <?php else: ?>
            <form method="post" action="/editor-mode/enable">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                <button type="submit">Enable editor mode</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($devModeCanUse ?? false) === true): ?>
        <p><a href="/admin/dev-mode">Open Dev Mode</a></p>

        <?php if (($devModeActive ?? false) === true): ?>
            <form method="post" action="/admin/dev-mode/disable">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                <button type="submit">Disable dev mode</button>
            </form>
        <?php else: ?>
            <form method="post" action="/admin/dev-mode/enable">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                <button type="submit">Enable dev mode</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="/admin/logout">
        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
        <button type="submit">Logout</button>
    </form>
</section>
