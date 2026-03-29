<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Edit Content Type';
$adminPageDescription = 'Update content type metadata and rendering mode.';
$formTitle = 'Edit Content Type';
$actionPath = '/admin/content-types/' . rawurlencode((string) ($contentType?->name() ?? '')) . '/edit';
$submitLabel = 'Save';
$slugReadonly = true;
?>
<section class="admin__stack" aria-label="Edit content type">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Edit Content Type</h2>
            <p class="admin-page__subtitle">Adjust display label and view type for this definition.</p>
        </div>
    </header>

    <?php require __DIR__ . '/_form.php'; ?>
</section>
