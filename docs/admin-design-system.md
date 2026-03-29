# Admin Design System (Foundation)

This document describes how admin UI styles should be built using `public/assets/css/admin-main.css`.

## Token usage

`admin-main.css` provides token groups in `:root` for:

- Color primitives (`--admin-color-*`) for canvas, surfaces, text, borders, accent, and semantic states.
- Spacing scale (`--admin-space-*`) for margin, padding, and grid gaps.
- Radius scale (`--admin-radius-*`) for corners and pills.
- Shadow scale (`--admin-shadow-*`) for elevation.
- Typography scale (`--admin-font-size-*` + family token).
- Layout tokens (`--admin-sidebar-width`, `--admin-topbar-height`, `--admin-content-max-width`).

When implementing components, use tokens instead of one-off literal values for spacing, color, radius, and elevation.

## Naming patterns

The admin CSS follows a BEM-inspired convention:

- `admin-*` prefixes all admin-specific blocks (`.admin-panel`, `.admin-btn`, `.admin-nav`).
- Elements use `__` (`.admin-panel__title`, `.admin-stat__value`).
- Variants/modifiers use `--` (`.admin-btn--primary`, `.admin-badge--success`).

This keeps class names predictable and prevents collisions with front-end/site styles.

## Future component guidance

For any new admin screen:

1. Compose screen layout using existing shell classes (`.admin__layout`, `.admin__sidebar`, `.admin__topbar`, `.admin__content`).
2. Reuse base primitives first (`.admin-panel`, `.admin-grid`, `.admin-btn`, `.admin-input`, `.admin-table`, `.admin-badge`).
3. Add new block classes only when needed, and implement with existing tokens.
4. Keep CSS mobile-first; add media queries for enhancements, not overrides of desktop-first assumptions.
