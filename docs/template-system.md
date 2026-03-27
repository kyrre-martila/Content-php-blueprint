# Template System

## v1 foundation

Template System v1 is intentionally deterministic and minimal.

Implemented template structure:

```text
templates/
  index.php
  system/
    404.php
```

### Rendering model

- `templates/index.php` is the universal renderer for content-driven pages.
- `templates/system/404.php` is the dedicated renderer for not-found responses.
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

This keeps content rendering deterministic and centralized while leaving room for future system route templates (for example search).
