<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Upload File';
$adminPageDescription = 'Upload a new file to the Files library.';
$errors = is_array($errors ?? null) ? $errors : [];
?>
<section class="admin__stack" aria-label="Upload file">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Upload File</h2>
            <p class="admin-page__subtitle">Add a new file to Files with explicit visibility.</p>
        </div>
        <p><a class="admin-btn admin-btn--ghost" href="/admin/files">Back to Files</a></p>
    </header>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Upload details</h3>
        </div>

        <form class="admin-form" action="/admin/files/upload" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">

            <div class="admin-form__field">
                <label class="admin-form__label" for="file">File</label>
                <input class="admin-input" id="file" name="file" type="file" required>
                <?php if (is_string($errors['file'] ?? null)): ?>
                    <p class="admin-form__error" role="alert"><?= $e($errors['file']) ?></p>
                <?php endif; ?>
            </div>

            <div class="admin-form__field">
                <label class="admin-form__label" for="visibility">Visibility</label>
                <select class="admin-input" id="visibility" name="visibility" required>
                    <option value="private">private</option>
                    <option value="authenticated">authenticated</option>
                    <option value="public">public</option>
                </select>
                <?php if (is_string($errors['visibility'] ?? null)): ?>
                    <p class="admin-form__error" role="alert"><?= $e($errors['visibility']) ?></p>
                <?php endif; ?>
            </div>

            <div class="admin-actions">
                <button class="admin-btn admin-btn--primary" type="submit">Upload File</button>
                <a class="admin-btn admin-btn--ghost" href="/admin/files">Cancel</a>
            </div>
        </form>
    </article>
</section>
