# Content PHP Blueprint

Framework-light PHP 8.3+ blueprint for structured content websites with explicit layered architecture.

## What is implemented now

- Deterministic route registration with a public catch-all route that is always last.
- Deterministic template resolution (no WordPress-style hierarchy).
- Category collection routing at `GET /categories/{groupSlug}/{categorySlug}`.
- Content collections and category collections with shared pagination/query behavior.
- Explicit separation of hierarchy (`parent_id`), categories (`content_item_categories`), and relationships (`content_item_relationships`).
- Install-state-aware admin routing, role-gated Editor Mode/Dev Mode, and trusted proxy-aware client IP resolution.

## AI operating environment

This repository is designed for AI-assisted and human-led development.

- `docs/` describes architecture and operating boundaries.
- `skills/` contains reusable implementation playbooks.
- OCF export + composition snapshot export support AI reasoning without direct DB access.
- Dev Mode is for source-layer presentation edits (templates/patterns/CSS/JS).
- Editor Mode is for safe runtime content edits within allowlisted fields.

## Developer setup (local source checkout)

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create local env file:
   ```bash
   cp .env.example .env
   ```
3. Configure required values (minimum):

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_VERSION=0.1.0

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

# Comma-separated trusted reverse proxy IPs/CIDRs
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
```

## Local run

Serve `public/` as the web root.

```bash
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000`.

## Template and routing model (current)

- Single content route: `GET /{slug}`.
- Category collection route: `GET /categories/{groupSlug}/{categorySlug}`.
- Category route resolves group slug first, then category slug inside that group.
- If either slug is missing/invalid, runtime renders the system 404 template.
- If a category exists but has no published content, runtime still renders the category collection template with an empty collection.

Template resolution (current runtime):

- Single content: `templates/content/{content_type}.php` -> `templates/index.php`
- Content collection: `templates/collections/{content_type}.php` -> `templates/system/404.php`
- Category collection: `templates/collections/categories/{group_slug}.php` -> `templates/system/404.php`
- System routes: `templates/system/{route}.php` -> `templates/system/404.php`

## Category model vs hierarchy vs relationships (current)

- **Hierarchy**: tree-like parent/child structure using `content_items.parent_id`.
- **Categories**: classification via `content_item_categories` and category groups.
- **Relationships**: directional typed links via `content_item_relationships` + type rules.

These are independent systems and should not be conflated.

## Trusted proxies (deployment-critical)

Configure `TRUSTED_PROXIES` correctly in each environment:

- Empty value: runtime uses `REMOTE_ADDR` only.
- Configured value: runtime trusts `X-Forwarded-For` **only** when immediate `REMOTE_ADDR` is in trusted proxies.

Incorrect proxy trust configuration can collapse many users into one login rate-limit bucket.

See `docs/security.md` for deployment checklists.

## Runtime storage and persistent state

Runtime state is intentionally separate from source files.

Must persist across deploys/upgrades:

- `.env`
- `storage/`
- `storage/logs/`
- `storage/exports/composition/`
- `storage/exports/ocf/`

`public/index.php` calls `RuntimeStorage::ensure($projectRoot)` during bootstrap to create missing runtime directories.

`EnvironmentCheck` verifies runtime directories are present and writable, but does not create them.

## Release artifact deployment workflow (current)

Production should deploy from the release zip artifact (built by `scripts/build-release.sh`):

1. Build/download release zip artifact.
2. Upload and unzip on target server.
3. Preserve existing `.env` and `storage/`.
4. Point web root to `public/` (recommended).
5. Run `/install` only for first-time installation.
6. For upgrades, deploy new files while preserving runtime state; runtime `UpgradeRunner` handles post-deploy upgrade hooks when version differs.

Release artifacts include prebuilt `vendor/`; target servers are not expected to run Composer.

## Install flow (current)

1. Deploy files.
2. Open `/install`.
3. Submit DB settings and first admin credentials.
4. Installer runs migrations, creates initial admin, writes install flags/version.
5. Redirect to `/admin/login`.

Install-state checks require: migrations table + key tables, admin user, install flag true, installed version persisted.

## Future roadmap (not yet implemented)

- In-admin GitHub release updater (release discovery/download/file replacement orchestration).
- Expanded automated deployment orchestration around existing release artifact flow.
- Additional upgrade tasks as `UpgradeRunner` hooks evolve.
