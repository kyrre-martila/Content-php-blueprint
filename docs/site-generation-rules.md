# Site generation rules

Use this file when generating a new site from this blueprint.

## Mandatory generation rules

1. Follow existing layered architecture (`Domain`, `Application`, `Infrastructure`, `Http`, `Admin`, `templates`, `patterns`).
2. Prefer extending existing systems over creating parallel systems.
3. Keep templates deterministic and free from business logic.
4. Prefer reusable patterns over ad hoc page markup.
5. Keep editor-safe boundaries intact (content editing only in Editor Mode/admin).
6. Keep dev-mode boundaries intact (presentation-layer files only).
7. Do not introduce drag-and-drop/layout-chaos editing models.
8. Use content types and patterns deliberately; avoid one-off undocumented structures.
9. Use explicit file placement and naming aligned to existing repository conventions.
10. Update docs and skills when introducing architectural behavior changes.

## Generation checklist

- Add/extend content types with migrations + repository/application updates.
- Add/extend templates under `templates/` with escaped output.
- Add/extend patterns under `patterns/{slug}/` with valid `pattern.json`.
- Wire admin and route updates in `Kernel` explicitly.
- Add tests/static checks where behavior changed.
- Document current vs planned behavior in `docs/`.

## Explicit anti-rules

- Do not add runtime AI API integrations for this workflow phase.
- Do not expose unrestricted source editing to editor-facing features.
- Do not bypass CSRF/auth/path safety controls.
