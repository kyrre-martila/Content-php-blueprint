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
    <article class="admin-panel admin-panel--muted admin-hero" aria-labelledby="dashboard-welcome-title">
        <div class="admin-hero__content">
            <h2 class="admin-panel__title" id="dashboard-welcome-title">Welcome back, <?= $e($displayName) ?></h2>
            <p class="admin-panel__description">Signed in as <?= $e($role) ?>.</p>
            <p class="admin-panel__description">Use quick actions to move directly into the tools you need.</p>
        </div>
    </article>

    <section class="admin-section" aria-labelledby="dashboard-actions-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-actions-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-actions-title">Quick actions</h2>
            </div>
            <div class="admin-actions admin-actions--stack">
                <?php foreach ($quickActions as $action): ?>
                    <a
                        class="<?= $e((string) ($action['class'] ?? 'admin-action admin-action--secondary')) ?>"
                        href="<?= $e((string) ($action['href'] ?? '#')) ?>"
                        <?php if (($action['isPlaceholder'] ?? false) === true): ?>aria-disabled="true"<?php endif; ?>
                    >
                        <?= $e((string) ($action['label'] ?? 'Action')) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="admin-section" aria-labelledby="dashboard-template-status-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-template-status-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-template-status-title">Template status</h2>
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

    <section class="admin-section" aria-labelledby="dashboard-content-type-summary-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-content-type-summary-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-content-type-summary-title">Content types summary</h2>
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
