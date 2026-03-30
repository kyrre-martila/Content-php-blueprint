# Template System

This document defines runtime template categories, fallback behavior, and template variables.

## Template categories

### 1) Content templates (single-item routes)

Used for content types with `view_type = single`.

Resolver order:

1. `templates/content/{content-type}.php`
2. `templates/index.php` (index fallback)

### 2) Collection templates (content-type collection routes)

Used for content types with `view_type = collection`.

Resolver order:

1. `templates/collections/{content-type}.php`
2. `templates/system/404.php`

### 3) Category collection templates (category-group collection routes)

Used for category collection pages such as:

- `/categories/blog/news`
- `/categories/locations/kirkenes`

Resolver order:

1. `templates/categories/{category-group-slug}.php`
2. `templates/collections/{content-type}.php` (when a relevant content type context is available)
3. `templates/system/404.php`

Important: category collection support is route-level only. There are no item-level category template overrides.

### 4) System templates

Used for system routes (`search`, `404`, etc.).

Resolver order:

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

Collection templates receive all single-template variables, plus:

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

Pagination query parameters:

- `page` (default: `1`)
- `perPage` (default: `20`)

Only positive integer values are accepted; invalid values fall back to defaults.
