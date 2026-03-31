# Template System

This document defines runtime template categories, fallback behavior, and template variables.

## Template categories

### 1) Content templates (single-item routes)

Used for content types with `view_type = single`.

Resolver order:

1. `templates/content/{content_type}.php`
2. `templates/index.php` (index fallback)

### 2) Collection templates (content-type collection routes)

Used for content types with `view_type = collection`.

Resolver order:

1. `templates/collections/{content_type}.php`
2. `templates/system/404.php`

### 3) Category collection templates (category-group collection routes)

Used for category collection pages such as:

- `/categories/blog/news`
- `/categories/locations/kirkenes`

Resolver order:

1. `templates/collections/categories/{group_slug}.php`
2. `templates/system/404.php`

Important: category collection support is route-level only. There are no item-level template overrides.
Category collection routes are `GET /categories/{groupSlug}/{categorySlug}`.
If either slug does not resolve, runtime renders the system 404 template.
If the category resolves but contains no published items, runtime still renders the category collection template with empty `$collectionItems`.

### 4) System templates

Used for system routes (`search`, `404`, etc.).

Resolver order:

1. `templates/system/{route}.php`
2. `templates/system/404.php`

## Official runtime architecture (resolution order)

This is the enforced runtime template model:

- **Content routes**
  1. `templates/content/{content_type}.php`
  2. `templates/index.php`
- **Content type collection routes**
  1. `templates/collections/{content_type}.php`
  2. `templates/system/404.php`
- **Category collection routes**
  1. `templates/collections/categories/{group_slug}.php`
  2. `templates/system/404.php`
- **System routes**
  1. `templates/system/{route}.php`
  2. `templates/system/404.php`

## Public template variables

### Single content templates

- `$contentItem` (`App\Domain\Content\ContentItem`): the published item being rendered.
- `$request` (`App\Http\Request`): incoming request object.
- `$slug` (`string`): requested slug.
- `$patternBlocks` (`array`): pattern block payload from the item.
- `$meta` (`array{noindex: bool}`): metadata flags for rendering.
- `$editorModeActive` (`bool`): whether editor mode is currently active.
- `$editorCanUse` (`bool`): whether current actor can use editor mode.

### Collection content templates

Collection templates always receive the following collection contract (in addition to the single-template variables):

- `$collectionItems` (`list<App\Domain\Content\ContentItem>`): published sibling items for the same content type, paginated.
- `$totalCount` (`int`): total number of published items available for this content type.
- `$currentPage` (`int`): current one-based page.
- `$perPage` (`int`): page size used for the query.
- `$pagination` (`array`): normalized pagination metadata:
  - `totalCount` (`int`)
  - `currentPage` (`int`)
  - `perPage` (`int`)
  - `offset` (`int`)
  - `totalPages` (`int`)
- `$contentItem` (`App\Domain\Content\ContentItem`): the current collection route item.

Empty collections are rendered without a 404. In that case, `$collectionItems` is an empty list and pagination keys remain present.

Example usage:

```php
<?php /** @var list<App\Domain\Content\ContentItem> $collectionItems */ ?>
<h1><?= htmlspecialchars($contentItem->title(), ENT_QUOTES, 'UTF-8') ?></h1>

<?php if ($collectionItems === []): ?>
    <p>No entries yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($collectionItems as $item): ?>
            <li><a href="/<?= htmlspecialchars($item->slug()->value(), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item->title(), ENT_QUOTES, 'UTF-8') ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p>Page <?= $currentPage ?> of <?= max(1, $pagination['totalPages']) ?></p>
```

Pagination query parameters:

- `page` (default: `1`)
- `perPage` (default: `20`)

Only positive integer values are accepted; invalid values fall back to defaults.

### Category collection templates

Category collection templates receive the same collection contract:

- `$contentItem` (`null`): always `null` for category collections (provided for contract consistency).
- `$collectionItems` (`list<App\Domain\Content\ContentItem>`) — may be empty.
- `$totalCount` (`int`)
- `$currentPage` (`int`)
- `$perPage` (`int`)
- `$pagination` (`array`) with the same shape as collection template pagination metadata.

Plus category-specific context:

- `$request` (`App\Http\Request`)
- `$categoryGroup` (`App\Domain\Content\CategoryGroup`)
- `$category` (`App\Domain\Content\Category`)
- `$breadcrumbs` (`list<array{label: string, url: string}>`) ready for breadcrumb rendering:
  1. `/categories`
  2. `/categories/{groupSlug}`
  3. `/categories/{groupSlug}/{categorySlug}`
- `$editorModeActive` (`bool`)
- `$editorCanUse` (`bool`)

Example usage:

```php
<h1><?= htmlspecialchars($category->name(), ENT_QUOTES, 'UTF-8') ?></h1>
<p>Group: <?= htmlspecialchars($categoryGroup->name(), ENT_QUOTES, 'UTF-8') ?></p>

<?php if ($collectionItems === []): ?>
    <p>No published content in this category yet.</p>
<?php else: ?>
    <?php foreach ($collectionItems as $item): ?>
        <article>
            <h2><a href="/<?= htmlspecialchars($item->slug()->value(), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item->title(), ENT_QUOTES, 'UTF-8') ?></a></h2>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

<nav aria-label="pagination">
    <span><?= $totalCount ?> total items</span>
    <span>Page <?= $currentPage ?></span>
</nav>
```
