<?php

declare(strict_types=1);

$currentPath = '/';
if (isset($request) && is_object($request) && method_exists($request, 'path')) {
    $currentPath = (string) $request->path();
}

$navigationSections = [
    [
        'title' => 'Dashboard',
        'items' => [
            [
                'label' => 'Dashboard',
                'href' => '/admin',
                'icon' => '⌂',
                'active' => $currentPath === '/admin',
            ],
        ],
    ],
    [
        'title' => 'Content',
        'items' => [
            [
                'label' => 'Content',
                'href' => '/admin/content',
                'icon' => '☰',
                'active' => $currentPath === '/admin/content' || str_starts_with($currentPath, '/admin/content/'),
            ],
            [
                'label' => 'Content Types',
                'href' => '/admin/content-types',
                'icon' => '⊞',
                'active' => $currentPath === '/admin/content-types' || str_starts_with($currentPath, '/admin/content-types/'),
            ],
            [
                'label' => 'Categories',
                'href' => null,
                'icon' => '◫',
                'active' => false,
                'future' => true,
            ],
            [
                'label' => 'Relationships',
                'href' => '/admin/relationships',
                'icon' => '⇄',
                'active' => str_starts_with($currentPath, '/admin/relationships'),
            ],
            [
                'label' => 'Templates',
                'href' => '/admin/templates',
                'icon' => '◇',
                'active' => str_starts_with($currentPath, '/admin/templates'),
            ],
        ],
    ],
    [
        'title' => 'System',
        'items' => [
            [
                'label' => 'Settings',
                'href' => '/admin/settings',
                'icon' => '⚙',
                'active' => $currentPath === '/admin/settings' || str_starts_with($currentPath, '/admin/settings/'),
            ],
            [
                'label' => 'Users',
                'href' => null,
                'icon' => '◉',
                'active' => false,
                'future' => true,
            ],
            [
                'label' => 'Dev Mode',
                'href' => '/admin/dev-mode',
                'icon' => '⌘',
                'active' => str_starts_with($currentPath, '/admin/dev-mode'),
            ],
            [
                'label' => 'System Templates',
                'href' => '/admin/system-templates',
                'icon' => '◇',
                'active' => str_starts_with($currentPath, '/admin/system-templates'),
            ],
            [
                'label' => 'View site',
                'href' => '/',
                'icon' => '↗',
                'active' => false,
            ],
        ],
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

    <div class="admin-sidebar__navs" data-sidebar-state="expanded">
        <?php foreach ($navigationSections as $section): ?>
            <nav class="admin-sidebar__nav" aria-label="<?= $e($section['title']) ?> navigation">
                <p class="admin-sidebar__section-title"><?= $e($section['title']) ?></p>
                <ul class="admin-sidebar__list">
                    <?php foreach ($section['items'] as $item): ?>
                        <li>
                            <?php if (($item['href'] ?? null) !== null): ?>
                                <a
                                    class="admin-sidebar__link<?= $item['active'] ? ' admin-sidebar__link--active' : '' ?>"
                                    href="<?= $e($item['href']) ?>"
                                    <?= $item['active'] ? 'aria-current="page"' : '' ?>
                                >
                                    <span class="admin-sidebar__active-indicator" aria-hidden="true"></span>
                                    <span class="admin-sidebar__icon" aria-hidden="true"><?= $e($item['icon']) ?></span>
                                    <span class="admin-sidebar__label"><?= $e($item['label']) ?></span>
                                </a>
                            <?php else: ?>
                                <span class="admin-sidebar__link admin-sidebar__link--future" aria-disabled="true">
                                    <span class="admin-sidebar__active-indicator" aria-hidden="true"></span>
                                    <span class="admin-sidebar__icon" aria-hidden="true"><?= $e($item['icon']) ?></span>
                                    <span class="admin-sidebar__label"><?= $e($item['label']) ?></span>
                                    <span class="admin-sidebar__future-label">Future</span>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endforeach; ?>
    </div>
</aside>
