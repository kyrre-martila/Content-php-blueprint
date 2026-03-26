# Skill: refactor-module

## Goal
Refactor safely while preserving architecture boundaries.

## Workflow
1. Identify current layer violations and target module boundary.
2. Move code with strict typing and constructor injection.
3. Keep external behavior stable with regression tests.
4. Run Pest + PHPStan before and after.
5. Document notable architectural decisions in `docs/architecture.md`.
