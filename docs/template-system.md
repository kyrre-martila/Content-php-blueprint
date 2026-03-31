# Template System

## v1 foundation

Template System v1 is intentionally deterministic and minimal.

Implemented template structure:

```text
templates/
  index.php
  content/
    {content_type}.php
  collections/
    {content_type}.php
    categories/
      {group_slug}.php
  system/
    {route}.php
    404.php
```

### Rendering model

- Content routes try `templates/content/{content_type}.php` first, then fall back to `templates/index.php`.
- Collection routes try `templates/collections/{content_type}.php` first, then fall back to `templates/system/404.php`.
- Category collection routes try `templates/collections/categories/{group_slug}.php` first, then fall back to `templates/system/404.php`.
- System routes try `templates/system/{route}.php` first, then fall back to `templates/system/404.php`.
- Pattern blocks remain the primary mechanism for page structure within content templates.

### Intentional exclusions in v1

Template System v1 does **not** implement a WordPress-style hierarchy.

Not included:

- slug templates
- per-content-item template overrides
- editor-selected template switching
- dynamic arbitrary include chains

### Deterministic resolver behavior

`TemplateResolver` maps routes to templates with explicit methods:

- `resolveContentTemplate(ContentType $type)` → `templates/content/{content_type}.php`, then `templates/index.php`
- `resolveCollectionTemplate(ContentType $type)` → `templates/collections/{content_type}.php`, then `templates/system/404.php`
- `resolveCategoryCollectionTemplate(CategoryGroup $group)` → `templates/collections/categories/{group_slug}.php`, then `templates/system/404.php`
- `resolveSystemTemplate(string $route)` → `templates/system/{route}.php`, then `templates/system/404.php`
- `resolveNotFound()` → `resolveSystemTemplate('404')`

### Rendering coordination guardrail

- `TemplateRenderer` is the render coordinator, not a mini-framework or feature dump.
- SEO/social head tags are rendered by `SeoMetaRenderer`.
- JSON-LD/schema output is rendered by `StructuredDataRenderer`.
- Future cross-cutting rendering behavior must follow the same pattern: add a dedicated renderer service and delegate from `TemplateRenderer`.
- Keep behavior explicit and lightweight: no templating engine, no service container requirement.
