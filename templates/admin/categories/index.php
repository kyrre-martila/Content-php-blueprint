<?php

declare(strict_types=1);

use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;

$layout = 'layouts/admin.php';
$adminPageTitle = 'Categories';
$adminPageDescription = 'Manage category groups and nested categories from a vertical admin workspace.';

/** @var list<CategoryGroup> $groups */
/** @var list<Category> $categories */
/** @var list<array{category: Category, depth: int}> $categoryRows */

$selectedGroupId = is_int($selectedGroupId ?? null) ? $selectedGroupId : null;
$editingGroupId = is_int($editingGroupId ?? null) ? $editingGroupId : null;
$editingCategoryId = is_int($editingCategoryId ?? null) ? $editingCategoryId : null;

$categoriesById = [];
foreach ($categories as $category) {
    $categoryId = $category->id();

    if ($categoryId !== null) {
        $categoriesById[$categoryId] = $category;
    }
}
?>
<section class="admin__stack" aria-label="Category management">
    <header class="admin-page__header">
        <div>
            <h2 class="admin-page__title">Categories</h2>
            <p class="admin-page__subtitle">Manage category groups on the left and nested categories in the selected group on the right.</p>
        </div>
    </header>

    <?php if (isset($groupSuccess) && is_string($groupSuccess) && $groupSuccess !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($groupSuccess) ?></span></p>
    <?php endif; ?>
    <?php if (isset($groupError) && is_string($groupError) && $groupError !== ''): ?>
        <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e($groupError) ?></span></p>
    <?php endif; ?>
    <?php if (isset($categorySuccess) && is_string($categorySuccess) && $categorySuccess !== ''): ?>
        <p role="status"><span class="admin-badge admin-badge--success"><?= $e($categorySuccess) ?></span></p>
    <?php endif; ?>
    <?php if (isset($categoryError) && is_string($categoryError) && $categoryError !== ''): ?>
        <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e($categoryError) ?></span></p>
    <?php endif; ?>

    <section class="admin-category-manager" aria-label="Category manager layout">
        <article class="admin-panel admin-card admin-category-manager__groups">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Category Groups</h3>
                <p class="admin-card__meta">Select a group to manage its category tree.</p>
            </div>

            <ul class="admin-category-group-list" aria-label="Category group list">
                <?php foreach ($groups as $group): ?>
                    <?php $groupId = $group->id(); ?>
                    <?php if ($groupId === null): continue; endif; ?>
                    <li>
                        <a
                            class="admin-category-group-list__link<?= $selectedGroupId === $groupId ? ' admin-category-group-list__link--active' : '' ?>"
                            href="/admin/categories?group=<?= $e((string) $groupId) ?>"
                            <?= $selectedGroupId === $groupId ? 'aria-current="page"' : '' ?>
                        >
                            <span><?= $e($group->name()) ?></span>
                            <code><?= $e($group->slug()->value()) ?></code>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="admin-category-manager__forms">
                <h4 class="admin-category-manager__subheading">Create Group</h4>
                <form class="admin-form" method="post" action="/admin/categories/groups/create" novalidate>
                    <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="group_name">Name</label>
                        <input class="admin-form__input" id="group_name" name="name" type="text" required>
                    </div>
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="group_slug">Slug</label>
                        <input class="admin-form__input" id="group_slug" name="slug" type="text" required>
                    </div>
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="group_description">Description</label>
                        <textarea class="admin-form__input" id="group_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="admin-form__actions">
                        <button class="admin-action admin-action--primary" type="submit">Create Group</button>
                    </div>
                </form>

                <?php if ($selectedGroupId !== null && isset($selectedGroup) && $selectedGroup instanceof CategoryGroup): ?>
                    <div class="admin-category-manager__divider"></div>
                    <h4 class="admin-category-manager__subheading">Edit Group</h4>
                    <form class="admin-form" method="post" action="/admin/categories/groups/<?= $e((string) $selectedGroupId) ?>/edit" novalidate>
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="group_edit_name">Name</label>
                            <input class="admin-form__input" id="group_edit_name" name="name" type="text" required value="<?= $e($selectedGroup->name()) ?>">
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="group_edit_slug">Slug</label>
                            <input class="admin-form__input" id="group_edit_slug" name="slug" type="text" required value="<?= $e($selectedGroup->slug()->value()) ?>">
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="group_edit_description">Description</label>
                            <textarea class="admin-form__input" id="group_edit_description" name="description" rows="3"><?= $e((string) ($selectedGroup->description() ?? '')) ?></textarea>
                        </div>
                        <div class="admin-form__actions admin-category-manager__actions-split">
                            <button class="admin-action admin-action--primary" type="submit">Save Group</button>
                        </div>
                    </form>

                    <form method="post" action="/admin/categories/groups/<?= $e((string) $selectedGroupId) ?>" class="admin-category-manager__delete-form">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <button class="admin-action admin-action--danger" type="submit">Delete Group</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>

        <article class="admin-panel admin-card admin-category-manager__categories">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Categories</h3>
                <p class="admin-card__meta">
                    <?php if (isset($selectedGroup) && $selectedGroup instanceof CategoryGroup): ?>
                        Group: <strong><?= $e($selectedGroup->name()) ?></strong>
                    <?php else: ?>
                        Select or create a category group to manage categories.
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!isset($selectedGroup) || !$selectedGroup instanceof CategoryGroup || $selectedGroupId === null): ?>
                <p class="admin-form__help">No category group selected.</p>
            <?php else: ?>
                <div class="admin-table admin-table--categories">
                    <div class="admin-table__header admin-table__row--categories">
                        <div class="admin-table__cell">Category</div>
                        <div class="admin-table__cell">Slug</div>
                        <div class="admin-table__cell">Sort</div>
                        <div class="admin-table__cell">Description</div>
                        <div class="admin-table__cell">Actions</div>
                    </div>
                    <?php if ($categoryRows === []): ?>
                        <div class="admin-table__row admin-table__row--empty">
                            <div class="admin-table__cell">No categories in this group yet.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categoryRows as $row): ?>
                            <?php $category = $row['category']; ?>
                            <?php $categoryId = $category->id(); ?>
                            <?php if ($categoryId === null): continue; endif; ?>
                            <div class="admin-table__row admin-table__row--categories">
                                <div class="admin-table__cell">
                                    <span class="admin-category-tree__item admin-category-tree__depth-<?= $e((string) min($row['depth'], 6)) ?>">
                                        <?= $e($category->name()) ?>
                                    </span>
                                </div>
                                <div class="admin-table__cell"><code><?= $e($category->slug()->value()) ?></code></div>
                                <div class="admin-table__cell"><?= $e((string) $category->sortOrder()) ?></div>
                                <div class="admin-table__cell"><?= $e((string) ($category->description() ?? '—')) ?></div>
                                <div class="admin-table__cell">
                                    <div class="admin-actions admin-actions--table">
                                        <a class="admin-action admin-action--secondary" href="/admin/categories?group=<?= $e((string) $selectedGroupId) ?>&edit_category=<?= $e((string) $categoryId) ?>">Edit</a>
                                        <form method="post" action="/admin/categories/<?= $e((string) $categoryId) ?>">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                                            <button class="admin-action admin-action--danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="admin-category-manager__forms">
                    <h4 class="admin-category-manager__subheading">Create Category</h4>
                    <form class="admin-form" method="post" action="/admin/categories/create" novalidate>
                        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                        <input type="hidden" name="group_id" value="<?= $e((string) $selectedGroupId) ?>">
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="category_name">Name</label>
                            <input class="admin-form__input" id="category_name" name="name" type="text" required>
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="category_slug">Slug</label>
                            <input class="admin-form__input" id="category_slug" name="slug" type="text" required>
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="category_parent">Parent Category</label>
                            <select class="admin-form__input" id="category_parent" name="parent_id">
                                <option value="">None</option>
                                <?php foreach ($categoryRows as $row): ?>
                                    <?php $optionCategory = $row['category']; ?>
                                    <?php $optionCategoryId = $optionCategory->id(); ?>
                                    <?php if ($optionCategoryId === null): continue; endif; ?>
                                    <option value="<?= $e((string) $optionCategoryId) ?>">
                                        <?= $e(str_repeat('— ', $row['depth']) . $optionCategory->name()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="category_sort_order">Sort Order</label>
                            <input class="admin-form__input" id="category_sort_order" name="sort_order" type="number" value="0">
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="category_description">Description</label>
                            <textarea class="admin-form__input" id="category_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="admin-form__actions">
                            <button class="admin-action admin-action--primary" type="submit">Create Category</button>
                        </div>
                    </form>

                    <?php if ($editingCategoryId !== null && isset($categoriesById[$editingCategoryId])): ?>
                        <?php $editingCategory = $categoriesById[$editingCategoryId]; ?>
                        <div class="admin-category-manager__divider"></div>
                        <h4 class="admin-category-manager__subheading">Edit Category</h4>
                        <form class="admin-form" method="post" action="/admin/categories/<?= $e((string) $editingCategoryId) ?>/edit" novalidate>
                            <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="category_edit_name">Name</label>
                                <input class="admin-form__input" id="category_edit_name" name="name" type="text" required value="<?= $e($editingCategory->name()) ?>">
                            </div>
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="category_edit_slug">Slug</label>
                                <input class="admin-form__input" id="category_edit_slug" name="slug" type="text" required value="<?= $e($editingCategory->slug()->value()) ?>">
                            </div>
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="category_edit_parent">Parent Category</label>
                                <select class="admin-form__input" id="category_edit_parent" name="parent_id">
                                    <option value="">None</option>
                                    <?php foreach ($categoryRows as $row): ?>
                                        <?php $optionCategory = $row['category']; ?>
                                        <?php $optionCategoryId = $optionCategory->id(); ?>
                                        <?php if ($optionCategoryId === null || $optionCategoryId === $editingCategoryId): continue; endif; ?>
                                        <option value="<?= $e((string) $optionCategoryId) ?>" <?= $editingCategory->parentId() === $optionCategoryId ? 'selected' : '' ?>>
                                            <?= $e(str_repeat('— ', $row['depth']) . $optionCategory->name()) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="category_edit_sort_order">Sort Order</label>
                                <input class="admin-form__input" id="category_edit_sort_order" name="sort_order" type="number" value="<?= $e((string) $editingCategory->sortOrder()) ?>">
                            </div>
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="category_edit_description">Description</label>
                                <textarea class="admin-form__input" id="category_edit_description" name="description" rows="3"><?= $e((string) ($editingCategory->description() ?? '')) ?></textarea>
                            </div>
                            <div class="admin-form__actions">
                                <button class="admin-action admin-action--primary" type="submit">Save Category</button>
                                <a class="admin-action admin-action--secondary" href="/admin/categories?group=<?= $e((string) $selectedGroupId) ?>">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>
</section>
