<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Content Types';
$adminPageDescription = 'Manage content type definitions and template availability.';
?>
<section class="admin__stack" aria-label="Content types">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Content Types</h2>
            <p class="admin-page__subtitle">Define, review, and maintain content type rendering contracts.</p>
        </div>
        <p><a class="admin-btn admin-btn--primary" href="/admin/content-types/create">Create Content Type</a></p>
    </header>

    <?php if (isset($success) && is_string($success) && $success !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($success) ?></span></p>
    <?php endif; ?>

    <?php if (isset($error) && is_string($error) && $error !== ''): ?>
        <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e($error) ?></span></p>
    <?php endif; ?>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Registered Content Types</h3>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption>Admin content type definitions</caption>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Slug</th>
                        <th scope="col">View Type</th>
                        <th scope="col">Template</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!isset($rows) || !is_array($rows) || $rows === []): ?>
                        <tr>
                            <td colspan="6">No content types found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= $e((string) ($row['name'] ?? '')) ?></td>
                                <td><code><?= $e((string) ($row['slug'] ?? '')) ?></code></td>
                                <td>
                                    <?php if (($row['viewType'] ?? '') === 'collection'): ?>
                                        <span class="admin-badge admin-badge--primary">collection</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge--muted">single</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= $e((string) ($row['template'] ?? '')) ?></code>
                                    <?php if (($row['templateExists'] ?? false) === true): ?>
                                        <span class="admin-badge admin-badge--success">Exists</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge--warning">Missing template</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($row['templateExists'] ?? false) === true): ?>
                                        <span class="admin-badge admin-badge--success">template exists</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge--warning">template missing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <a class="admin-action admin-action--secondary" href="<?= $e((string) ($row['editPath'] ?? '#')) ?>">Edit</a>
                                        <?php if (($row['canDelete'] ?? false) === true): ?>
                                            <form action="<?= $e((string) ($row['deletePath'] ?? '')) ?>" method="post">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                                                <button class="admin-action admin-action--danger" type="submit">Delete</button>
                                            </form>
                                        <?php endif; ?>
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
