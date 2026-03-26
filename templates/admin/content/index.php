<?php

declare(strict_types=1);

$layout = 'layouts/default.php';
?>
<section>
    <header>
        <h1>Content Items</h1>
        <p><a href="/admin/content/create">Create content item</a></p>
    </header>

    <?php if (isset($success) && is_string($success) && $success !== ''): ?>
        <p role="status" style="color:#067647;"><?= $e($success) ?></p>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <p>No content items found.</p>
    <?php else: ?>
        <table>
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
    <?php endif; ?>

    <p><a href="/admin">Back to dashboard</a></p>
</section>
