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

## Collection rendering contract (current)

Both content-type collections and category collections receive:

- `$collectionItems` (`list<App\Domain\Content\ContentItem>`, may be empty)
- `$totalCount` (`int`)
- `$currentPage` (`int`)
- `$perPage` (`int`)
- `$pagination` (`array`):
  - `totalCount`
  - `currentPage`
  - `perPage`
  - `offset`
  - `totalPages`

Query params:

- `page` (default `1`)
- `perPage` (default `20`)

Only positive integers are accepted; invalid values fall back to defaults.

### Single-content common variables

- `$contentItem` (`App\Domain\Content\ContentItem` for single/content-type collection; `null` for category collection)
- `$request` (`App\Http\Request`)
- `$editorModeActive` (`bool`)
- `$editorCanUse` (`bool`)

### Category-collection specific variables

- `$categoryGroup` (`App\Domain\Content\CategoryGroup`)
- `$category` (`App\Domain\Content\Category`)
- `$breadcrumbs` (`list<array{label: string, url: string}>`)

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
