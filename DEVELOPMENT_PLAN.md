# DEVELOPMENT_PLAN.md

Content PHP Blueprint

## Purpose

This document is the implementation-oriented source of truth for current runtime architecture.

## Current architecture summary

- Layered PHP architecture: Domain, Application, Infrastructure, Http, Admin.
- Explicit route registration through `src/Http/Routing/RouteRegistry.php`.
- Deterministic template resolution through `TemplateResolver`.
- Pattern-based page composition through Pattern System v1.
- Role-gated Editor Mode and Dev Mode with strict scope boundaries.

## Runtime routing architecture

### Public/system routes

- `GET /` → home controller.
- `GET /health` → health controller.
- `GET /search` → search controller.
- `GET /sitemap.xml` → sitemap controller (published content XML sitemap).
- `GET /robots.txt` → robots controller (dynamic environment-aware robots policy).
- `GET /install`, `POST /install` → installer (only when install is incomplete or DB bootstrap is unavailable).

### Content route

- `GET /{slug}` → content controller for published content items.

### Admin/auth/editor/dev routes

- auth: `/admin/login`, `/admin/logout` (+ system aliases `/login`, `/logout`)
- admin dashboard: `/admin`
- content admin: `/admin/content*`
- pattern registry endpoint: `GET /admin/patterns`
- editor mode: `/editor-mode/*` (+ `/editor/*` aliases)
- dev mode: `/admin/dev-mode/*` (+ `/dev/*` aliases)

### Install redirect behavior

Kernel redirects setup-dependent admin paths (`/admin` and `/admin/*`) to `/install` when installation is incomplete.

## Template System v1 (current, not planned)

Template model is intentionally minimal and deterministic:

- `templates/index.php` is the universal content renderer.
- `templates/system/404.php` is the not-found renderer.
- `templates/system/search.php` is the search/system route renderer.

Resolver behavior:

- `resolveContentTemplate()` → `templates/index.php`
- `resolveNotFound()` → `templates/system/404.php`
- `resolveSystemTemplate('search')` → `templates/system/search.php` (or `system/404.php` fallback if missing)

Out of scope for v1:

- `templates/pages/{slug}.php` routing
- `templates/page.php` routing
- `templates/default.php` routing
- WordPress-style fallback chains
- content-type/slug template hierarchy
- editor-selected template switching

## Pattern System v1 (current)

Patterns are filesystem-defined reusable blocks:

- `patterns/{key}/pattern.json`
- `patterns/{key}/pattern.php`

Runtime services:

- `PatternRegistry` for deterministic discovery
- `PatternRenderer` for safe rendering
- `PatternDataValidator` for field-level validation

Current field support is limited to `text` and `textarea`.

## Pattern blocks on content items

Content items store `pattern_blocks` JSON. Each block contains:

```json
{ "pattern": "slug", "data": { "field": "value" } }
```

Rendering behavior:

- content controller loads published item
- universal template (`templates/index.php`) iterates blocks
- each block is rendered via `PatternRenderer` in saved order

## Editor Mode foundation (current scope)

Editor Mode is session-based and role-gated (`superadmin`, `admin`, `editor`).

Current capabilities:

- enable/disable mode
- visible mode banner
- conditional loading of editor assets
- inline save endpoint: `POST /editor-mode/save-field`

Allowed inline edits in v1:

- content item title
- pattern block `text` fields
- pattern block `textarea` fields

Current hard boundaries:

- no template/layout editing
- no pattern definition editing
- no CSS/JS editing
- no routing/PHP editing
- no arbitrary content JSON mutation

## Dev Mode foundation (current scope)

Dev Mode is session-based and role-gated (`superadmin`, `admin`).

Editable roots:

- `templates/`
- `patterns/`
- `public/assets/css/`
- `public/assets/js/`

Safety model:

- allowlisted roots/extensions
- path normalization and traversal rejection
- bounded file size
- edit history logging to `storage/logs/dev-mode-edits.log`

Not allowed:

- unrestricted `src/` edits
- migrations
- `.env`/secrets
- `vendor/`

## Install flow and install-state logic

Installation responsibilities:

1. run environment checks
2. validate installer input
3. connect to target DB
4. run Phinx migrations
5. create initial admin user
6. mark install completed in `settings.install_completed`

Install state is considered complete only when all checks pass:

- required tables exist (`phinxlog`/configured migrations table, `users`, `content_types`)
- admin/superadmin user exists
- install flag evaluates true

## Deployment and install strategy

### Release artifact packaging

