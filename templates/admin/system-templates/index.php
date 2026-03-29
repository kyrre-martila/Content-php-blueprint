<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'System Templates';
$adminPageDescription = 'Manage system-level templates used by framework routes and error flows.';

/** @var list<array<string, mixed>> $templates */
?>
<section class="admin__stack" aria-label="System templates manager">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">System Templates</h2>
            <p class="admin-page__subtitle">Manage system-level templates separately from content templates.</p>
        </div>
    </header>

    <section class="admin-section" aria-labelledby="system-templates-table">
        <article class="admin-panel admin-card">
            <div class="admin-card__header">
                <h3 class="admin-card__title" id="system-templates-table">System template registry</h3>
            </div>

            <div class="admin-table admin-table--template-manager">
                <div class="admin-table__header admin-table__row--template">
                    <div class="admin-table__cell">Template name</div>
                    <div class="admin-table__cell">File path</div>
                    <div class="admin-table__cell">Status</div>
                    <div class="admin-table__cell">Actions</div>
                </div>

                <?php if ($templates === []): ?>
                    <div class="admin-table__row admin-table__row--empty">
                        <div class="admin-table__cell">
                            <div class="admin-table-empty">
                                <p class="admin-table-empty__text">No system templates are registered yet. Start by creating a content type to scaffold templates and workflows.</p>
                                <a class="admin-btn admin-btn--primary" href="/admin/content-types/create">Create Content Type</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($templates as $template): ?>
                        <div class="admin-table__row admin-table__row--template">
                            <div class="admin-table__cell"><?= $e((string) $template['name']) ?></div>
                            <div class="admin-table__cell"><code><?= $e((string) $template['path']) ?></code></div>
                            <div class="admin-table__cell">
                                <?php if (($template['status'] ?? '') === 'exists'): ?>
                                    <span class="admin-badge admin-badge--success">Exists</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge--warning">Missing template</span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-table__cell">
                                <div class="admin-actions admin-actions--table">
                                    <?php if (($template['status'] ?? '') === 'exists'): ?>
                                        <a class="admin-action admin-action--secondary" href="<?= $e((string) $template['editPath']) ?>">Edit</a>
                                    <?php else: ?>
                                        <a class="admin-action admin-action--secondary" href="<?= $e((string) $template['editPath']) ?>">Create</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>
</section>
