# Template System

## v1 foundation

Template System v1 is intentionally deterministic and minimal.

Implemented template structure:

```text
templates/
  index.php
  system/
    404.php
    search.php
```

### Rendering model

- `templates/index.php` is the universal renderer for content-driven pages.
- `templates/system/404.php` is the dedicated renderer for not-found responses.
- `templates/system/search.php` is the dedicated renderer for search responses.
- Pattern blocks remain the primary mechanism for page structure within content templates.

### Intentional exclusions in v1

Template System v1 does **not** implement a WordPress-style hierarchy.

Not included:

- slug templates
- content-type templates
- editor-selected template switching
- dynamic arbitrary include chains

### Deterministic resolver behavior

`TemplateResolver` maps routes to templates with explicit methods:

- `resolveContentTemplate()` → `templates/index.php`
- `resolveNotFound()` → `templates/system/404.php`
- `resolveSystemTemplate('search')` → `templates/system/search.php`

System template resolution is deterministic: the resolver checks `templates/system/{name}.php` and falls back to `templates/system/404.php` if the requested system template file does not exist.
