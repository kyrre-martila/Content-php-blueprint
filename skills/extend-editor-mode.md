# Skill: extend-editor-mode

## Purpose
Extend inline editing capabilities without breaking editor-safety boundaries.

## When to use
- New safe inline content interaction is needed in Editor Mode.

## Architectural rules
- Editor Mode remains content-only.
- No template/source-code editing through Editor Mode.
- Validate editable target/type strictly in controller logic.

## File placement expectations
- Mode policy/service: `src/Infrastructure/Editor/EditorMode.php`
- Update handler: `src/Admin/Controller/EditorModeController.php`
- Client behavior: `public/assets/js/editor-mode.js`
- Rendering hooks: templates/patterns where needed

## Implementation checklist
- [ ] Define allowed target + field type explicitly.
- [ ] Add/update backend validation and persistence flow.
- [ ] Update frontend editable markers/interactions.
- [ ] Ensure CSRF/auth/session requirements remain intact.
- [ ] Update `docs/editor-mode.md` with current limits.
