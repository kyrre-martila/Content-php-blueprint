<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Create Content Type';
$adminPageDescription = 'Define a new content type and template mapping contract.';
$formTitle = 'Create Content Type';
$actionPath = '/admin/content-types/create';
$submitLabel = 'Save';
$slugReadonly = false;
?>
<section class="admin__stack" aria-label="Create content type">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Create Content Type</h2>
            <p class="admin-page__subtitle">Configure content schema identity and view rendering mode.</p>
        </div>
    </header>

    <?php require __DIR__ . '/_form.php'; ?>
</section>