- Production deployment format is a prebuilt release zip artifact.
- Packaging is handled by CI via `.github/workflows/build-release.yml` and can also be run locally with `scripts/build-release.sh`.
- The release zip includes runtime-required directories/files (`public/`, `src/`, `templates/`, `patterns/`, `config/`, `database/`, `vendor/`, `storage/`, `composer.json`, `phinx.php`, `README.md`, `.env.example`, and root `index.php`).
- Non-runtime paths (tests/docs/skills/.git/.github and local tooling) are excluded from the package.

### Production server expectation

- Production target servers should not need Composer installed.
- Dependencies are resolved during packaging using `composer install --no-dev --optimize-autoloader`.
- Runtime install remains browser-driven through `/install` after `.env` configuration.

### Future deploy ergonomics

- Keep deployment automation decoupled from packaging.
- The release zip provides a stable handoff artifact for future one-step deploy tooling (e.g., upload/unzip/switch symlink orchestration).

## AI/data boundary architecture

Runtime architecture keeps strict boundaries between content, composition, and source code.

- **OCF = content only** (portable semantic content).
- **Composition snapshot = separate blueprint-specific layer** (pattern assembly/order and blueprint runtime composition context).
- **Repository source layer** = templates, patterns, assets, runtime code, docs, skills.

This prevents portable content from absorbing blueprint-specific rendering details.

## AI workflow scope

Current intended operating model:

- AI assists with repository code generation/refactoring.
- Humans review/refine source changes.
- Editors manage runtime content updates through admin + Editor Mode.
- Dev Mode is for high-trust presentation edits only.

Out of scope in current runtime:

- in-app LLM orchestration
- runtime prompt pipelines
- external AI API execution inside request flow


## Sitemap generation implemented

Automatic sitemap generation is implemented as a core runtime capability.

Current behavior:

- `GET /sitemap.xml` returns a valid XML sitemap response.
- Entries are built from published content items only.
- Each entry includes `loc` and `lastmod` (`updated_at` timestamp).
- `canonical_url` metadata is respected when provided.
- Otherwise, absolute `loc` URLs are generated from `APP_URL` and the content item slug.

## robots.txt generation implemented

Automatic robots generation is implemented as a core runtime capability.

Current behavior:

- `GET /robots.txt` returns `text/plain; charset=utf-8`.
- When `APP_ENV=production`, robots output allows public crawling and references sitemap URL built from `APP_URL`.
- Privileged or operator surfaces remain non-indexable in production via disallow rules:
  - `/admin`
  - `/editor`
  - `/editor-mode`
  - `/dev`
  - `/install`
- When `APP_ENV` is not `production`, robots output blocks all crawling (`Disallow: /`).
- Route handling is dynamic through application routing instead of static-file ownership.

Possible future extensions:

- custom disallow rules
- per-content-type indexing rules
- multisite support
- environment overrides

Future roadmap:

- sitemap index generation for larger sites
- image sitemap support
- module-contributed sitemap entries
- multilingual sitemap support

## Social metadata auto-rendering implemented

OpenGraph and Twitter metadata rendering is implemented centrally in `TemplateRenderer`.

Current behavior:

- OpenGraph metadata is auto-injected into page `<head>` output (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`).
- Twitter card metadata is auto-injected (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`).
- Metadata values are derived from structured content metadata with runtime fallbacks:
  - title fallback to content title
  - description fallback to generated summary from content blocks
  - image fallback to optional default image when available
  - URL fallback to canonical slug URL generation
- Duplicate social tags are prevented by renderer-level de-duplication, preserving TemplateRenderer as the single metadata source.

Possible future extensions:

- site-level default `og_image` wiring from config/settings
- per-content-type `og:type` mapping configuration
- image dimension metadata support (`og:image:width` / `og:image:height`)
- multi-language metadata variants

## Structured data generation implemented

Schema.org JSON-LD generation is implemented centrally in `TemplateRenderer`.

Current behavior:

- Renderer injects a single `<script type="application/ld+json">` block into page `<head>`.
- Global schema nodes are emitted automatically:
  - `WebSite` (`APP_NAME`, `APP_URL`)
  - `Organization` (`APP_NAME`, `APP_URL`)
- Per-page schema nodes are emitted automatically:
  - `WebPage` (name/url/description from metadata with safe fallbacks)
  - `Article` (for `article` content type, with safe optional fields)
  - `BreadcrumbList` (only when hierarchy data exists)
- Missing metadata fields are omitted safely without breaking overall JSON-LD shape.
- Templates are not responsible for schema logic; renderer remains the centralized metadata source.

Future roadmap:

- product schema
- event schema
- faq schema
- multi-language schema variants
- author schema support
