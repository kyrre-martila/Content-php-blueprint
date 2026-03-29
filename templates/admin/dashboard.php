<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Dashboard';
$adminPageDescription = 'Overview of your admin workspace and publishing health.';

$displayName = is_array($authUser) && is_string($authUser['display_name'] ?? null)
    ? $authUser['display_name']
    : 'Unknown';

$role = is_array($authUser) && is_string($authUser['role'] ?? null)
    ? $authUser['role']
    : 'unknown';

$editorModeEnabled = ($editorModeActive ?? false) === true && ($editorCanUse ?? false) === true;
?>
<section class="admin__stack" aria-label="Dashboard shell content">
    <article class="admin-panel admin-panel--muted">
        <h2 class="admin-panel__title">Welcome back, <?= $e($displayName) ?></h2>
        <p class="admin-panel__description">This shell is ready for dashboard cards, tables, template management, and settings screens.</p>
        <p class="admin-panel__description">Signed in as <?= $e($role) ?>.</p>
    </article>

    <section class="admin-grid admin-grid--stats" aria-label="Dashboard placeholders">
        <article class="admin-stat">
            <p class="admin-stat__label">Pages</p>
            <p class="admin-stat__value">--</p>
        </article>
        <article class="admin-stat">
            <p class="admin-stat__label">Published</p>
            <p class="admin-stat__value">--</p>
        </article>
        <article class="admin-stat">
            <p class="admin-stat__label">Drafts</p>
            <p class="admin-stat__value">--</p>
        </article>
        <article class="admin-stat">
            <p class="admin-stat__label">Media</p>
            <p class="admin-stat__value">--</p>
        </article>
    </section>

    <article class="admin-panel">
        <h2 class="admin-panel__title">Reusable shell actions</h2>
        <div class="admin__stack">
            <p>
                <a class="admin-btn admin-btn--primary" href="/admin/content">Manage content</a>
                <a class="admin-btn" href="/admin/dev-mode">Open dev mode</a>
            </p>
            <p class="admin-panel__description">Application version: <strong><?= $e((string) ($currentVersion ?? 'unknown')) ?></strong>. Installed version: <strong><?= $e((string) (($installedVersion ?? null) ?? 'not recorded')) ?></strong>.</p>

            <?php if (($upgradeRequired ?? false) === true): ?>
                <p><span class="admin-badge admin-badge--warning">Upgrade pending</span></p>
            <?php endif; ?>

            <?php if (($editorCanUse ?? false) === true): ?>
                <?php if ($editorModeEnabled): ?>
                    <form method="post" action="/editor-mode/disable">
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <button class="admin-btn" type="submit">Disable editor mode</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/editor-mode/enable">
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <button class="admin-btn" type="submit">Enable editor mode</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (($devModeCanUse ?? false) === true): ?>
                <?php if (($devModeActive ?? false) === true): ?>
                    <form method="post" action="/admin/dev-mode/disable">
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <button class="admin-btn" type="submit">Disable dev mode</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/admin/dev-mode/enable">
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <button class="admin-btn" type="submit">Enable dev mode</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="/admin/logout">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                <button class="admin-btn" type="submit">Logout</button>
            </form>
        </div>
    </article>
</section>
