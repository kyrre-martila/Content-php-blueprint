# DEVELOPMENT_PLAN.md

Content PHP Blueprint

## Purpose

This document is the implementation-oriented source of truth for:

1. **Current runtime architecture** (what exists now), and
2. **Future roadmap** (what is planned, not yet runtime behavior).

When code and this document conflict, update this document to match code.

---

## Current architecture summary

- Layered PHP architecture with clear boundaries: `Domain`, `Application`, `Infrastructure`, `Http`, `Admin`.
- Registrar-based routing coordinated by `src/Http/Routing/RouteRegistry.php`.
- Deterministic template resolution through `TemplateResolver`.
- Pattern-driven composition using `pattern_blocks` rendered through `PatternRenderer`.
- Session-based, role-gated Editor Mode and Dev Mode with explicit scope boundaries.
- Install-state-aware kernel behavior for setup-dependent admin routes.

---

## Runtime routing architecture (current implementation)

Routes are registered by modular registrars in deterministic order:

1. `SystemRouteRegistrar`
2. `AuthRouteRegistrar`
3. `AdminRouteRegistrar`
4. `DevModeRouteRegistrar`
5. `EditorModeRouteRegistrar`
6. `PublicContentRouteRegistrar` (**catch-all, always last**)

This ordering is the active runtime route-priority model.

### System routes

- `GET /` → home
- `GET /health` → health check
- `GET /search` → search page
- `GET /sitemap.xml` → XML sitemap
- `GET /robots.txt` → dynamic robots policy
- install flow:
  - `GET /install` → install form (or redirect if already installed)
  - `POST /install` → installer execution

### Auth routes

- `GET /admin/login`, `POST /admin/login`
- `POST /admin/logout`
- aliases: `/login`, `/logout`

### Admin routes

- `GET /admin` dashboard
- `GET /admin/patterns` pattern registry endpoint
- content management (`/admin/content`, create/edit/store/update)

### Dev Mode routes

- `/admin/dev-mode/*` primary dev-mode UI/actions
- `/dev/*` aliases
- includes snapshot export endpoint: `POST /admin/dev/export`

### Editor Mode routes

- `/editor-mode/*` primary editor-mode actions
- `/editor/*` aliases
- inline save endpoint: `POST /editor-mode/save-field`

### Public content catch-all route

- `GET /{slug}` → published content rendering via `ContentController`
- must remain last so system/admin/editor/dev/auth routes resolve first

### Install redirect behavior in kernel

`Kernel` redirects setup-dependent admin paths (`/admin` and `/admin/*`) to `/install` when installation is incomplete.

---

## Template system (current implementation)

Template runtime is intentionally minimal and deterministic.

### Active runtime entrypoints

- `templates/index.php` = universal renderer for content routes.
- `templates/system/404.php` = not-found renderer.
- `templates/system/search.php` = search renderer.

### Resolver behavior

- `resolveContentTemplate()` → `templates/index.php`
- `resolveNotFound()` → `templates/system/404.php`
- `resolveSystemTemplate('search')` → `templates/system/search.php` (fallback to `templates/system/404.php` if missing)

### Layout behavior

Templates set `$layout` (currently `layouts/default.php` in core runtime templates), and `TemplateRenderer` resolves that file under `templates/`.

### Explicitly not current runtime behavior

The following are **not** part of active runtime template selection:

- `templates/pages/{slug}.php`
- `templates/pages/page.php`
- `templates/default.php` as route-resolved default
- WordPress-style fallback chains
- content-type template hierarchy as runtime selection logic
- slug-template hierarchy as runtime selection logic
- editor-selected template switching

Composition is pattern-driven; template dispatch is not hierarchy-driven.

---

## Pattern system (current implementation)

Patterns are filesystem-defined blocks:

- `patterns/{key}/pattern.json`
- `patterns/{key}/pattern.php`

Runtime services:

- `PatternRegistry` for deterministic discovery
- `PatternRenderer` for rendering
- `PatternDataValidator` for field validation

Current supported inline-edit field types in patterns: `text`, `textarea`.

### Content composition model

Content items store ordered `pattern_blocks` JSON blocks, each shaped like:

```json
{ "pattern": "slug", "data": { "field": "value" } }
```

Rendering path:

1. `ContentController` loads published content item.
2. `templates/index.php` iterates `pattern_blocks` in stored order.
3. Each block renders through `PatternRenderer`.

---

## Editor Mode (current implementation)

Editor Mode is session-based and role-gated (`superadmin`, `admin`, `editor`).

### Current capabilities

- enable / disable editor mode
- editor banner + editor assets when active
- inline save endpoint for allowlisted fields

### Current allowlisted inline edits

- content item fields:
  - `title`
  - `meta_title`
  - `meta_description`
  - `og_image`
  - `canonical_url`
  - `noindex`
- pattern block fields of type `text` and `textarea`

### Current hard boundaries

- no template/layout file editing
- no pattern definition editing (`pattern.json`/`pattern.php`)
- no CSS/JS editing
- no routing/PHP source editing
- no unrestricted arbitrary JSON mutation outside validator constraints

---

## Dev Mode (current implementation)

Dev Mode is session-based and role-gated (`superadmin`, `admin`).

### Editable roots

- `templates/`
- `patterns/`
- `public/assets/css/`
- `public/assets/js/`

