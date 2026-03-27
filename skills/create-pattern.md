# Skill: create-pattern

## Purpose
Add a reusable presentation pattern consumable by admin content composition and frontend rendering.

## When to use
- A recurring content section should be reusable across pages.

## Architectural rules
- Follow `PatternRegistry` metadata requirements.
- Keep pattern view focused on rendering declared fields only.
- Preserve editor-safe behavior; inline editing only for supported field types.

## File placement expectations
- Metadata: `patterns/{slug}/pattern.json`
- View: `patterns/{slug}/pattern.php`

## Implementation checklist
- [ ] Create slug directory under `patterns/`.
- [ ] Add valid `pattern.json` (`name`, `slug`, `description`, `fields`).
- [ ] Add `pattern.php` with escaped output.
- [ ] Ensure fields align with allowed types (`text`, `textarea`, `image`).
- [ ] Update docs (`docs/pattern-system.md`) if capabilities changed.
