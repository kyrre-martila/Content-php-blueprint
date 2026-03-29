<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Edit file';
$adminPageDescription = 'Modify an allowed file inside Dev Mode.';
?>
<section class="admin__stack">
    <h1>Dev Mode File Editor</h1>
    <p><strong>Warning:</strong> Changes saved here affect live rendering immediately.</p>

    <?php if (is_string($success ?? null) && $success !== ''): ?>
        <p role="status" style="color:#067647;"><?= $e($success) ?></p>
    <?php endif; ?>

    <?php if (is_string($error ?? null) && $error !== ''): ?>
        <p role="alert" style="color:#b42318;"><?= $e($error) ?></p>
    <?php endif; ?>

    <p><strong>Path:</strong> <code><?= $e($relativePath) ?></code></p>
    <p>Maximum editable file size: <?= $e((string) $maxSize) ?> bytes.</p>

    <form class="admin-form" method="post" action="/admin/dev-mode/edit">
        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
        <input type="hidden" name="path" value="<?= $e($relativePath) ?>">

        <label for="content">File content</label>
        <textarea id="content" name="content" rows="30" style="width:100%;font-family:monospace;"><?= $e($content) ?></textarea>

        <p>
            <button type="submit">Save file</button>
        </p>
    </form>

    <p><a href="/admin/dev-mode">Back to Dev Mode index</a></p>
</section>
