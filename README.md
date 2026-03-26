# Content PHP Blueprint

Framework-light PHP 8.3+ architecture blueprint for structured content websites.

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create your local environment file:
   ```bash
   cp .env.example .env
   ```
3. Update `.env` with project-specific values (especially `APP_URL`, `DB_NAME`, and `DB_USER`).
4. Serve from `public/` (local server or web server vhost).

### Configuration flow

- `public/index.php` loads `.env` once via `App\Infrastructure\Support\Env`.
- `App\Infrastructure\Config\ConfigLoader` loads all `config/*.php` files.
- `App\Infrastructure\Config\ConfigRepository` provides read-only runtime access to configuration values.
- Config files are the single place where environment values are mapped into app-ready settings.

## Database and migrations

This project uses **Phinx** (Composer-installed) for versioned SQL migrations.

### Why Phinx

- Lightweight, framework-agnostic, and shared-hosting friendly.
- Uses timestamped migration files in `database/migrations`.
- Easy to run in both local and production environments with Composer scripts.

### Required environment variables

Ensure these are set in `.env` before running migrations:

```dotenv
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
```

### Migration commands

Run these exact commands from the project root:

```bash
composer migrate
composer migrate:status
composer migrate:rollback
composer migrate:create -- MigrationName
```

Equivalent direct binary commands:

```bash
vendor/bin/phinx migrate -c phinx.php
vendor/bin/phinx status -c phinx.php
vendor/bin/phinx rollback -c phinx.php
vendor/bin/phinx create -c phinx.php MigrationName
```
