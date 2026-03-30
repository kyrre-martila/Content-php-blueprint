# Content model

This document describes the **currently implemented** content model and clearly separates planned expansion.

## Current model

### Core records

- `content_types`
  - `slug` (machine name)
  - `name` (label)
  - `description` (currently used as default template path storage)
  - `view_type` (`single` or `collection`)
- `content_items`
  - `content_type_id`
  - `title`
  - `slug`
  - `status` (`draft` or `published`)
  - `pattern_blocks` JSON
  - timestamps
- `content_type_relationship_rules`
  - `from_content_type_id`
  - `to_content_type_id`
  - `relation_type` (stable identifier; lowercase `a-z`, max 60 chars)
- `content_item_relationships`
  - `from_content_item_id`
  - `to_content_item_id`
  - `relation_type` (must match an allowed rule identifier)
  - `sort_order`
  - timestamps

### Domain objects

- `ContentType` (`name`, `label`, `defaultTemplate`, `viewType`)
- `ContentItem` (type, title, slug, status, timestamps, pattern blocks)
- `Slug` value object for URL-safe slug validation
- `ContentStatus` enum-like domain type
- `ContentRelationship` and `EnrichedContentRelationship` for typed links between items

### Relationship rule identifiers

Relationship `relation_type` values are treated as stable machine identifiers (for example: `author` or `related`), not free-form labels.

Validation constraints:

- required (non-empty after trim)
- maximum length: 60 characters
- pattern: `^[a-z]*$` (lowercase letters only)

### Pattern block storage format (current)

`pattern_blocks` is stored as JSON array entries shaped like:

```json
{
  "pattern": "hero",
  "data": {
    "headline": "Welcome"
  }
}
```

Only scalar field values are persisted in normalized string form.

### Template resolution behavior (current)

Content pages resolve by `ContentType.viewType`:

1. `single` → `templates/content/{content_type}.php`, then `templates/index.php` fallback.
2. `collection` → `templates/collections/{content_type}.php`, then `templates/system/404.php` fallback.

## Current editing boundaries

- Admin content screens manage structured content records and pattern block payload.
- Inline Editor Mode updates only:
  - `ContentItem` title
  - `text`/`textarea` fields in registered pattern blocks
- Template/theme source is out of scope for Editor Mode.

## Planned direction (not yet implemented)

- Dedicated storage for richer field-value models beyond current pattern block payload.
- Additional content type definitions and field tooling generated through reusable workflows.
