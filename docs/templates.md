# Template Variables

This document defines the runtime variables available to public content templates.

## Single content templates

Single templates (used when a content type has `view_type = single`) receive:

- `$contentItem` (`App\Domain\Content\ContentItem`): the published item being rendered.
- `$request` (`App\Http\Request`): incoming request object.
- `$slug` (`string`): requested slug.
- `$patternBlocks` (`array`): pattern block payload from the item.
- `$meta` (`array{noindex: bool}`): metadata flags for rendering.
- `$editorModeActive` (`bool`): whether editor mode is currently active.
- `$editorCanUse` (`bool`): whether current actor can use editor mode.

## Collection content templates

Collection templates (used when a content type has `view_type = collection`) receive all single-template variables, plus:

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
