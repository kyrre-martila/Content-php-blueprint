<?php

declare(strict_types=1);

$currentPath = '/';
if (isset($request) && is_object($request) && method_exists($request, 'path')) {
    $currentPath = (string) $request->path();
}

$primaryNavigation = [
    [
        'label' => 'Dashboard',
        'href' => '/admin',
        'icon' => '⌂',
        'active' => $currentPath === '/admin',
    ],
    [
        'label' => 'Content',
        'href' => '/admin/content',
        'icon' => '☰',
        'active' => str_starts_with($currentPath, '/admin/content'),
    ],
    [
        'label' => 'Templates',
        'href' => '#',
        'icon' => '◇',
        'active' => false,
    ],
];

$systemNavigation = [
    [
        'label' => 'Dev Mode',
        'href' => '/admin/dev-mode',
        'icon' => '⌘',
        'active' => str_starts_with($currentPath, '/admin/dev-mode'),
    ],
    [
        'label' => 'View site',
        'href' => '/',
        'icon' => '↗',
        'active' => false,
    ],
];
?>
<aside class="admin__sidebar" aria-label="Admin sidebar navigation">
    <div class="admin-sidebar__brand">
        <span class="admin-sidebar__brand-mark" aria-hidden="true">◈</span>
        <div>
            <p class="admin-sidebar__brand-name">Content PHP</p>
            <p class="admin-sidebar__brand-subtitle">Admin panel</p>
        </div>
    </div>

    <nav class="admin-sidebar__nav" aria-label="Primary navigation">
        <p class="admin-sidebar__section-title">Main</p>
        <ul class="admin-sidebar__list">
            <?php foreach ($primaryNavigation as $item): ?>
                <li>
                    <a
                        class="admin-sidebar__link<?= $item['active'] ? ' admin-sidebar__link--active' : '' ?>"
                        href="<?= $e($item['href']) ?>"
                        <?= $item['active'] ? 'aria-current="page"' : '' ?>
                    >
                        <span class="admin-sidebar__icon" aria-hidden="true"><?= $e($item['icon']) ?></span>
                        <span><?= $e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <nav class="admin-sidebar__nav admin-sidebar__nav--system" aria-label="System navigation">
        <p class="admin-sidebar__section-title">System</p>
        <ul class="admin-sidebar__list">
            <?php foreach ($systemNavigation as $item): ?>
                <li>
                    <a
                        class="admin-sidebar__link<?= $item['active'] ? ' admin-sidebar__link--active' : '' ?>"
                        href="<?= $e($item['href']) ?>"
                        <?= $item['active'] ? 'aria-current="page"' : '' ?>
                    >
                        <span class="admin-sidebar__icon" aria-hidden="true"><?= $e($item['icon']) ?></span>
                        <span><?= $e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
