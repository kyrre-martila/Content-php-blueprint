# Dev mode

Dev Mode is a high-trust, role-gated presentation editing mode.

## What Dev Mode can do (current)

For authenticated `superadmin` and `admin` users, with Dev Mode enabled in session:

- list editable files in approved roots
- open/edit/save approved files
- log updates to `storage/logs/dev-mode-edits.log`

Approved roots:

- `templates/`
- `patterns/`
- `public/assets/css/`
- `public/assets/js/`

## What Dev Mode cannot do (current)

- edit `src/` core architecture (Domain/Application/Infrastructure internals)
- edit migrations, `.env`, `vendor/`, or unrestricted filesystem paths
- bypass allowed extension checks and path normalization rules

## Operating boundary

Dev Mode is a **presentation-safe layer** only.

It is intentionally not an app-core code editor.

## Planned direction (not yet implemented)

- More granular policy controls and richer audit review tooling.
