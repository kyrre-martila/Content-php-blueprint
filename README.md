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

This section is for local development where Composer and developer tooling are available.

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

This project supports two deployment entrypoint layouts:

1. **Recommended mode (preferred):**
   - Web root points to `public/`
   - Requests execute `public/index.php` directly

2. **Compatibility mode (shared hosting fallback):**
   - Web root points to the project root
   - Root `index.php` delegates execution to `public/index.php`

Why `public/` is preferred:

- Keeps non-public files outside the document root
- Reduces accidental exposure of config, source, and migration files
- Matches modern PHP deployment hardening practices

Why compatibility mode exists:

- Some shared hosting plans do not allow changing the web root
- The root `index.php` keeps deployment possible in those environments without changing application architecture

## Future installation workflow

Planned first-run lifecycle:

- **Deployment** delivers files to the target server.
- **Installation** prepares required runtime prerequisites (for example DB connectivity and schema readiness).
- **First-run setup** initializes project-specific state (for example initial admin account and installer lock state).

The future `/install` flow will handle setup tasks after deployment, not replace deployment itself.

## Production deployment (planned workflow)

Planned direction for production releases:

- Deploy from **release zip packages**, not from a full developer checkout
- Release zips should include:
  - `vendor/`
  - compiled/autoloaded Composer artifacts
  - production-optimized dependencies

Production servers should **not** require running Composer.

Future install wizard plan:

- Route placeholder: `/install`
- Wizard responsibilities:
  - environment checks
  - database configuration
  - migration execution
  - initial admin user creation

Important distinction:

- **Deployment** = shipping files to the server
- **Installation** = preparing runtime prerequisites and running setup actions
- **First-run setup** = initial bootstrap state (admin account, install lock, and persisted config where supported)

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

## v0.1 hardening highlights

- Centralized error handling with safe production output.
- File-based logging channels under `storage/logs/`.
- CSRF protection on all admin POST routes.
- Expanded docs and reusable AI skill workflows under `docs/` and `skills/`.

## Installation via browser

Use this flow after deployment is complete (files are already uploaded and web root is configured):

1. Upload/deploy project files to the server.
2. Open `/install` in your browser.
3. Fill in database connection settings.
4. Provide initial admin email and password.
5. Submit to run migrations, create the first admin user, and finalize installation.

Deployment and installation are different steps:

- **Deployment** copies application files to the server.
- **Installation** initializes runtime state (database schema and first admin account).

## Pattern system

Patterns are reusable presentation blocks that live in `patterns/` and are loaded directly from the filesystem.

Key properties:

- filesystem-based (`patterns/{slug}/pattern.json` + `pattern.php`)
- reusable and structured through typed field metadata
- safe for editors to use because they can only select approved developer-defined patterns
- developer- or AI-authored as version-controlled repository code
- version-controlled with the rest of the application source

### Templates vs patterns vs content

- **Templates** (`templates/`) define page-level layout and route-oriented rendering structure.
- **Patterns** (`patterns/`) define small reusable presentation sections rendered inside templates.
- **Content** is editor-managed data (for example page title, slug, status, field values) persisted through content repositories.

This keeps presentation extensible for developers while preserving predictable editor-safe behavior.

## Pattern blocks in content items

Content items can include a `pattern_blocks` payload that stores structured pattern composition per entry.

Key behavior:

- patterns define allowed fields and field types from `pattern.json`
- each block stores `{ "pattern": "slug", "data": { ... } }`
- block data is persisted as JSON in the content item record
- frontend rendering outputs blocks sequentially using `PatternRenderer`

Editor workflow:

1. Open create/edit content item in admin.
2. Select a pattern for each block from registered patterns.
3. Fill the generated pattern fields.
4. Save content item.
5. Visit the content page to see pattern blocks rendered in saved order.

## Editor Mode

Editor Mode is a role-gated inline editing mode for safe content updates while viewing a published page.

What it allows in v1:

- content item title editing
- pattern block `text` field editing
- pattern block `textarea` field editing

What it does **not** allow:

- drag-and-drop layout editing
- template editing
- CSS editing
- JavaScript editing
- arbitrary database field mutation

Editor Mode is session-based and must be explicitly enabled by an authenticated admin/editor role. Layout, templates, and rendering structure remain developer-controlled.
