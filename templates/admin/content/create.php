<?php

declare(strict_types=1);

$layout = 'layouts/default.php';
?>
<section>
    <header>
        <h1>Create Content Item</h1>
        <p><a href="/admin/content">Back to content list</a></p>
    </header>

    <?php if (isset($errors['general'])): ?>
        <p role="alert" style="color:#b42318;"><?= $e($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" action="/admin/content/create" novalidate>
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="<?= $e((string) ($old['title'] ?? '')) ?>" required>
        <?php if (isset($errors['title'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['title']) ?></p>
        <?php endif; ?>

        <label for="slug">Slug</label>
        <input id="slug" type="text" name="slug" value="<?= $e((string) ($old['slug'] ?? '')) ?>" required>
        <?php if (isset($errors['slug'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['slug']) ?></p>
        <?php endif; ?>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="">Select status</option>
            <option value="draft" <?= (($old['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= (($old['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
        </select>
        <?php if (isset($errors['status'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['status']) ?></p>
        <?php endif; ?>

        <label for="content_type">Content Type</label>
        <select id="content_type" name="content_type" required>
            <option value="">Select content type</option>
            <?php foreach ($contentTypes as $type): ?>
                <option
                    value="<?= $e($type->name()) ?>"
                    <?= (($old['content_type'] ?? '') === $type->name()) ? 'selected' : '' ?>
                >
                    <?= $e($type->label()) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['content_type'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['content_type']) ?></p>
        <?php endif; ?>

        <label for="body">Body</label>
        <textarea id="body" name="body" rows="8" disabled aria-describedby="body-help"><?= $e((string) ($old['body'] ?? '')) ?></textarea>
        <p id="body-help">Body persistence will be wired through field-value infrastructure in a future step.</p>

        <button type="submit">Create</button>
    </form>
</section>
