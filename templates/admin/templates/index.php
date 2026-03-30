<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Template Manager';
$adminPageDescription = 'Manage index, content, collection, category collection, and system template files.';

/** @var array<string, list<array<string, mixed>>> $templateGroups */
?>
<section class="admin__stack" aria-label="Template manager">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Template Manager</h2>
            <p class="admin-page__subtitle">Grouped template map for blueprint index, content, collection, category collection, and system rendering.</p>
        </div>
    </header>

    <?php foreach ($templateGroups as $groupName => $entries): ?>
        <?php $groupId = 'template-group-' . strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $groupName)); ?>
        <section class="admin-section" aria-labelledby="<?= $e($groupId) ?>">
            <article class="admin-panel admin-card">
                <div class="admin-card__header">
                    <h3 class="admin-card__title" id="<?= $e($groupId) ?>"><?= $e($groupName) ?> templates</h3>
                </div>

                <div class="admin-table admin-table--template-manager">
                    <div class="admin-table__header admin-table__row--template">
                        <div class="admin-table__cell">Template</div>
                        <div class="admin-table__cell">Type</div>
                        <div class="admin-table__cell">File path</div>
                        <div class="admin-table__cell">Status</div>
                        <div class="admin-table__cell">Action</div>
                    </div>

                    <?php if ($entries === []): ?>
                        <div class="admin-table__row admin-table__row--empty">
                            <div class="admin-table__cell">
                                <div class="admin-table-empty">
                                    <p class="admin-table-empty__text">No templates were found in this group. Create a content type first to generate and manage template targets.</p>
                                    <a class="admin-btn admin-btn--primary" href="/admin/content-types/create">Create Content Type</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <div class="admin-table__row admin-table__row--template">
                                <div class="admin-table__cell">
                                    <span><?= $e((string) $entry['name']) ?></span>
                                    <?php if (($entry['isFallbackRole'] ?? false) === true): ?>
                                        <span class="admin-badge admin-badge--muted">Fallback template</span>
                                    <?php endif; ?>
                                </div>
                                <div class="admin-table__cell"><?= $e((string) $entry['type']) ?></div>
                                <div class="admin-table__cell"><code><?= $e((string) $entry['path']) ?></code></div>
                                <div class="admin-table__cell">
                                    <?php if (($entry['status'] ?? '') === 'exists'): ?>
                                        <span class="admin-badge admin-badge--success">Exists</span>
                                    <?php elseif (($entry['status'] ?? '') === 'fallback'): ?>
                                        <span class="admin-badge admin-badge--muted">Fallback template</span>
                                        <?php if (is_string($entry['fallbackPath'] ?? null)): ?>
                                            <span class="admin-template-manager__meta">→ <?= $e((string) $entry['fallbackPath']) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge--warning">Missing template</span>
                                    <?php endif; ?>
                                </div>
                                <div class="admin-table__cell">
                                    <div class="admin-actions admin-actions--table">
                                        <a class="admin-action admin-action--secondary" href="<?= $e((string) $entry['editPath']) ?>">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    <?php endforeach; ?>
</section>