### Safety model

- allowlisted roots and supported-file discovery
- path normalization + traversal rejection
- blocked sensitive paths (`src`, `vendor`, `.env`, `config`, etc.)
- bounded file size for edits
- edit history logging (`storage/logs/dev-mode-edits.log`)

### Current capabilities

- browse allowlisted editable files
- edit/save allowlisted files
- export snapshot endpoint (`POST /admin/dev/export`) that returns:
  - composition snapshot export result
  - OCF export result when OCF exporter is available

### Not current behavior

- no unrestricted repository editing
- no migration editing
- no package/dependency management workflows
- no guaranteed bidirectional sync/update pipeline beyond current export endpoint behavior

---

## Install flow and install-state logic (current implementation)

Install sequence:

1. run environment checks
2. validate installer input
3. connect to target DB
4. run Phinx migrations
5. create initial admin user
6. create/update `settings.install_completed`
7. run demo seeder (`BlueprintDemoSeeder`)
8. verify install state and redirect to `/admin/login`

Install state is complete only when all checks pass:

- required tables exist (`phinxlog`/configured migrations table, `users`, `content_types`, `settings`)
- admin/superadmin user exists
- install flag is truthy in `settings.install_completed`

---

## OCF / composition / Git boundary (current implementation)

Runtime architecture enforces strict separation:

- **OCF export = content only** (portable semantic content model)
- **Composition snapshot = blueprint-specific assembly metadata** (route composition + ordered patterns)
- **Git repository = presentation/runtime source of truth** (`templates`, `patterns`, assets, PHP runtime code, docs, skills)

This keeps portable content from absorbing blueprint-specific rendering/runtime concerns.

### OCF export structure (current)

`OCFExporter` emits content-only payloads with portable semantic structure:

- header metadata:
  - `export_format_version: 2`
  - `ocf_version: "0.1-draft"`
  - `generated_by`
  - `generated_at` (ISO timestamp)
- `content_types` including semantic field schemas
  - dynamic `fieldDefinitions()` are used when provided by the domain model
  - fallback schema remains available for compatibility
- `content_items` including:
  - semantic fields (`title`, `slug`, `status`)
  - `pattern_blocks` as structured semantic content data (not layout instructions)
  - relationships object with `content_type` and future-safe room for `parent_slug` / `related_items`
  - optional SEO metadata (`meta_title`, `meta_description`, `canonical_url`) when present
  - creation/update timestamps

### Boundary clarification

- OCF export includes portable structured content and semantics only.
- Composition snapshot export includes blueprint-specific composition/assembly context.
- Runtime rendering remains responsible for templates/layout/renderers/assets.
- OCF export does **not** include templates, layout files, renderer entrypoints, CSS classes, or pattern rendering instructions.

### Composition snapshot format (current)

`CompositionExporter` emits `export_format_version: 2` snapshots.

Scopes:

- `scope: content-routes` (per-content snapshot)
- `scope: system-routes` (system route snapshot)

Current content-route fields:

- `slug`
- `title`
- `route_type` (`content`)
- `renderer_entrypoint` (`templates/index.php`)
- `layout` (current exporter value: `templates/layout.php`)
- `patterns` (ordered pattern blocks)

Current system-route fields:

- `route`
- `renderer_entrypoint` (`templates/system/*.php`)
- `layout` (current exporter value: `templates/layout.php`)
- `patterns`

Notes:

- `renderer_entrypoint` is the canonical field (replacing older `template` naming).
- Snapshot metadata is tooling/export context and does not redefine runtime route matching rules.

---

## SEO runtime capabilities (current implementation)

### Sitemap

- `GET /sitemap.xml`
- includes published content items
- emits `loc` + `lastmod`
- uses `canonical_url` when available, otherwise `APP_URL + slug`

### robots.txt

- `GET /robots.txt` dynamic output
- `APP_ENV=production`: allows crawling and references sitemap
- privileged/operator paths disallowed in production (`/admin`, `/editor`, `/editor-mode`, `/dev`, `/install`)
- non-production: `Disallow: /`

### Social metadata + structured data

Handled through dedicated renderers coordinated by `TemplateRenderer` (not per-template duplication):

- `SeoMetaRenderer`
  - canonical tag
  - meta description
  - OpenGraph tags
  - Twitter card tags
- `StructuredDataRenderer`
  - JSON-LD graph output
  - WebSite, Organization, WebPage, Article (when applicable), BreadcrumbList (when data exists)

Architectural guardrail: `TemplateRenderer` coordinates rendering, but future cross-cutting rendering concerns must be implemented as dedicated services and delegated from the coordinator.

---

## Future roadmap (not current runtime behavior)

Only the items below are roadmap-level (planned/possible), not guaranteed current behavior:

### SEO roadmap

- sitemap index generation
- image sitemaps
- module-contributed sitemap entries
- multilingual sitemap variants
- richer robots customization (custom rules / overrides)
- per-content-type indexing policies

### Metadata roadmap

- site-level default OG image wiring from settings/config
- configurable `og:type` mapping by content type
- OG image dimension tags
- multilingual metadata variants
- expanded schema coverage (product, event, FAQ, author)

### Deployment ergonomics roadmap

- deployment orchestration improvements layered on top of current release artifact packaging
