<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Files';
$adminPageDescription = 'Upload, browse, inspect, and delete files.';
?>
<section class="admin__stack" aria-label="Files">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Files</h2>
            <p class="admin-page__subtitle">Manage uploaded files and visibility settings.</p>
        </div>
        <p><a class="admin-btn admin-btn--primary" href="/admin/files/upload">Upload File</a></p>
    </header>

    <?php if (isset($success) && is_string($success) && $success !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($success) ?></span></p>
    <?php endif; ?>

    <?php if (isset($error) && is_string($error) && $error !== ''): ?>
        <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e($error) ?></span></p>
    <?php endif; ?>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Uploaded Files</h3>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption>Admin uploaded files</caption>
                <thead>
                <tr>
                    <th scope="col">Original Name</th>
                    <th scope="col">Mime Type</th>
                    <th scope="col">Extension</th>
                    <th scope="col">Size</th>
                    <th scope="col">Visibility</th>
                    <th scope="col">Uploaded By</th>
                    <th scope="col">Created At</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!isset($rows) || !is_array($rows) || $rows === []): ?>
                    <tr>
                        <td colspan="8">
                            <div class="admin-table-empty">
                                <p class="admin-table-empty__text">No files uploaded yet.</p>
                                <a class="admin-btn admin-btn--primary" href="/admin/files/upload">Upload File</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= $e((string) ($row['original_name'] ?? '')) ?></td>
                            <td><code><?= $e((string) ($row['mime_type'] ?? '')) ?></code></td>
                            <td><code><?= $e((string) ($row['extension'] ?? '')) ?></code></td>
                            <td><?= $e(number_format((int) ($row['size_bytes'] ?? 0))) ?> bytes</td>
                            <td><span class="admin-badge admin-badge--muted"><?= $e((string) ($row['visibility'] ?? '')) ?></span></td>
                            <td><?= $e((string) (($row['uploaded_by'] ?? null) === null ? 'System' : ('User #' . (int) $row['uploaded_by']))) ?></td>
                            <td><?= $e((string) ($row['created_at'] ?? '')) ?></td>
                            <td>
                                <div class="admin-actions admin-actions--table">
                                    <a class="admin-action admin-action--secondary" href="<?= $e((string) ($row['edit_path'] ?? '#')) ?>">Inspect / Edit</a>
                                    <form action="<?= $e((string) ($row['delete_path'] ?? '')) ?>" method="post">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                                        <button class="admin-action admin-action--danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
