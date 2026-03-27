# Content PHP Blueprint

Framework-light PHP 8.3+ blueprint for structured content websites with explicit layered architecture.

## Developer setup

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create local env file:
   ```bash
   cp .env.example .env
   ```

## Environment setup

Set at minimum in `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=content_blueprint
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_MIGRATIONS_TABLE=phinxlog
PHINX_ENV=development

SESSION_NAME=content_blueprint_session
SESSION_SECURE_COOKIE=false
```

## Migration commands

```bash
composer migrate
composer migrate:status
composer migrate:rollback
composer migrate:create -- MigrationName
```

Direct binary equivalents:

```bash
vendor/bin/phinx migrate -c phinx.php
vendor/bin/phinx status -c phinx.php
vendor/bin/phinx rollback -c phinx.php
vendor/bin/phinx create -c phinx.php MigrationName
```

## Local run instructions

Serve `public/` as your web root.

Quick local server option:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000`.

## Deployment strategy

This project supports two entrypoint layouts:

1. **Recommended mode**
   - Web root points to `public/`
   - Requests execute `public/index.php`

2. **Compatibility mode**
   - Web root points to project root
   - Root `index.php` delegates to `public/index.php`

## Release artifact deployment

Production deployments should prefer the generated release zip artifact.

- The release zip includes runtime files plus prebuilt `vendor/`.
- Target servers are not expected to run Composer.
- The intended deploy flow is:
  1. download the release zip artifact
  2. upload and unzip it on the target server
  3. configure `.env`
  4. open `/install` to complete setup

This release artifact flow is distinct from local developer setup. Local development still uses `composer install`.

## Installation flow and install-state logic

Installation is a runtime setup flow, separate from deployment.

Browser flow:

1. Deploy files.
2. Open `/install`.
3. Submit DB settings and first admin credentials.
4. Installer runs migrations, creates initial admin, writes `settings.install_completed = true`.
5. On success, user is redirected to `/admin/login`.

### Demo seed content

During installation, Blueprint also runs a demo seeder that creates a small example site.

- demo content is created automatically during install
- demo content is safe to delete after setup
- demo content demonstrates Blueprint architecture (content modeling, pattern blocks, composition intent, and export boundaries)

Install-state checks require all of the following:

- required tables exist (`phinxlog`/configured migrations table, `users`, `content_types`)
- at least one admin/superadmin user exists
- install flag is true in `settings.install_completed`

Routing behavior tied to install-state:

- if setup is incomplete, `/admin` and `/admin/*` are redirected to `/install`
- `/install` is available while installation is required
- once installed, `/install` redirects to `/`

## Runtime routing architecture

Routes are explicitly registered in `RouteRegistry`.

Current public/system routes:

- `GET /` (home)
- `GET /health`
- `GET /search`
- `GET /sitemap.xml`
- `GET /robots.txt`
- `GET|POST /install` (when install is required)

Current content route:

- `GET /{slug}` for published content items

## Canonical URL enforcement

Canonical routing is enforced automatically for content item routes as part of Blueprint's SEO-first architecture.

- The content item `slug` is the canonical path source of truth (`/{slug}`).
- Incoming content requests are normalized (lowercase, duplicate slash cleanup, trailing slash normalization, and `/index` removal) before canonical comparison.
- If a request variant is not already canonical, runtime issues an HTTP `301` redirect to the canonical target.
- Query parameters are preserved on canonical redirects.
- If a content item defines `canonical_url`, that value overrides slug-based canonical routing and redirects to the metadata URL.
- System routes are excluded from canonical enforcement (`/search`, `/login`, `/logout`, `/admin/*`, `/editor/*`, `/dev/*`).
- Template rendering auto-injects `<link rel="canonical" ...>` using `canonical_url` metadata when present, otherwise from the content slug.

Template mapping in runtime:

- content pages render through `templates/index.php`
- not-found responses render through `templates/system/404.php`
- search renders through `templates/system/search.php`

Template System v1 does **not** use WordPress-style fallback chains and does **not** resolve `templates/pages/{slug}.php`, `templates/page.php`, or `templates/default.php` for route selection.

## Pattern System v1

Patterns are reusable presentation blocks loaded from `patterns/`:

- `patterns/{key}/pattern.json` metadata
- `patterns/{key}/pattern.php` renderer

Runtime behavior:

- patterns are discovered by `PatternRegistry`
- rendering is executed by `PatternRenderer`
- data is validated by `PatternDataValidator`
- v1 render field support is intentionally limited to `text` and `textarea`
- registry is exposed at authenticated `GET /admin/patterns`

### Pattern blocks stored on content items

Content items store page composition as `pattern_blocks` JSON.

Each block uses:

```json
{ "pattern": "slug", "data": { "field": "value" } }
```

`templates/index.php` renders blocks sequentially through `PatternRenderer`.

## Editor Mode (current scope)

Editor Mode is role-gated and session-based (`superadmin`, `admin`, `editor`).

Current v1 scope:

- enable/disable mode per session
- show Editor Mode banner while active
- load editor assets only while active
- inline save endpoint: `POST /editor-mode/save-field`
- editable fields are limited to:
  - content item title
  - content item SEO metadata (`meta_title`, `meta_description`, `og_image`, `canonical_url`, `noindex`)
  - pattern block `text` fields
  - pattern block `textarea` fields

Out of scope in current runtime:

- template/layout editing
- pattern definition editing
- CSS/JS editing
- routing/PHP changes
- pattern block reorder/insert/remove UI

## Dev Mode (current scope)

Dev Mode is role-gated (`superadmin`/`admin`) and session-activated.

Editable roots:

- `templates/`
- `patterns/`
- `public/assets/css/`
- `public/assets/js/`

Dev Mode does not allow unrestricted source editing (no core `src/`, migrations, `.env`, or `vendor/`).

## AI and data boundary architecture

This repository is AI-first for code generation/refactoring, but runtime AI invocation is out of scope.

Boundary model:

- **OCF = content only** (portable structured content)
- **Composition snapshot = separate blueprint-specific layer** (pattern order/assembly and blueprint-specific composition context)
- **Repository source layer** = templates, patterns, assets, runtime code, docs, skills

This keeps portable content, blueprint composition, and source code workflows separate and predictable.

## SEO metadata support

SEO metadata is part of the structured content model (not plugin-based and not template-owned).

Each content item supports:

- `meta_title` (nullable string, falls back to item title at render-time)
- `meta_description` (nullable string, falls back to a truncated content summary when possible)
- `og_image` (nullable string/path)
- `canonical_url` (nullable string)
- `noindex` (boolean, default `false`)

The metadata fields are persisted in the core content repositories and are provided automatically to templates through the content render payload as `meta`, so templates can consume metadata without adding plugin wiring.

## OpenGraph and Twitter metadata support

OpenGraph and Twitter card metadata are generated automatically by `TemplateRenderer` as part of centralized metadata rendering.

- OpenGraph tags (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`) are auto-rendered from structured content metadata with safe fallbacks.
- Twitter tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`) are auto-rendered from the same metadata source.
- Fallback behavior is handled by runtime metadata resolution (title, description summary fallback, canonical URL fallback, and optional default image support).
- Metadata rendering is centralized in `TemplateRenderer`, so no plugin wiring is required and templates do not need duplicated social-tag logic.

## Structured data support

Schema.org JSON-LD metadata is generated automatically by `TemplateRenderer` as part of the same centralized metadata pipeline.

- JSON-LD is injected into `<head>` using `<script type="application/ld+json">` and rendered once per page.
- Renderer-managed schema types are selected from metadata/context:
  - `WebSite` (global)
  - `Organization` (global)
  - `WebPage` (per page)
  - `Article` (for `article` content type)
  - `BreadcrumbList` (when hierarchy/breadcrumb data exists)
- No plugin is required; behavior is metadata-driven and resolved in core runtime rendering.
- Templates should not implement schema.org logic directly; TemplateRenderer is the single structured data source.
- Fallback-safe rendering omits only missing fields and preserves valid JSON-LD structure.


## Sitemap generation

`/sitemap.xml` is generated automatically as a core platform feature.

- Sitemap output is valid XML sitemap format and returned as `application/xml; charset=utf-8`.
- Only published content items are included.
- Each entry includes `loc` and `lastmod` (`updated_at` as ISO-8601).
- `canonical_url` metadata is used for `loc` when present.
- When `canonical_url` is missing, absolute URLs are generated from `APP_URL` + `slug`.

## robots.txt generation

`/robots.txt` is generated automatically as a core platform feature (not a static file requirement).

- Non-production environments (`APP_ENV` not equal to `production`) return:
  - `User-agent: *`
  - `Disallow: /`
- Production environments return:
  - `User-agent: *`
  - `Allow: /`
  - `Disallow: /admin`
  - `Disallow: /editor`
  - `Disallow: /editor-mode`
  - `Disallow: /dev`
  - `Disallow: /install`
  - `Sitemap: {APP_URL}/sitemap.xml`
- `APP_URL` is used dynamically to build the sitemap URL in robots output.
- Runtime route registration guarantees the dynamic `/robots.txt` endpoint takes precedence over catch-all content slug routing.
- In front-controller deployments, requests resolved by the router always use the dynamic controller response for `/robots.txt` even if `public/robots.txt` also exists.

## Test commands

```bash
composer test
vendor/bin/pest
```

## Static analysis commands

```bash
composer analyse
vendor/bin/phpstan analyse -c phpstan.neon.dist
```
