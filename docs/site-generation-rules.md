# Site generation rules

Use this file when generating or refactoring sites from this blueprint.

## Core operating rules

1. Keep layered architecture boundaries (`Domain`, `Application`, `Infrastructure`, `Http`, `Admin`, `templates`, `patterns`).
2. Treat repository code as **presentation/runtime truth**.
3. Treat OCF export as **content truth**.
4. Treat composition snapshot export as **page-assembly truth**.
5. Never collapse these three layers into one schema.
6. Keep templates deterministic and free of business logic.
7. Prefer extending existing patterns over ad hoc template markup.
8. Preserve editor-safe and dev-safe boundaries.
9. Update docs and skills when architecture or operations change.

## Layer-boundary rules

- Keep OCF content-only and portable.
- Do **not** add layout/template/runtime implementation details to OCF.
- Keep composition snapshots blueprint-specific.
- Do **not** add repository internals or source-level implementation details to composition snapshots.
- Keep repository source as the home for rendering/runtime concerns (templates, patterns, CSS/JS, controllers/services).

## Safe generation checklist

- Extend content types through migrations + repository/application updates.
- Extend templates under `templates/` with escaped output.
- Extend patterns under `patterns/{slug}/` with valid `pattern.json` and renderer.
- Wire route/controller changes explicitly in kernel/registrars.
- Add or update tests/checks where behavior changed.
- Document what changed in `docs/`.

## Anti-rules

- Do not mix content, composition, and runtime concerns in one export contract.
- Do not introduce drag-and-drop layout chaos editing.
- Do not expose unrestricted source editing to editor-facing workflows.
- Do not bypass CSRF/auth/path safety controls.
- Do not add runtime AI API integrations unless explicitly planned.
