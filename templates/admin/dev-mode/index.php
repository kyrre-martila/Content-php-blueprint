<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Dev mode';
$adminPageDescription = 'Controlled template and asset editing access.';

$devModeEnabled = ($devModeActive ?? false) === true;
?>
<section class="admin__stack">
    <h1>Dev Mode</h1>
    <p><strong>Warning:</strong> Dev Mode edits the live presentation layer. Use only for trusted, auditable changes.</p>
    <p>Scope is intentionally limited to templates, patterns, and frontend asset files.</p>

    <?php if (is_string($success ?? null) && $success !== ''): ?>
        <p role="status" style="color:#067647;"><?= $e($success) ?></p>
    <?php endif; ?>

    <?php if (is_string($error ?? null) && $error !== ''): ?>
        <p role="alert" style="color:#b42318;"><?= $e($error) ?></p>
    <?php endif; ?>

    <p>Status: <strong><?= $devModeEnabled ? 'Active' : 'Inactive' ?></strong></p>

    <?php if ($devModeEnabled): ?>
        <form class="admin-flow" method="post" action="/admin/dev-mode/disable">
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
            <button type="submit">Disable Dev Mode</button>
        </form>
    <?php else: ?>
        <form class="admin-flow" method="post" action="/admin/dev-mode/enable">
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
            <button type="submit">Enable Dev Mode</button>
        </form>
    <?php endif; ?>

    <h2>Allowed editable roots</h2>
    <ul>
        <?php foreach ($allowedRoots as $root): ?>
            <li><code><?= $e($root) ?></code></li>
        <?php endforeach; ?>
    </ul>

    <h2>Editable files</h2>
    <?php foreach ($editableFiles as $area => $files): ?>
        <h3><?= $e($area) ?></h3>
        <?php if ($files === []): ?>
            <p>No editable files found.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li>
                        <a href="/admin/dev-mode/edit?path=<?= rawurlencode($file) ?>"><?= $e($file) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>

    <p><a href="/admin">Back to dashboard</a></p>
</section>
