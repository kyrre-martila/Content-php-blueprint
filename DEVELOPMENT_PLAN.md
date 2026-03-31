# DEVELOPMENT_PLAN.md

Content PHP Blueprint

## Purpose

This document is the implementation-oriented source of truth for:

1. **Current runtime behavior** (implemented now), and
2. **Future roadmap** (planned, not yet runtime behavior).

If code and this document diverge, update this document to match code.

---

## Current implementation

### 1) Runtime routing model

Route registration order is deterministic and priority-sensitive:

1. `SystemRouteRegistrar`
2. `AuthRouteRegistrar`
3. `AdminRouteRegistrar`
4. `DevModeRouteRegistrar`
5. `EditorModeRouteRegistrar`
6. `PublicContentRouteRegistrar` (**must remain last**)

Public routes currently relevant to rendering:

- `GET /{slug}` -> `ContentController::show`
- `GET /categories/{groupSlug}/{categorySlug}` -> `ContentController::showCategoryCollection`

Category routing behavior:

- resolve category group by `groupSlug`
- resolve category by `categorySlug` within that group
- 404 if either lookup fails
- render category collection template even when result set is empty

Install-aware kernel behavior:

- `/admin` and `/admin/*` redirect to `/install` while installation is incomplete

### 2) Template resolution model

Runtime template selection is deterministic and intentionally simple.

- single content:
  - `templates/content/{content_type}.php`
  - fallback `templates/index.php`
- content-type collection:
  - `templates/collections/{content_type}.php`
  - fallback `templates/system/404.php`
- category collection:
  - `templates/collections/categories/{group_slug}.php`
  - fallback `templates/system/404.php`
- system route:
  - `templates/system/{route}.php`
  - fallback `templates/system/404.php`

Not implemented in runtime selection:

- WordPress-style hierarchy chains
- slug-specific template hierarchy
- editor-selected template switching
- item-level template override system

### 3) Collection rendering model

Collection pages (content-type and category) share contract and query rules:

- query params: `page`, `perPage`
- positive integer parsing with defaults (`page=1`, `perPage=20`)
- template context includes:
  - `collectionItems`
  - `pagination` (`totalCount`, `currentPage`, `perPage`, `offset`, `totalPages`)
  - `totalCount`, `currentPage`, `perPage`

Category collection extras:

- `categoryGroup`
- `category`
- `breadcrumbs`
- `contentItem = null` (contract consistency)

### 4) Content organization boundaries

These systems are distinct:

- **Hierarchy**: `content_items.parent_id` + `sort_order`
- **Categories**: `content_item_categories` + `category_groups`/`categories`
- **Relationships**: `content_item_relationships` constrained by `content_type_relationship_rules`

Category-specific notes:

- categories support optional nesting via `categories.parent_id`
- category-group availability per content type is enforced via `content_type_category_groups`

### 5) Trusted proxy and security behavior

`TRUSTED_PROXIES` -> `config/security.php` -> `security.trusted_proxies`

- empty trusted proxy config: use `REMOTE_ADDR`
- configured trusted proxies: trust `X-Forwarded-For` only when immediate `REMOTE_ADDR` is trusted

This directly affects login rate limiting and abuse controls.

### 6) Release artifact and runtime-state behavior

Current release artifact flow uses `scripts/build-release.sh`.

- release zip includes runtime code + `vendor/`
- runtime directories are created in artifact and also ensured at bootstrap by `RuntimeStorage`
- deploy/upgrades must preserve `.env` and `storage/`

### 7) Admin design system usage

Admin styles are modular and loaded in strict order from `templates/layouts/admin.php`:

1. `admin-tokens.css`
2. `admin-shell.css`
3. `admin-components.css`
4. `admin-screens.css`

Ownership model:

- tokens in `admin-tokens.css`
- structural layout in `admin-shell.css`
- reusable primitives in `admin-components.css`
- page-level variants in `admin-screens.css`

---

## Future roadmap (not current runtime behavior)

- GitHub-driven in-admin updater orchestration:
  - release discovery
  - artifact download
  - safe file replacement orchestration
  - rollback workflows
- broader deployment ergonomics around current artifact deployment
- richer SEO and metadata automation
- extended upgrade-task hooks in `UpgradeRunner`

Roadmap items must not be documented as current runtime behavior until implemented.
