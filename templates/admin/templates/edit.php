<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Template Editor';
$adminPageDescription = 'Edit template source files used by site rendering.';
?>
<section class="admin__stack" aria-label="Template editor">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Template Editor</h2>
            <p class="admin-page__subtitle">File-based editing entry for template source files.</p>
        </div>
    </header>

    <?php if (is_string($success ?? null) && $success !== ''): ?>
        <p role="status" class="admin-form__help"><?= $e($success) ?></p>
    <?php endif; ?>

    <?php if (is_string($error ?? null) && $error !== ''): ?>
        <p role="alert" class="admin-form__help admin-form__help--danger"><?= $e($error) ?></p>
    <?php endif; ?>

    <article class="admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Template details</h3>
        </div>

        <p><span class="admin-badge admin-badge--warning">Warning</span> Editing template files affects site rendering.</p>

        <form class="admin-form" method="post" action="/admin/templates/edit">
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
            <input type="hidden" name="path" value="<?= $e((string) $templatePath) ?>">

            <div class="admin-form__group">
                <label class="admin-form__label" for="template-name">Template name</label>
                <input class="admin-form__input" id="template-name" type="text" readonly value="<?= $e((string) $templateName) ?>">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="template-path">Template path</label>
                <input class="admin-form__input" id="template-path" type="text" readonly value="<?= $e((string) $templatePath) ?>">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="template-content">File editor</label>
                <textarea
                    class="admin-form__input"
                    id="template-content"
                    name="content"
                    rows="24"
                    spellcheck="false"
                    style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;"
                ><?= $e((string) $content) ?></textarea>
                <p class="admin-form__help">Maximum template size: <?= $e((string) $maxSize) ?> bytes.</p>
            </div>

            <?php if (($templateExists ?? false) !== true): ?>
                <div class="admin-form__group">
                    <p class="admin-form__help admin-form__help--danger">This template file does not exist yet.</p>
                    <label class="admin-form__option" for="create-if-missing">
                        <input id="create-if-missing" type="checkbox" name="create_if_missing" value="1">
                        Create template file
                    </label>
                </div>
            <?php endif; ?>

            <div class="admin-form__actions">
                <button type="submit" class="admin-btn admin-btn--primary">
                    <?= (($templateExists ?? false) === true) ? 'Save' : 'Save / Create' ?>
                </button>
                <a class="admin-btn" href="/admin/templates">Cancel</a>
            </div>
        </form>
    </article>
</section>
