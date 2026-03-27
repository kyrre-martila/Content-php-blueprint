# Editor mode

Editor Mode is a role-gated, inline editing mode intended for safe content operations.

## What Editor Mode can do (current)

- Enable/disable per authenticated session for `superadmin`, `admin`, `editor` roles.
- Edit content title inline on content pages.
- Edit pattern block fields inline when field type is `text` or `textarea`.
- Persist changes through repository save flow.

## What Editor Mode cannot do (current)

- Reorder layout structure outside defined pattern block order in admin form.
- Edit templates, patterns, CSS, or JS source files.
- Edit domain/application/infrastructure code.
- Mutate arbitrary database schema or unrestricted fields.

## Operating boundary

Editor Mode is a **content-safe layer** only.

It exists so editors can update copy safely without crossing into presentation/system code.

## Planned direction (not yet implemented)

- Additional field-level capabilities may be added, but only when they keep editor safety and deterministic rendering intact.
