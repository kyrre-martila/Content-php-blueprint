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

$stats = [
    ['label' => 'Total pages', 'value' => '34', 'trend' => '+3 this week'],
    ['label' => 'Published content', 'value' => '156', 'trend' => '+12 this month'],
    ['label' => 'Draft items', 'value' => '18', 'trend' => '5 need review'],
    ['label' => 'Media files', 'value' => '450', 'trend' => '+28 uploaded'],
    ['label' => 'Content types', 'value' => '8', 'trend' => '2 in progress'],
    ['label' => 'Active users', 'value' => '12', 'trend' => '2 online now'],
];

$recentActivity = [
    ['title' => 'Home page', 'type' => 'Page', 'status' => 'Published', 'author' => 'Kyrre', 'updated' => '2 hours ago'],
    ['title' => 'About us', 'type' => 'Page', 'status' => 'Published', 'author' => 'Jane', 'updated' => '5 hours ago'],
    ['title' => 'Contact', 'type' => 'Page', 'status' => 'Published', 'author' => 'Jane', 'updated' => '1 day ago'],
    ['title' => 'Summer opening hours', 'type' => 'Post', 'status' => 'Published', 'author' => 'Kyrre', 'updated' => '2 days ago'],
    ['title' => 'Jane Doe profile', 'type' => 'Team member', 'status' => 'Draft', 'author' => 'Admin', 'updated' => '3 days ago'],
];

$quickActions = [
    ['label' => 'Create page', 'href' => '/admin/content/create'],
    ['label' => 'Add post', 'href' => '/admin/content/create'],
    ['label' => 'Upload media', 'href' => '#'],
    ['label' => 'Manage navigation', 'href' => '#'],
];

$contentOverview = [
    ['label' => 'Pages', 'count' => '34'],
    ['label' => 'Posts', 'count' => '78'],
    ['label' => 'Team members', 'count' => '12'],
    ['label' => 'Testimonials', 'count' => '25'],
    ['label' => 'Case studies', 'count' => '14'],
    ['label' => 'Site settings', 'count' => '20'],
];

$feedItems = [
    'Home page updated by Kyrre',
    'New testimonial published by Jane',
    'Service content type edited by Admin',
    'Redirect updated from /old-page to /new-page by Admin',
];
?>
<section class="admin-dashboard" aria-label="Dashboard overview">
    <article class="admin-panel admin-panel--muted admin-hero" aria-labelledby="dashboard-welcome-title">
        <div class="admin-hero__content">
            <h2 class="admin-panel__title" id="dashboard-welcome-title">Welcome back, <?= $e($displayName) ?></h2>
            <p class="admin-panel__description">Manage structured content with a clean, production-ready workflow built for your editorial team.</p>
            <p class="admin-panel__description">Signed in as <?= $e($role) ?>.</p>
        </div>
        <div class="admin-actions" aria-label="Quick start actions">
            <a class="admin-btn admin-btn--primary" href="/admin/content/create">Create page</a>
            <a class="admin-btn" href="/admin/content">Add content</a>
            <a class="admin-btn" href="/admin/content">Manage content types</a>
        </div>
    </article>

    <section class="admin-section" aria-labelledby="dashboard-stats-title">
        <h2 class="admin-section__title" id="dashboard-stats-title">Summary</h2>
        <div class="admin-grid admin-grid--stats admin-grid--stats-wide">
            <?php foreach ($stats as $stat): ?>
                <article class="admin-stat admin-stat--interactive">
                    <p class="admin-stat__label"><?= $e($stat['label']) ?></p>
                    <p class="admin-stat__value"><?= $e($stat['value']) ?></p>
                    <p class="admin-stat__meta"><?= $e($stat['trend']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="dashboard-recent-title">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-recent-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-recent-title">Recent activity</h2>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Author</th>
                        <th scope="col">Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentActivity as $item): ?>
                        <tr>
                            <td><?= $e($item['title']) ?></td>
                            <td><?= $e($item['type']) ?></td>
                            <td>
                                <span class="admin-badge <?= $item['status'] === 'Published' ? 'admin-badge--success' : 'admin-badge--warning' ?>">
                                    <?= $e($item['status']) ?>
                                </span>
                            </td>
                            <td><?= $e($item['author']) ?></td>
                            <td><?= $e($item['updated']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="admin-grid admin-grid--dashboard-columns" aria-label="Dashboard details">
        <article class="admin-panel admin-card" aria-labelledby="dashboard-overview-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-overview-title">Content overview</h2>
            </div>
            <div class="admin-list-block admin-list-block--tiles">
                <?php foreach ($contentOverview as $item): ?>
                    <div class="admin-list-item admin-list-item--metric">
                        <span class="admin-list-item__label"><?= $e($item['label']) ?></span>
                        <span class="admin-list-item__value"><?= $e($item['count']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="admin-panel admin-card" aria-labelledby="dashboard-actions-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-actions-title">Quick actions</h2>
            </div>
            <div class="admin-actions admin-actions--stack">
                <?php foreach ($quickActions as $action): ?>
                    <a class="admin-btn admin-btn--block" href="<?= $e($action['href']) ?>"><?= $e($action['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="admin-panel admin-card" aria-labelledby="dashboard-feed-title">
            <div class="admin-card__header">
                <h2 class="admin-card__title" id="dashboard-feed-title">Activity feed</h2>
            </div>
            <ul class="admin-list-block" role="list">
                <?php foreach ($feedItems as $feedItem): ?>
                    <li class="admin-list-item"><?= $e($feedItem) ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>
</section>
