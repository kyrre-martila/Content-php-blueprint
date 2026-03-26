# Content PHP Blueprint

Framework-light PHP 8.3+ blueprint for structured content websites with explicit layered architecture.

## Installation

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
