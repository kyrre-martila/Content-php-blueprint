<?php

declare(strict_types=1);

$pageTitle = is_string($adminPageTitle ?? null) && trim($adminPageTitle) !== ''
    ? trim($adminPageTitle)
    : 'Admin';
$pageDescription = is_string($adminPageDescription ?? null) && trim($adminPageDescription) !== ''
    ? trim($adminPageDescription)
    : null;
$displayName = is_array($authUser ?? null) && is_string($authUser['display_name'] ?? null)
    ? $authUser['display_name']
    : 'Admin User';
?>
<header class="admin__topbar">
    <div class="admin-topbar__title-group">
        <h1 class="admin-topbar__title"><?= $e($pageTitle) ?></h1>
        <?php if ($pageDescription !== null): ?>
            <p class="admin-topbar__description"><?= $e($pageDescription) ?></p>
        <?php endif; ?>
    </div>

    <div class="admin-topbar__search" role="search" aria-label="Admin search placeholder">
        <label class="admin-topbar__search-label" for="admin-shell-search">Search</label>
        <input class="admin-input" id="admin-shell-search" type="search" placeholder="Search..." disabled>
    </div>

    <div class="admin-topbar__utilities">
        <a class="admin-btn" href="/" target="_blank" rel="noopener">View site</a>
        <div class="admin-topbar__profile" aria-label="Current user">
            <span class="admin-topbar__avatar" aria-hidden="true"><?= $e(strtoupper(substr($displayName, 0, 1))) ?></span>
            <span class="admin-topbar__name"><?= $e($displayName) ?></span>
        </div>
    </div>
</header>
