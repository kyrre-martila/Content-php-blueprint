# Editor mode

Editor Mode is a role-gated, session-based foundation for safe inline content editing workflows.

## What Editor Mode can do (v1 foundation)

- Enable/disable per authenticated session for `superadmin`, `admin`, `editor` roles.
- Show a visible "Editor Mode Active" banner while mode is enabled.
- Load editor-mode CSS/JS assets only while mode is enabled.
- Prepare safe frontend hooks for future inline field editing.

## What Editor Mode cannot do (explicit boundary)

Editor Mode must not allow:

- layout editing
- template editing
- pattern editing
- CSS editing
- JS editing
- routing changes
- PHP file editing

Editor Mode is content-safe only. Presentation architecture and system code remain under developer control.

## Current implementation notes

- Activation routes: `POST /editor-mode/enable`, `POST /editor-mode/disable`.
- Permission checks are role-aware and enforced before mode activation.
- Mode state is stored in session under a dedicated key.
- Unauthorized activation/disable attempts return a safe forbidden response.

## Planned direction (next steps)

- inline editable field mapping
- safe save endpoint with strict field allowlist
- field validation and error feedback
- media replacement for supported field types
- richer editor controls that preserve deterministic rendering
