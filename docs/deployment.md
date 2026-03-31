# Deployment

This document separates developer setup, deployment, installation, and runtime-state handling so releases are repeatable across local, staging, and production.

## Developer setup (local)

1. `composer install`
2. `cp .env.example .env`
3. Configure DB/app values in `.env`.
4. Run migrations (`composer migrate`).
5. Serve with web root on `public/`.

Local runtime storage expectations:

- writable: `storage/`, `storage/logs/`, `storage/exports/`, `storage/exports/composition/`, `storage/exports/ocf/`
- persistent between local restarts: `.env`, `storage/`
- auto-created if missing: required `storage/*` runtime directories during HTTP bootstrap (`public/index.php`)

## Deployment strategy (staging + production)

### Recommended mode (public mode)

- Configure web root to `public/`.
- Requests execute `public/index.php` directly.
- Keeps source/config/migrations outside document root.

### Compatibility mode

- Use project root as web root where hosting cannot point to `public/`.
- Root `index.php` delegates to `public/index.php`.
- This is supported for hosting compatibility, not preferred security posture.

## Runtime storage contract

Runtime state (must not be treated as replaceable release files):

- `.env`
- `storage/`
- `storage/logs/`
- `storage/exports/composition/`
- `storage/exports/ocf/`

Writable at runtime:

- `storage/` and all runtime subdirectories above

Preserve during upgrades:

- `.env`
- entire `storage/` tree

Operationally: do not run upgrade steps that delete/replace these runtime-state paths.

## Automatic runtime directory creation

`App\Infrastructure\Application\RuntimeStorage` defines required runtime directories and creates them when missing.

Creation point (single source of truth):

- HTTP bootstrap (`public/index.php`) before logger/kernel boot

`EnvironmentCheck::run` does not create directories; it only verifies that required runtime directories already exist and are writable.

This keeps runtime directory creation centralized while still surfacing deploy/runtime misconfiguration during install checks.

## Release artifact notes

`scripts/build-release.sh` pre-creates runtime directories in the release bundle:

- `storage/logs`
- `storage/exports/composition`
- `storage/exports/ocf`

Even with pre-created folders in artifacts, deployed environments must still preserve `.env` and `storage/` across updates.

## Installation strategy

After files are deployed:

1. Open `/install` while app is not yet installed.
2. Run environment checks.
3. Submit DB and admin credentials.
4. Installer runs migrations, creates first admin, and marks install state.

Deployment and installation are separate by design:

- deployment = shipping files
- installation = preparing runtime state
