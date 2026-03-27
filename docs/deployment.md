# Deployment

This document separates developer setup, deployment, and installation so AI/human operators follow the same lifecycle.

## Developer setup (current)

1. `composer install`
2. `cp .env.example .env`
3. Configure DB and app env values.
4. Run migrations (`composer migrate`).
5. Serve with web root on `public/`.

## Deployment strategy (current)

### Recommended mode (public mode)

- Configure web root to `public/`.
- Requests execute `public/index.php` directly.
- Keeps source/config/migrations outside document root.

### Compatibility mode

- Use project root as web root where hosting cannot point to `public/`.
- Root `index.php` delegates to `public/index.php`.
- This is supported for hosting compatibility, not preferred security posture.

## Installation strategy (current)

After files are deployed:

1. Open `/install` while app is not yet installed.
2. Run environment checks.
3. Submit DB and admin credentials.
4. Installer runs migrations, creates first admin, and marks install state.

Deployment and installation are separate by design:

- deployment = shipping files
- installation = preparing runtime state

## Planned direction (not yet implemented)

- Production release packaging conventions (zip artifacts with vendor and optimized autoloading) as a standardized release process.
