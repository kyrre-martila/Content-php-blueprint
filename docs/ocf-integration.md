# OCF integration direction

This document defines how Content PHP Blueprint should relate to Open Content Format (OCF).

## Core position

- OCF should represent **content**, not presentation.
- OCF exports should contain portable structured content semantics.
- OCF should not carry templates, layout behavior, pattern rendering code, CSS, JS, or route-specific runtime rendering decisions.

## Blueprint relationship model

Content PHP Blueprint should evolve toward a three-artifact model:

1. **OCF content export**
   - editor-managed content data
   - portable and vendor-neutral
2. **Blueprint composition snapshot export**
   - page assembly structure (pattern ordering/grouping, system route composition)
   - blueprint-specific and tooling-oriented
3. **Repository/Git source**
   - templates, patterns, assets, runtime code, docs, skills
   - presentation and implementation source of truth

## Why composition stays separate from OCF

Separating composition from OCF preserves OCF portability and neutrality:

- content can move between systems without importing this blueprint's rendering model
- blueprint-specific layout/pattern assembly remains explicit, not hidden inside portable content exports
- AI/tooling can still reconstruct page behavior by combining OCF + composition snapshot + repository source

## Current implementation status

Implemented now:

- `App\Application\OCF\OCFExporter` writes `storage/exports/ocf/content-export.json`
- export payload is intentionally **content-only** (types/items/semantic fields/slug/status/content metadata)
- export intentionally excludes templates, pattern rendering details, layout logic, CSS/JS, dev metadata, and repository structure

Still planned:

- blueprint composition snapshot export for page/pattern structure
- repository sync workflows for presentation-layer source changes

This separation keeps architecture clean while allowing export/sync implementation without breaking content portability.
