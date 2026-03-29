<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Dashboard';
$adminPageDescription = 'A calm overview of content operations, publishing momentum, and team activity.';

$displayName = is_array($authUser) && is_string($authUser['display_name'] ?? null)
    ? $authUser['display_name']
    : 'Admin';

$role = is_array($authUser) && is_string($authUser['role'] ?? null)
    ? $authUser['role']
    : 'editor';

$templateStatus = [
    ['label' => 'Published content', 'value' => '156', 'meta' => '+12 this month'],
    ['label' => 'Draft items', 'value' => '18', 'meta' => '5 need review'],
    ['label' => 'Content types', 'value' => '8', 'meta' => '2 in progress'],
    ['label' => 'Media files', 'value' => '450', 'meta' => '+28 uploaded'],
];

$quickActions = [
    ['label' => 'Create page', 'href' => '/admin/content/create'],
    ['label' => 'Add post', 'href' => '/admin/content/create'],
    ['label' => 'Upload media', 'href' => '#'],
    ['label' => 'Manage navigation', 'href' => '#'],
];

$contentTypeSummary = [
    ['label' => 'Pages', 'count' => '34'],
    ['label' => 'Posts', 'count' => '78'],
    ['label' => 'Team members', 'count' => '12'],
    ['label' => 'Testimonials', 'count' => '25'],
    ['label' => 'Case studies', 'count' => '14'],
    ['label' => 'Site settings', 'count' => '20'],
];
?>
<section class="admin-dashboard" aria-label="Dashboard overview">
    <header class="admin-dashboard__header" aria-labelledby="dashboard-page-title">
        <p class="admin-dashboard__eyebrow">Control panel</p>
        <h2 class="admin-dashboard__title" id="dashboard-page-title">Editorial overview</h2>
        <p class="admin-dashboard__description">A clean snapshot of the current publishing workflow and your most-used actions.</p>
    </header>

    <section class="admin-section admin-section--divided" aria-labelledby="dashboard-welcome-title">
        <article class="admin-panel admin-panel--muted admin-card" aria-labelledby="dashboard-welcome-title">
            <div class="admin-card__header admin-card__header--stacked">
                <p class="admin-card__meta">Welcome section</p>
                <h3 class="admin-card__title" id="dashboard-welcome-title">Welcome back, <?= $e($displayName) ?></h3>
            </div>
            <p class="admin-panel__description">Manage structured content with a clean, production-ready workflow built for your editorial team.</p>
            <p class="admin-panel__description">Signed in as <?= $e($role) ?>.</p>
        </article>
    </section>

    <section class="admin-section admin-section--divided" aria-labelledby="dashboard-actions-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-actions-title">
            <div class="admin-card__header admin-card__header--stacked">
                <p class="admin-card__meta">Primary navigation</p>
                <h3 class="admin-card__title" id="dashboard-actions-title">Quick actions</h3>
            </div>
            <div class="admin-actions admin-actions--stack" aria-label="Quick start actions">
                <?php foreach ($quickActions as $action): ?>
                    <a class="admin-action admin-action--primary admin-action--block" href="<?= $e($action['href']) ?>"><?= $e($action['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="admin-section admin-section--divided" aria-labelledby="dashboard-template-status-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-template-status-title">
            <div class="admin-card__header admin-card__header--stacked">
                <p class="admin-card__meta">Template status</p>
                <h3 class="admin-card__title" id="dashboard-template-status-title">Publishing status</h3>
            </div>
            <div class="admin-grid admin-grid--stats-wide">
                <?php foreach ($templateStatus as $stat): ?>
                    <article class="admin-stat admin-stat--interactive">
                        <p class="admin-stat__label"><?= $e($stat['label']) ?></p>
                        <p class="admin-stat__value"><?= $e($stat['value']) ?></p>
                        <p class="admin-stat__meta"><?= $e($stat['meta']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="admin-section admin-section--divided" aria-labelledby="dashboard-content-types-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-content-types-title">
            <div class="admin-card__header admin-card__header--stacked">
                <p class="admin-card__meta">Content type summary</p>
                <h3 class="admin-card__title" id="dashboard-content-types-title">Content type totals</h3>
            </div>
            <div class="admin-list-block admin-list-block--tiles">
                <?php foreach ($contentTypeSummary as $item): ?>
                    <div class="admin-list-item admin-list-item--metric">
                        <span class="admin-list-item__label"><?= $e($item['label']) ?></span>
                        <span class="admin-list-item__value"><?= $e($item['count']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</section>
