<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Dashboard';
$adminPageDescription = 'Use the dashboard as a central navigation hub for content operations.';

$displayName = is_array($authUser) && is_string($authUser['display_name'] ?? null)
    ? $authUser['display_name']
    : 'Admin';

$role = is_array($authUser) && is_string($authUser['role'] ?? null)
    ? $authUser['role']
    : 'editor';
?>
<section class="admin-dashboard" aria-label="Dashboard control center">
    <header class="admin-dashboard__header">
        <h1 class="admin-dashboard__title">Dashboard</h1>
        <p class="admin-dashboard__meta">Control panel overview for content operations and template health.</p>
    </header>

    <section class="admin-section" aria-labelledby="dashboard-welcome-section-title">
        <h2 class="admin-section__title" id="dashboard-welcome-section-title">Welcome</h2>
        <article class="admin-panel admin-panel--muted admin-card admin-hero" aria-labelledby="dashboard-welcome-title">
            <div class="admin-hero__content">
                <h3 class="admin-card__title" id="dashboard-welcome-title">Welcome back, <?= $e($displayName) ?></h3>
                <p class="admin-card__meta">Signed in as <?= $e($role) ?>.</p>
                <p class="admin-panel__description">Use quick actions to move directly into the tools you need.</p>
            </div>
        </article>
    </section>

    <section class="admin-section" aria-labelledby="dashboard-actions-section-title">
        <h2 class="admin-section__title" id="dashboard-actions-section-title">Quick actions</h2>
        <article class="admin-panel admin-card" aria-labelledby="dashboard-actions-title">
            <div class="admin-card__header">
                <h3 class="admin-card__title" id="dashboard-actions-title">Primary actions</h3>
                <p class="admin-card__meta">Jump directly into common setup and content workflows.</p>
            </div>
            <div class="admin-actions admin-actions--stack">
                <?php foreach ($quickActions as $action): ?>
                    <?php
                    $actionClass = trim((string) ($action['class'] ?? 'admin-action admin-action--primary'));
                    if (strpos($actionClass, 'admin-action--primary') === false) {
                        $actionClass .= ' admin-action--primary';
                    }
                    ?>
                    <a
                        class="<?= $e($actionClass) ?>"
                        href="<?= $e((string) ($action['href'] ?? '#')) ?>"
                        <?php if (($action['isPlaceholder'] ?? false) === true): ?>aria-disabled="true"<?php endif; ?>
                    >
                        <?= $e((string) ($action['label'] ?? 'Action')) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="admin-section" aria-labelledby="dashboard-template-status-section-title">
        <h2 class="admin-section__title" id="dashboard-template-status-section-title">Template status</h2>
        <article class="admin-panel admin-card" aria-labelledby="dashboard-template-status-title">
            <div class="admin-card__header">
                <h3 class="admin-card__title" id="dashboard-template-status-title">Template status overview</h3>
                <p class="admin-card__meta">Track missing templates and storefront readiness indicators.</p>
            </div>
            <ul class="admin-list-block" role="list">
                <li class="admin-list-item admin-list-item--metric">
                    <span class="admin-list-item__label">Index template status</span>
                    <span class="admin-list-item__value"><?= $e((string) ($templateStatus['indexTemplateStatus'] ?? 'Unknown')) ?></span>
                </li>
                <li class="admin-list-item admin-list-item--metric">
                    <span class="admin-list-item__label">Missing content templates</span>
                    <span class="admin-list-item__value"><?= $e((string) ($templateStatus['missingContentTemplateCount'] ?? 0)) ?></span>
                </li>
                <li class="admin-list-item admin-list-item--metric">
                    <span class="admin-list-item__label">Missing system templates</span>
                    <span class="admin-list-item__value"><?= $e((string) ($templateStatus['missingSystemTemplateCount'] ?? 0)) ?></span>
                </li>
            </ul>
        </article>
    </section>

    <section class="admin-section" aria-labelledby="dashboard-content-type-summary-section-title">
        <h2 class="admin-section__title" id="dashboard-content-type-summary-section-title">Content type summary</h2>
        <article class="admin-panel admin-card" aria-labelledby="dashboard-content-type-summary-title">
            <div class="admin-card__header">
                <h3 class="admin-card__title" id="dashboard-content-type-summary-title">Content type distribution</h3>
                <p class="admin-card__meta">Compare total, collection, and single type counts.</p>
            </div>
            <ul class="admin-list-block" role="list">
                <li class="admin-list-item admin-list-item--metric">
                    <span class="admin-list-item__label">Content types</span>
                    <span class="admin-list-item__value"><?= $e((string) ($contentTypeSummary['total'] ?? 0)) ?></span>
                </li>
                <li class="admin-list-item admin-list-item--metric">
                    <span class="admin-list-item__label">Collection types</span>
                    <span class="admin-list-item__value"><?= $e((string) ($contentTypeSummary['collections'] ?? 0)) ?></span>
                </li>
                <li class="admin-list-item admin-list-item--metric">
                    <span class="admin-list-item__label">Single types</span>
                    <span class="admin-list-item__value"><?= $e((string) ($contentTypeSummary['singles'] ?? 0)) ?></span>
                </li>
            </ul>
        </article>
    </section>
</section>
