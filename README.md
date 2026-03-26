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
