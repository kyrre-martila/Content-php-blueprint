<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Inspect File';
$adminPageDescription = 'Inspect and edit file metadata.';
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
?>
<section class="admin__stack" aria-label="Inspect file">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Inspect File</h2>
            <p class="admin-page__subtitle">Review immutable upload details and edit file metadata.</p>
        </div>
        <p><a class="admin-btn admin-btn--ghost" href="/admin/files">Back to Files</a></p>
    </header>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">File Details</h3>
        </div>
        <dl>
            <dt>Original name</dt>
            <dd><?= $e($file->originalName()) ?></dd>
            <dt>Mime type</dt>
            <dd><code><?= $e($file->mimeType()) ?></code></dd>
            <dt>Extension</dt>
            <dd><code><?= $e($file->extension()) ?></code></dd>
            <dt>Size</dt>
            <dd><?= $e(number_format($file->sizeBytes())) ?> bytes</dd>
            <dt>Storage path</dt>
            <dd><code><?= $e($file->storagePath()) ?></code></dd>
        </dl>
    </article>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Edit metadata</h3>
        </div>
        <form class="admin-form" action="/admin/files/<?= $e((string) $file->id()) ?>/edit" method="post">
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">

            <div class="admin-form__field">
                <label class="admin-form__label" for="slug">Slug</label>
                <input class="admin-input" id="slug" name="slug" type="text" required value="<?= $e((string) ($old['slug'] ?? $file->slug())) ?>">
                <?php if (is_string($errors['slug'] ?? null)): ?>
                    <p class="admin-form__error" role="alert"><?= $e($errors['slug']) ?></p>
                <?php endif; ?>
            </div>

            <div class="admin-form__field">
                <label class="admin-form__label" for="visibility">Visibility</label>
                <?php $selectedVisibility = (string) ($old['visibility'] ?? $file->visibility()->value); ?>
                <select class="admin-input" id="visibility" name="visibility" required>
                    <?php foreach (['private', 'authenticated', 'public'] as $visibility): ?>
                        <option value="<?= $e($visibility) ?>" <?= $selectedVisibility === $visibility ? 'selected' : '' ?>><?= $e($visibility) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (is_string($errors['visibility'] ?? null)): ?>
                    <p class="admin-form__error" role="alert"><?= $e($errors['visibility']) ?></p>
                <?php endif; ?>
            </div>

            <div class="admin-actions">
                <button class="admin-btn admin-btn--primary" type="submit">Save Metadata</button>
                <a class="admin-btn admin-btn--ghost" href="/admin/files">Cancel</a>
            </div>
        </form>
    </article>
</section>
