# Admin rules

Admin features are intentionally split into two trust zones.

## 1) Standard admin/editor workflows (content-safe)

Applies to:

- login/logout
- dashboard
- content create/edit/list
- Editor Mode toggle + inline content updates

Allowed:

- manage content item records
- manage ordered pattern block data for content
- update safe inline text/textarea values in Editor Mode

Not allowed:

- edit templates, CSS, JS, or PHP source
- alter routing rules
- change infrastructure internals or secrets

## 2) Dev Mode workflows (high-trust presentation editing)

Applies to authenticated superadmin/admin users only.

Allowed roots:

- `templates/`
- `patterns/`
- `public/assets/css/`
- `public/assets/js/`

Not allowed:

- `src/Domain/`, `src/Application/`, database infrastructure internals
- migrations and environment secrets (`.env`)
- `vendor/`

## Security controls currently enforced

- Session-based auth with role checks.
- CSRF protection on admin POST requests.
- Dev Mode path normalization and traversal prevention.
- Dev Mode extension allow-list by area.
- Dev Mode update audit log (`storage/logs/dev-mode-edits.log`).

## Planned direction (not yet implemented)

- More granular permission policies per feature area.
- Broader audit trails for content updates beyond current scope.
