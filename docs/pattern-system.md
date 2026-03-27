# Pattern system

Patterns are reusable presentation units stored on disk.

## Pattern metadata and registry foundation

The current implementation introduces a metadata contract and deterministic registry discovery, without changing the high-level runtime wiring yet.

### Filesystem layout

Each pattern lives at:

- `patterns/{pattern-key}/pattern.json`
- `patterns/{pattern-key}/pattern.php`

Only `pattern.json` is required for metadata discovery. `pattern.php` is used by the existing renderer when present.

### `pattern.json` structure

Each `pattern.json` must contain:

- `name` (non-empty string)
- `key` (non-empty string)
- `description` (non-empty string)
- `fields` (array)

Each `fields` entry must contain:

- `name` (non-empty string)
- `type` (one of `text`, `textarea`, `image`)

Current editor/runtime support is focused on:

- `text`
- `textarea`

`image` is accepted in metadata to keep the model future-ready.

### Metadata model

`PatternMetadata` is an immutable value object that:

- validates required metadata structure at creation time
- exposes getters only (`name()`, `key()`, `description()`, `fields()`)
- provides normalized metadata via `toArray()` for view consumption

### Deterministic discovery

`PatternRegistry` scans `patterns/` deterministically by:

1. reading only immediate child directories
2. sorting discovered directories lexicographically
3. parsing `pattern.json` safely
4. validating metadata with `PatternMetadata::fromArray()`
5. indexing valid patterns by `key`
6. sorting final keys lexicographically

### Failure handling

Registry loading is conservative and non-fatal:

- missing `pattern.json` -> pattern ignored
- unreadable/invalid JSON -> pattern ignored
- invalid metadata shape or unsupported field types -> pattern ignored
- malformed pattern never crashes application boot

### Why metadata exists

Pattern metadata makes pattern discovery explicit and machine-readable, which is required for:

- deterministic pattern listing in admin/editor surfaces
- future schema-aware data validation
- future runtime rendering integration improvements
- future admin discovery and insertion UX
