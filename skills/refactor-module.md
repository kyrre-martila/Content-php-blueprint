# Skill: refactor-module

## Purpose
Refactor an existing module while preserving behavior and architecture boundaries.

## When to use
- Module is hard to maintain or violates layer responsibilities.

## Architectural rules
- Preserve public behavior unless change is explicitly requested.
- Move logic to the correct layer, do not create shortcut coupling.
- Keep classes small, explicit, and constructor-injected.

## File placement expectations
- Keep files within existing layer folders unless boundary correction is intentional.
- Update docs when responsibilities move.

## Implementation checklist
- [ ] Identify current boundary issues.
- [ ] Apply minimal structural refactor with typed contracts.
- [ ] Keep/expand regression tests.
- [ ] Run test + static analysis checks.
- [ ] Document architectural outcome in `docs/architecture.md`.
