# AI operating model

This repository uses a multi-layer context model so AI agents can reason safely without mixing concerns.

## Current implementation model

### Layer 1: Source code (runtime/presentation truth)

Use repository code for what the application actually does now:

- routing and controllers (`src/`, `public/index.php`)
- template resolution/rendering (`templates/`, view services)
- pattern rendering and assets (`patterns/`, `public/assets/`)
- deployment/runtime behavior (`scripts/`, bootstrap/runtime services)

### Layer 2: Composition snapshot export (blueprint assembly truth)

Use composition snapshots for route/page assembly context in this blueprint:

- route-level composition metadata
- ordered pattern blocks per route/page
- renderer/layout hints for tooling

Boundary: composition snapshots are blueprint-specific and do not replace runtime source truth.

### Layer 3: OCF export (portable content truth)

Use OCF for structured, portable content:

- content types
- content items and field values
- portable metadata

Boundary: OCF excludes presentation/runtime details (templates, renderer internals, CSS classes, route wiring).

## Required reasoning approach

When planning or implementing changes:

1. Confirm current behavior in source code.
2. Use docs to enforce architecture/operational boundaries.
3. Use composition snapshots for blueprint assembly context.
4. Use OCF for portable content semantics.
5. Keep hierarchy/categories/relationships conceptually separate.

## Current operational boundaries

- Dev Mode: source-level and privileged (templates/pattern files/source editors/exports).
- Editor Mode: runtime-safe content editing only (title/slug policy/structured values/file refs).
- Release deployment: artifact-driven with persistent runtime state (`.env`, `storage/`).
- Category collections: route-level rendering at `/categories/{groupSlug}/{categorySlug}` with deterministic template resolution.

### Editor Safe Mode enforcement model

Editor restrictions are enforced both in UI and server-side controller policy checks:

- forms hide source-level controls for editors
- controllers sanitize/restrict incoming editor payloads to safe runtime fields
- privileged routes remain inaccessible to editor role

This prevents bypass via direct POSTs and keeps Runtime Editor Mode separate from Dev Mode capabilities.

---

## Future roadmap (not implemented)

- deeper AI-assisted deployment/updater orchestration
- richer AI validation against exported artifacts
- stronger automated consistency checks between docs, source, and export layers
