<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Relationships';
$adminPageDescription = 'Manage relationship rules and inspect linked content items.';
$contentTypes = is_array($contentTypes ?? null) ? $contentTypes : [];
$rules = is_array($rules ?? null) ? $rules : [];
$contentItems = is_array($contentItems ?? null) ? $contentItems : [];
$inspectedRelationships = is_array($inspectedRelationships ?? null) ? $inspectedRelationships : [];
$selectedItemId = $selectedItem?->id();
?>
<section class="admin__stack" aria-label="Relationships administration">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Relationships</h2>
            <p class="admin-page__subtitle">Define relationship rules and inspect item-level links.</p>
        </div>
    </header>

    <?php if (isset($success) && is_string($success) && $success !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($success) ?></span></p>
    <?php endif; ?>

    <?php if (isset($error) && is_string($error) && $error !== ''): ?>
        <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e($error) ?></span></p>
    <?php endif; ?>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Relationship Rules</h3>
        </div>

        <form class="admin-form" method="post" action="/admin/relationships/rules">
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">

            <div class="admin-form__group">
                <label class="admin-form__label" for="from_content_type">From Content Type</label>
                <select class="admin-form__input" id="from_content_type" name="from_content_type" required>
                    <option value="">Select a content type</option>
                    <?php foreach ($contentTypes as $type): ?>
                        <option value="<?= $e($type->name()) ?>"><?= $e($type->label()) ?> (<?= $e($type->name()) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="to_content_type">To Content Type</label>
                <select class="admin-form__input" id="to_content_type" name="to_content_type" required>
                    <option value="">Select a content type</option>
                    <?php foreach ($contentTypes as $type): ?>
                        <option value="<?= $e($type->name()) ?>"><?= $e($type->label()) ?> (<?= $e($type->name()) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="relation_type">Relation Type</label>
                <input class="admin-form__input" id="relation_type" name="relation_type" type="text" required placeholder="author">
                <p class="admin-form__help">Examples: author, related-article, venue.</p>
            </div>

            <div class="admin-form__actions">
                <button class="admin-action admin-action--primary" type="submit">Add Rule</button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption>Configured relationship rules</caption>
                <thead>
                    <tr>
                        <th scope="col">From type</th>
                        <th scope="col">To type</th>
                        <th scope="col">Relation type</th>
                        <th scope="col">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rules === []): ?>
                        <tr>
                            <td colspan="4">No relationship rules yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td><?= $e((string) $rule['from_label']) ?> <code><?= $e((string) $rule['from_slug']) ?></code></td>
                                <td><?= $e((string) $rule['to_label']) ?> <code><?= $e((string) $rule['to_slug']) ?></code></td>
                                <td><code><?= $e((string) $rule['relation_type']) ?></code></td>
                                <td>
                                    <form method="post" action="/admin/relationships/rules/delete">
                                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                                        <input type="hidden" name="from_content_type" value="<?= $e((string) $rule['from_slug']) ?>">
                                        <input type="hidden" name="to_content_type" value="<?= $e((string) $rule['to_slug']) ?>">
                                        <input type="hidden" name="relation_type" value="<?= $e((string) $rule['relation_type']) ?>">
                                        <button class="admin-action admin-action--danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Content Relationships Inspector</h3>
        </div>

        <form class="admin-form" method="get" action="/admin/relationships">
            <div class="admin-form__group">
                <label class="admin-form__label" for="item_id">Select content item</label>
                <select class="admin-form__input" id="item_id" name="item_id">
                    <option value="">Choose an item</option>
                    <?php foreach ($contentItems as $item): ?>
                        <option value="<?= $e((string) $item->id()) ?>" <?= $selectedItemId === $item->id() ? 'selected' : '' ?>>
                            <?= $e($item->title()) ?> (<?= $e($item->type()->label()) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form__actions">
                <button class="admin-action admin-action--secondary" type="submit">Inspect</button>
            </div>
        </form>

        <?php if ($selectedItem !== null): ?>
            <p class="admin-form__help">Showing relationships for <strong><?= $e($selectedItem->title()) ?></strong>.</p>
        <?php endif; ?>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption>Content item relationships</caption>
                <thead>
                    <tr>
                        <th scope="col">From item</th>
                        <th scope="col">To item</th>
                        <th scope="col">Relation type</th>
                        <th scope="col">Sort order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($selectedItem === null): ?>
                        <tr>
                            <td colspan="4">Select an item to inspect relationships.</td>
                        </tr>
                    <?php elseif ($inspectedRelationships === []): ?>
                        <tr>
                            <td colspan="4">No relationships found for this item.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inspectedRelationships as $relationship): ?>
                            <tr>
                                <td>
                                    <?= $e((string) $relationship['from_item_title']) ?>
                                    <code>#<?= $e((string) $relationship['from_item_id']) ?></code>
                                </td>
                                <td>
                                    <?= $e((string) $relationship['to_item_title']) ?>
                                    <code>#<?= $e((string) $relationship['to_item_id']) ?></code>
                                </td>
                                <td><code><?= $e((string) $relationship['relation_type']) ?></code></td>
                                <td><?= $e((string) $relationship['sort_order']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
