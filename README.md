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

Install-state checks require all of the following:

- required tables exist (`users`, `content_types`, `settings`)
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
- `GET|POST /install` (when install is required)

Current content route:

- `GET /{slug}` for published content items

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
