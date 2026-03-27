# Editor mode

Editor Mode is a role-gated, session-based inline editing feature for safe content updates.

## What Editor Mode can do (v1)

- Enable/disable per authenticated session for `superadmin`, `admin`, `editor` roles.
- Show a visible "Editor Mode Active" banner while mode is enabled.
- Load editor-mode CSS/JS assets only while mode is enabled.
- Render inline editable markers for approved fields.
- Persist approved inline edits through `POST /editor-mode/save-field`.

## What Editor Mode cannot do (explicit boundary)

Editor Mode must not allow:

- slug editing
- status editing
- content type editing
- template selection editing
- pattern ordering changes
- arbitrary JSON field editing
- layout editing
- template editing
- pattern editing
- CSS editing
- JS editing
- routing changes
- PHP file editing

Editor Mode is content-safe only. Presentation architecture and system code remain under developer control.

## Editable field scope in v1

Only these fields are editable:

- content item `title`
- pattern block fields declared as `text`
- pattern block fields declared as `textarea`

All other fields are rejected by validator rules.

## Save flow

1. Frontend clicks an element with `data-editable="true"`.
2. Inline input/textarea is shown temporarily.
3. On blur, browser sends `POST /editor-mode/save-field`.
4. `EditableFieldValidator` enforces mode + allowlist + pattern metadata checks.
5. Controller persists through `ContentItemRepositoryInterface` only.

## Current implementation notes

- Activation routes: `POST /editor-mode/enable`, `POST /editor-mode/disable`.
- Save route: `POST /editor-mode/save-field`.
- Permission checks are role-aware and enforced before mode activation.
- Mode state is stored in session under a dedicated key.
- Unauthorized activation/disable attempts return a safe forbidden response.
- Unsupported inline updates return controlled JSON validation errors.

## Why layout stays protected

Inline editing is intentionally bounded to field values only. Layout structure, template files, and pattern definitions remain outside Editor Mode to preserve deterministic rendering and avoid accidental design breakage.

## Planned direction (next steps)

- media replacement for supported field types
- richer editor controls that preserve deterministic rendering
- pattern insert/remove/reorder workflows with explicit safeguards
- publish workflow integration and audit/undo history
