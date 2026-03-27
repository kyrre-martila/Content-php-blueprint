# Content model

This document describes the **currently implemented** content model and clearly separates planned expansion.

## Current model

### Core records

- `content_types`
  - `slug` (machine name)
  - `name` (label)
  - `description` (currently used as default template path storage)
- `content_items`
  - `content_type_id`
  - `title`
  - `slug`
  - `status` (`draft` or `published`)
  - `pattern_blocks` JSON
  - timestamps

### Domain objects

- `ContentType` (`name`, `label`, `defaultTemplate`)
- `ContentItem` (type, title, slug, status, timestamps, pattern blocks)
- `Slug` value object for URL-safe slug validation
- `ContentStatus` enum-like domain type

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

Content pages resolve template candidates in this order:

1. `templates/pages/{normalized-slug}.php`
2. `templates/pages/content.php`
3. `templates/default.php`

## Current editing boundaries

- Admin content screens manage structured content records and pattern block payload.
- Inline Editor Mode updates only:
  - `ContentItem` title
  - `text`/`textarea` fields in registered pattern blocks
- Template/theme source is out of scope for Editor Mode.

## Planned direction (not yet implemented)

- Dedicated storage for richer field-value models beyond current pattern block payload.
- Additional content type definitions and field tooling generated through reusable workflows.
