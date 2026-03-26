# Content model

The baseline content model is typed and relational.

## Core domain objects

- `ContentType`: name, label, default template path.
- `ContentItem`: id, content type, title, slug, status, timestamps.
- `Slug` value object enforces URL-safe slugs.

## Persistence

- `content_types` stores registered content definitions.
- `content_items` stores content entries.
- Repository interfaces live in `Domain`, MySQL implementations in `Infrastructure`.

## Rendering

Each content item resolves to templates using deterministic fallback in `TemplateResolver`:
1. `templates/pages/{slug}.php`
2. type default template (if defined)
3. `templates/default.php`
