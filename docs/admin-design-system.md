# Admin Design System (Foundation)

This document describes how admin UI styles are organized in the modular stylesheet stack under `public/assets/css/admin/`.

## File structure and load order

Admin CSS is split into four layers and loaded in this order:

1. `admin-tokens.css`
2. `admin-shell.css`
3. `admin-components.css`
4. `admin-screens.css`

Load order matters: each layer can rely on variables/selectors defined in previous layers, but should not redefine concerns owned by earlier layers.

### 1) `admin-tokens.css`

Owns all global CSS custom properties (`:root`):

- Color primitives (`--admin-color-*`) for canvas, surfaces, text, borders, accents, and semantic states.
- Spacing scale (`--admin-space-*`).
- Radius scale (`--admin-radius-*`).
- Shadow scale (`--admin-shadow-*`).
- Typography scale (`--admin-font-*`).
- Shell sizing and motion tokens (`--admin-sidebar-width`, `--admin-topbar-height`, `--admin-motion-*`).

### 2) `admin-shell.css`

Owns admin layout and structural chrome:

- Root admin context and base box model.
- Main shell grid (`.admin__layout`, `.admin__workspace`, `.admin__content`).
- Sidebar shell and navigation scaffolding.
- Topbar shell and utility region.
- Shell-responsive behavior (desktop/sidebar and mobile/topbar flow).

### 3) `admin-components.css`

Owns reusable primitives and shared building blocks:

- Panels/cards/stats and utility stacks.
- Buttons, actions, badges, inputs.
- Reusable table system (`admin-table` block/elements/modifiers).
- Forms and form utilities.
- Shared list/action/section composition helpers.

### 4) `admin-screens.css`

Owns screen-level variants and page-specific styling:

- Dashboard selectors (`.admin-dashboard*`).
- Template manager selectors (`.admin-template-manager*`, template row variants).
- Category manager selectors (`.admin-category-*`, category row/tree variants).
- Relationship manager selectors (`.admin-relationship-*`, relationship row variants).
- Screen-specific responsive adjustments.

## Token usage rules

- Always use existing tokens before introducing literal values.
- Add new tokens only in `admin-tokens.css`.
- Prefer semantic token names (`--admin-color-danger`) over hard-coded hex values in component/screen files.
- Keep component and screen files token-driven; no ad-hoc design constants.

## Naming patterns

The admin CSS follows a BEM-inspired convention:

- `admin-*` prefixes admin-specific blocks (`.admin-panel`, `.admin-btn`, `.admin-table`).
- Elements use `__` (`.admin-panel__title`, `.admin-table__cell`).
- Modifiers use `--` (`.admin-btn--primary`, `.admin-table--compact`).

## Authoring guidance

When adding/updating admin UI:

1. Add/adjust primitives in `admin-tokens.css` only if needed.
2. Put structural layout/chrome changes in `admin-shell.css`.
3. Put reusable UI patterns in `admin-components.css`.
4. Keep page-specific selectors in `admin-screens.css`.

This keeps concerns isolated, reduces cascade conflicts, and makes long-term admin styling maintenance predictable.
