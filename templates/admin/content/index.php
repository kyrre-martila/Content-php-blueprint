<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Content';
$adminPageDescription = 'Manage content records and publishing state.';
?>
<section class="admin__stack">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Content Items</h2>
            <p class="admin-page__subtitle">Reusable table region for pages, posts, and future content types.</p>
        </div>
        <p><a class="admin-btn admin-btn--primary" href="/admin/content/create">Create content item</a></p>
    </header>

    <?php if (isset($success) && is_string($success) && $success !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($success) ?></span></p>
    <?php endif; ?>

    <article class="admin-panel">
        <?php if ($items === []): ?>
            <p class="admin-panel__description">No content items found.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <caption>Admin content list</caption>
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                            <th scope="col">Updated</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $e($item->title) ?></td>
                                <td><?= $e($item->slug) ?></td>
                                <td><?= $e($item->contentType) ?></td>
                                <td><?= $e($item->status) ?></td>
                                <td><?= $e($item->updatedAt) ?></td>
                                <td><a href="/admin/content/<?= $e((string) $item->id) ?>/edit">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>
