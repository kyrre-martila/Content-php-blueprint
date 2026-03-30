<?php

declare(strict_types=1);

use App\Domain\Content\ContentType;

$layout = 'layouts/admin.php';
$adminPageTitle = 'Relationships';
$adminPageDescription = 'Manage relationship rules and inspect item-level relationships.';

/** @var list<ContentType> $contentTypes */
/** @var list<array{from_type: string, to_type: string, relation_type: string}> $rules */
/** @var list<array{id: int, label: string}> $itemOptions */
/** @var list<array{from_item: string, to_item: string, relation_type: string, sort_order: int}> $relationshipRows */

$selectedItemId = is_int($selectedItemId ?? null) ? $selectedItemId : null;
?>
<section class="admin__stack" aria-label="Relationship management">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Relationships</h2>
            <p class="admin-page__subtitle">Define relationship rules and inspect content-item relationship links.</p>
        </div>
    </header>

    <?php if (isset($success) && is_string($success) && $success !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($success) ?></span></p>
    <?php endif; ?>
    <?php if (isset($error) && is_string($error) && $error !== ''): ?>
        <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e($error) ?></span></p>
    <?php endif; ?>

    <article class="admin-panel admin-card admin-relationship-manager__rules">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Relationship Rules</h3>
            <p class="admin-card__meta">Create allowed relation types between content types.</p>
        </div>

        <form class="admin-form" method="post" action="/admin/relationships/rules/create" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">

            <div class="admin-form__group">
                <label class="admin-form__label" for="rule_from_type">From Content Type</label>
                <select class="admin-form__input" id="rule_from_type" name="from_type" required>
                    <option value="">Select from type</option>
                    <?php foreach ($contentTypes as $contentType): ?>
                        <option value="<?= $e($contentType->name()) ?>"><?= $e($contentType->label()) ?> (<?= $e($contentType->name()) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="rule_to_type">To Content Type</label>
                <select class="admin-form__input" id="rule_to_type" name="to_type" required>
                    <option value="">Select to type</option>
                    <?php foreach ($contentTypes as $contentType): ?>
                        <option value="<?= $e($contentType->name()) ?>"><?= $e($contentType->label()) ?> (<?= $e($contentType->name()) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="rule_relation_type">Relation Type</label>
                <input class="admin-form__input" id="rule_relation_type" name="relation_type" type="text" required placeholder="author">
                <p class="admin-form__help">Examples: author, related-article, venue.</p>
            </div>

            <div class="admin-form__actions">
                <button class="admin-action admin-action--primary" type="submit">Add Rule</button>
            </div>
        </form>

        <div class="admin-table admin-table--relationships">
            <div class="admin-table__header admin-table__row--relationship-rules">
                <div class="admin-table__cell">From type</div>
                <div class="admin-table__cell">To type</div>
                <div class="admin-table__cell">Relation type</div>
                <div class="admin-table__cell">Delete</div>
            </div>

            <?php if ($rules === []): ?>
                <div class="admin-table__row admin-table__row--empty">
                    <div class="admin-table__cell">No relationship rules configured yet.</div>
                </div>
            <?php else: ?>
                <?php foreach ($rules as $rule): ?>
                    <div class="admin-table__row admin-table__row--relationship-rules">
                        <div class="admin-table__cell"><code><?= $e($rule['from_type']) ?></code></div>
                        <div class="admin-table__cell"><code><?= $e($rule['to_type']) ?></code></div>
                        <div class="admin-table__cell"><code><?= $e($rule['relation_type']) ?></code></div>
                        <div class="admin-table__cell">
                            <form method="post" action="/admin/relationships/rules/<?= rawurlencode($rule['from_type']) ?>/<?= rawurlencode($rule['to_type']) ?>/<?= rawurlencode($rule['relation_type']) ?>">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                                <button class="admin-action admin-action--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="admin-panel admin-card admin-relationship-manager__inspector">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Content Relationships Inspector</h3>
            <p class="admin-card__meta">Inspect outgoing and incoming relationships for a selected item.</p>
        </div>

        <form class="admin-form" method="get" action="/admin/relationships">
            <div class="admin-form__group">
                <label class="admin-form__label" for="inspector_item">Content Item</label>
                <select class="admin-form__input" id="inspector_item" name="item">
                    <option value="">Select content item</option>
                    <?php foreach ($itemOptions as $option): ?>
                        <option value="<?= $e((string) $option['id']) ?>" <?= $selectedItemId === $option['id'] ? 'selected' : '' ?>>
                            <?= $e($option['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form__actions">
                <button class="admin-action admin-action--secondary" type="submit">Inspect</button>
            </div>
        </form>

        <div class="admin-table admin-table--relationships">
            <div class="admin-table__header admin-table__row--relationship-inspector">
                <div class="admin-table__cell">From item</div>
                <div class="admin-table__cell">To item</div>
                <div class="admin-table__cell">Relation type</div>
                <div class="admin-table__cell">Sort order</div>
            </div>

            <?php if ($selectedItemId === null): ?>
                <div class="admin-table__row admin-table__row--empty">
                    <div class="admin-table__cell">Select a content item to inspect relationships.</div>
                </div>
            <?php elseif ($relationshipRows === []): ?>
                <div class="admin-table__row admin-table__row--empty">
                    <div class="admin-table__cell">No relationships found for this item.</div>
                </div>
            <?php else: ?>
                <?php foreach ($relationshipRows as $row): ?>
                    <div class="admin-table__row admin-table__row--relationship-inspector">
                        <div class="admin-table__cell"><?= $e($row['from_item']) ?></div>
                        <div class="admin-table__cell"><?= $e($row['to_item']) ?></div>
                        <div class="admin-table__cell"><code><?= $e($row['relation_type']) ?></code></div>
                        <div class="admin-table__cell"><?= $e((string) $row['sort_order']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>
