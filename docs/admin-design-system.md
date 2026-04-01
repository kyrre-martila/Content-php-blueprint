# Admin Design System

This document defines how the admin UI stylesheet system works today and how contributors should extend it.

## Current implementation

### CSS file structure and required load order

Admin CSS is loaded from `templates/layouts/admin.php` in this order:

1. `public/assets/css/admin/admin-tokens.css`
2. `public/assets/css/admin/admin-shell.css`
3. `public/assets/css/admin/admin-components.css`
4. `public/assets/css/admin/admin-screens.css`

Load order is contractual: later layers may consume earlier-layer tokens/primitives but should not redefine earlier-layer responsibilities.

### Layer ownership

#### 1) `admin-tokens.css`

Owns global CSS custom properties (`:root`), including:

- color tokens (`--admin-color-*`)
- spacing/radius/shadow scales
- typography tokens
- shell sizing + motion tokens

#### 2) `admin-shell.css`

Owns admin layout/chrome:

- root admin context
- shell grid/workspace/content regions
- sidebar/topbar structure
- responsive shell behavior

#### 3) `admin-components.css`

Owns reusable UI primitives:

- cards/panels/stats
- inputs/buttons/badges
- tables/forms/shared utility patterns

#### 4) `admin-screens.css`

Owns page-specific variants:

- dashboard screen styles
- template manager styles
- category manager styles
- relationship manager styles

### Usage rules

- prefer existing tokens before introducing literal values
- add new design tokens only in `admin-tokens.css`
- keep structural rules in shell layer, reusable rules in components, and page-specific rules in screens
- use existing naming convention (`admin-*`, `__`, `--`)

### Authoring workflow

When adding or changing admin UI:

1. Add/update token(s) in `admin-tokens.css` if necessary.
2. Apply structural layout changes in `admin-shell.css`.
3. Add reusable primitives in `admin-components.css`.
4. Add screen-only styles in `admin-screens.css`.

### Reusable form collection pattern

When an admin form needs repeatable rows (for example Content Type field schemas), use the shared collection pattern:

- wrapper: `.admin-field-schema`
- list container: `.admin-field-schema__list`
- row container: `.admin-field-schema__item`
- row actions: `.admin-field-schema__actions`
- two-column setting pair: `.admin-field-schema__pair`

Behavior guidelines:

- keep add/remove/reorder interactions in vanilla JavaScript
- keep server as source of truth (all rows posted back via standard inputs)
- use `data-*` hooks for row actions and visibility toggles
- do not use inline styles; place reusable styling in `admin-components.css`

---

## Future roadmap (not implemented)

Potential future design-system work:

- design token documentation generation
- visual regression checks for admin screens
- stricter linting/validation for layer ownership
