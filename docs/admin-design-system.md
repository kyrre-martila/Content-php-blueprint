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

## Reusable admin table system

### `admin-table` usage

Use `.admin-table` as a generic container and compose rows/cells with BEM elements:

- `.admin-table__header` for the heading row.
- `.admin-table__row` for each data row.
- `.admin-table__cell` for each column cell.
- `.admin-table__actions` to group action controls in a cell.
- `.admin-table__row--empty` for an empty-state row.
- `.admin-table--compact` to reduce table density.

The table pattern is intentionally feature-agnostic so the same structure can be reused across any admin list (content, collections, users, settings, and future entities).

### `admin-badge` usage

Status labels use `.admin-badge` with semantic modifiers:

- `.admin-badge--success`
- `.admin-badge--warning`
- `.admin-badge--danger`
- `.admin-badge--muted`

Badge colors come from existing semantic and neutral design tokens only.

### `admin-action` usage

Inline/list row actions use `.admin-action` and optional variants:

- `.admin-action--primary`
- `.admin-action--secondary`
- `.admin-action--danger`

These actions are designed for table rows but can be reused in any compact admin control area.

## Summary

- **Components created:** reusable table block/elements (`admin-table` family), status badges (`admin-badge--muted`), and row action controls (`admin-action` family).
- **Selectors added:** `.admin-badge--muted`, `.admin-action`, `.admin-action--primary`, `.admin-action--secondary`, `.admin-action--danger`, `.admin-table__header`, `.admin-table__row`, `.admin-table__cell`, `.admin-table__actions`, `.admin-table--compact`, `.admin-table__row--empty`.
- **Reusable table capabilities:** row hover states, compact density mode, semantic badges, reusable action buttons, explicit empty-state rows, and mobile-friendly wrapping to minimize horizontal scrolling.
