# Skill: extend-dev-mode

## Purpose
Extend Dev Mode capabilities while preserving high-trust, presentation-only boundaries.

## When to use
- Need to expand editable presentation scope or improve Dev Mode safety/UX.

## Architectural rules
- Dev Mode may edit presentation layer only.
- Keep strict root allow-list + extension allow-list enforcement.
- Preserve audit logging for edits and rejected attempts.

## File placement expectations
- Policy: `src/Infrastructure/Editor/DevMode.php`
- File discovery rules: `src/Infrastructure/Editor/EditableFileRegistry.php`
- Admin controller/UI: `src/Admin/Controller/DevModeController.php`, `templates/admin/dev-mode/*`

## Implementation checklist
- [ ] Define/adjust allowed roots and file types deliberately.
- [ ] Keep path normalization/traversal protection intact.
- [ ] Preserve role gate + explicit enable/disable flow.
- [ ] Maintain edit logging and error reporting.
- [ ] Update `docs/dev-mode.md` and `docs/admin-rules.md`.
