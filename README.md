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
