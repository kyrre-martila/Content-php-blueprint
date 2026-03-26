# Admin rules

Admin UI is editor-safe by design.

## Allowed

- Authenticate and manage structured content records.
- Create/update content items within validation rules.

## Forbidden

- Template editing.
- Runtime schema editing.
- Ad hoc route creation.
- Direct infrastructure configuration mutation.

## Security controls in v0.1

- Session-based authentication gate.
- CSRF token required on all admin POST routes.
- Validation errors shown without leaking internals.
- Production error responses avoid stack traces.
