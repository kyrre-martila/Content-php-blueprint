# Pattern system

Patterns are reusable presentation units stored on disk and rendered through explicit runtime services.

## Filesystem layout

Each pattern lives at:

- `patterns/{pattern-key}/pattern.json`
- `patterns/{pattern-key}/pattern.php`

`pattern.json` provides registry metadata. `pattern.php` is the deterministic runtime template path.

## Metadata and deterministic registry

`PatternRegistry` scans `patterns/` deterministically by:

1. reading only immediate child directories
2. sorting discovered directories lexicographically
3. parsing `pattern.json` safely
4. validating metadata with `PatternMetadata::fromArray()`
5. indexing valid patterns by `key`
6. sorting final keys lexicographically

Invalid pattern metadata is ignored safely and does not crash application boot.

## Runtime renderer usage

Runtime rendering now flows through `PatternRenderer` and only uses registry-known patterns.

Flow:

1. Template calls `TemplateRenderer::renderPattern($key, $data)`.
2. `PatternRenderer` resolves metadata and template path from `PatternRegistry`.
3. Unknown patterns or missing template files return empty output.
4. Data is validated by `PatternDataValidator` before template include.
5. The renderer includes only the deterministic `pattern.php` path for the resolved pattern.

No arbitrary include paths are accepted.

## Validation flow

`PatternDataValidator` enforces conservative v1 field behavior:

- only declared metadata fields are allowed
- unknown input keys are rejected
- only `text` and `textarea` field types are accepted for runtime rendering
- values must be scalar or null and are converted to strings
- missing declared fields are defaulted to empty strings

If validation fails, rendering fails safely and returns empty output.

## Admin discovery endpoint

A new authenticated endpoint exposes runtime-discovered patterns:

- `GET /admin/patterns`
- returns JSON from `PatternRegistry`
- each item includes: `key`, `name`, `description`

Purpose:

- future editor insertion UI
- future developer tooling
- future AI tooling integration

## Current boundaries

Out of scope in this phase:

- insertion UI
- drag-and-drop ordering UI
- pattern preview UI
- pattern category/group management
- richer field type rendering
