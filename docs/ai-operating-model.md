# AI operating model

This repository uses a three-layer AI context model. AI agents should reason across all three layers together, not in isolation.

## Layer 1: Repository source code (presentation/runtime truth)

Repository source code defines how the site actually runs and renders.

This layer includes:

- templates (`templates/`)
- patterns (`patterns/`)
- CSS/JS assets (`public/assets/`)
- controllers, services, routing, and runtime behavior (`src/`, `public/index.php`)

Use this layer for decisions about rendering behavior, runtime wiring, boundaries, security, and implementation details.

## Layer 2: Composition snapshot export (page-assembly truth)

Composition snapshots describe how pages are assembled for this specific blueprint.

This layer includes:

- route-level page composition metadata
- ordered pattern blocks for routes/pages
- renderer/layout composition hints used for blueprint-aware tooling

Important boundary:

- composition snapshots are blueprint-specific assembly artifacts
- composition snapshots are not portable cross-CMS content packages
- composition snapshots should not absorb repository implementation internals

## Layer 3: OCF export (content truth)

OCF exports describe portable structured content independent of presentation.

This layer includes:

- content types
- content items
- field values
- SEO metadata where applicable

Important boundary:

- OCF is content-only
- OCF excludes presentation concerns (templates, pattern renderer implementation details, CSS classes, runtime wiring)

## How AI should reason across layers

When generating, reviewing, or refactoring:

1. Use repository code to understand runtime and presentation behavior.
2. Use composition snapshots to understand route/page assembly in this blueprint.
3. Use OCF exports to understand portable content structure.
4. Keep these layers separate while cross-referencing all of them.

Do not collapse these layers into one mixed schema. The separation is intentional and supports safe AI-assisted development, portability, and maintainability.
