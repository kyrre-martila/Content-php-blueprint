# Template System

This document defines the **current runtime template resolution model** and template context contracts.

## Current implementation

### Template categories and resolution order

#### 1) Single content templates (`/{slug}` for non-collection content types)

1. `templates/content/{content_type}.php`
2. `templates/index.php` (fallback)

#### 2) Content-type collection templates (`/{slug}` for collection-view content types)

1. `templates/collections/{content_type}.php`
2. `templates/system/404.php` (fallback)

#### 3) Category collection templates (`/categories/{groupSlug}/{categorySlug}`)

1. `templates/collections/categories/{group_slug}.php`
2. `templates/system/404.php` (fallback)

Routing/lookup behavior:

- `groupSlug` must resolve to an existing category group.
- `categorySlug` must resolve within that group.
- Missing group/category renders system 404.
- Resolved category with zero published items still renders the category collection template.

#### 4) System templates

1. `templates/system/{route}.php`
2. `templates/system/404.php` (fallback)

---

## Template data contracts (current runtime)

### Single content templates

Available variables:

- `$contentItem` (`App\Domain\Content\ContentItem`)
- `$request` (`App\Http\Request`)
- `$slug` (`string`)
- `$patternBlocks` (`list<mixed>`)
- `$meta` (`array{noindex: bool}`)
- `$editorModeActive` (`bool`)
- `$editorCanUse` (`bool`)
- `$fileFieldResolver` (`App\Application\Files\ContentItemFileFieldResolver`)


### Reading structured content type fields in templates

`$contentItem` now exposes schema-driven field values:

- `$contentItem->fieldValues(): array<string,mixed>` returns all known fields for the item type.
  - Missing stored keys are backfilled with field defaults (if configured) or `null`.
- `$contentItem->fieldValue('field_name')` returns one value or `null`.

Example:

```php
$summary = $contentItem->fieldValue('summary');
$isFeatured = (bool) ($contentItem->fieldValue('is_featured') ?? false);
$publishDate = $contentItem->fieldValue('publish_date'); // YYYY-MM-DD or null
```

Values are already normalized by the admin save flow and validation layer.

For `image`/`file` fields, stored values are file IDs (`int`) in v1. Resolve them when needed:

```php
$heroImageId = $contentItem->fieldValue('hero_image'); // int|null (or legacy string on old rows)
$heroImage = $fileFieldResolver->resolveField($contentItem, 'hero_image'); // ?FileAsset

if ($heroImage !== null) {
    echo $heroImage->originalName();
}
```

Backward compatibility note:

- Some legacy content rows may still contain URL/string values for `image`/`file` fields.
- Use `$fileFieldResolver->isLegacyValue($contentItem->fieldValue('hero_image'))` if you need to branch rendering logic during migration.

### Content-type collection templates

Available variables:

- `$contentItem` (`App\Domain\Content\ContentItem`) — the collection-page content record resolved by slug.
- `$request` (`App\Http\Request`)
- `$slug` (`string`)
- `$patternBlocks` (`list<mixed>`)
- `$meta` (`array{noindex: bool}`)
- `$collectionItems` (`list<App\Domain\Content\ContentItem>`, may be empty)
- `$pagination` (`array{totalCount: int, currentPage: int, perPage: int, offset: int, totalPages: int}`)
- `$totalCount` (`int`)
- `$currentPage` (`int`)
- `$perPage` (`int`)
- `$editorModeActive` (`bool`)
- `$editorCanUse` (`bool`)

### Category collection templates

Available variables:

- `$categoryGroup` (`App\Domain\Content\CategoryGroup`)
- `$category` (`App\Domain\Content\Category`)
- `$collectionItems` (`list<App\Domain\Content\ContentItem>`, may be empty)
- `$pagination` (`array{totalCount: int, currentPage: int, perPage: int, offset: int, totalPages: int}`)
- `$totalCount` (`int`)
- `$currentPage` (`int`)
- `$perPage` (`int`)
- `$breadcrumbs` (`list<array{label: string, url: string}>`)
- `$request` (`App\Http\Request`)
- `$editorModeActive` (`bool`)
- `$editorCanUse` (`bool`)

`$contentItem` is **absent** in category collection templates (not set to `null`).

### Shared collection query params

- `page` (default `1`)
- `perPage` (default `20`)

Only positive integers are accepted; invalid values fall back to defaults.

Note: breadcrumb links include `/categories` and `/categories/{groupSlug}` entries for UX consistency, but only `/categories/{groupSlug}/{categorySlug}` is currently registered as a public category collection route.

---

## Explicit non-features (current runtime)

The following are **not** runtime template-selection behavior:

- WordPress-style hierarchy chains
- slug-specific template hierarchy resolution
- editor-driven template switching
- item-level template override selection

Template dispatch remains deterministic and code-driven.

---

## Future roadmap (not implemented)

Potential future enhancements (not active runtime behavior):

- additional category landing routes (`/categories`, `/categories/{groupSlug}`)
- expanded system-template route catalog
- richer template tooling and diagnostics in admin/dev surfaces
