# Pattern system

Patterns are reusable presentation units stored on disk and rendered deterministically.

## Current implementation

## Filesystem model

Each pattern lives at:

- `patterns/{slug}/pattern.json`
- `patterns/{slug}/pattern.php`

`PatternRegistry` scans pattern directories, validates metadata, and registers valid patterns by slug.

## Metadata model (current)

`pattern.json` currently requires:

- `name` (string)
- `slug` (kebab-case)
- `description` (string)
- `fields` (array of `{name, type}`)

Allowed field types in current validator:

- `text`
- `textarea`
- `image`

## Rendering model (current)

- `PatternRenderer` accepts pattern slug + data.
- Only declared scalar fields are passed into template `$fields`.
- Unknown or non-scalar values are dropped/coerced to safe defaults.
- Pattern view file is included through controlled output buffering.

## Editor usage (current)

- Content admin forms read available patterns from `PatternRegistry`.
- Editors compose ordered `pattern_blocks` per content item.
- Editor Mode v1 supports inline editing for pattern `text` and `textarea` fields only.
- Inline saves are validated against pattern metadata before repository persistence.

## Storage model (current)

Pattern usage is persisted per content item in `pattern_blocks` JSON payload:

- block references pattern slug
- block data stores field values by field name

## Planned direction (not yet implemented)

- Versioning/migration strategy for pattern schema evolution.
- Additional safe field types and validation constraints.
- Stronger tooling for AI-assisted pattern generation and upgrade paths.
