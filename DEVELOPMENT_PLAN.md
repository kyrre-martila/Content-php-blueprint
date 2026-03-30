# DEVELOPMENT_PLAN.md

Content PHP Blueprint

## Purpose

This document is the implementation-oriented source of truth for:

1. **Current runtime architecture** (what exists now), and
2. **Future roadmap** (what is planned, not yet runtime behavior).

When code and this document conflict, update this document to match code.

---

## Current architecture summary

- Layered PHP architecture with clear boundaries: `Domain`, `Application`, `Infrastructure`, `Http`, `Admin`.
- Registrar-based routing coordinated by `src/Http/Routing/RouteRegistry.php`.
- Deterministic template resolution through `TemplateResolver`.
- Pattern-driven composition using `pattern_blocks` rendered through `PatternRenderer`.
- Session-based, role-gated Editor Mode and Dev Mode with explicit scope boundaries.
- Install-state-aware kernel behavior for setup-dependent admin routes.
- Category Group availability rules per Content Type via a pivot mapping table.

---

## Runtime routing architecture (current implementation)

Routes are registered by modular registrars in deterministic order:

1. `SystemRouteRegistrar`
2. `AuthRouteRegistrar`
3. `AdminRouteRegistrar`
4. `DevModeRouteRegistrar`
5. `EditorModeRouteRegistrar`
6. `PublicContentRouteRegistrar` (**catch-all, always last**)

This ordering is the active runtime route-priority model.

### System routes

- `GET /` → home
- `GET /health` → health check
- `GET /search` → search page
- `GET /sitemap.xml` → XML sitemap
- `GET /robots.txt` → dynamic robots policy
- install flow:
  - `GET /install` → install form (or redirect if already installed)
  - `POST /install` → installer execution

### Auth routes

- `GET /admin/login`, `POST /admin/login`
- `POST /admin/logout`
- aliases: `/login`, `/logout`

### Admin routes

- `GET /admin` dashboard
- `GET /admin/patterns` pattern registry endpoint
- `GET /admin/templates` template manager overview
- `GET /admin/categories` category manager overview (group + nested category split layout)
- category group writes:
  - `POST /admin/categories/groups/create`
  - `POST /admin/categories/groups/{id}/edit`
  - `POST|DELETE /admin/categories/groups/{id}`
- category writes:
  - `POST /admin/categories/create`
  - `POST /admin/categories/{id}/edit`
  - `POST|DELETE /admin/categories/{id}`
- content management (`/admin/content`, create/edit/store/update)

### Dev Mode routes

- `/admin/dev-mode/*` primary dev-mode UI/actions
- `/dev/*` aliases
- includes snapshot export endpoint: `POST /admin/dev/export`

### Editor Mode routes

- `/editor-mode/*` primary editor-mode actions
- `/editor/*` aliases
- inline save endpoint: `POST /editor-mode/save-field`

### Public content catch-all route

- `GET /{slug}` → published content rendering via `ContentController`
- must remain last so system/admin/editor/dev/auth routes resolve first

### Install redirect behavior in kernel

`Kernel` redirects setup-dependent admin paths (`/admin` and `/admin/*`) to `/install` when installation is incomplete.

---

## Template system (current implementation)

Template runtime is intentionally minimal and deterministic.

### Active runtime entrypoints

- `templates/index.php` = universal renderer for content routes.
- `templates/system/404.php` = not-found renderer.
- `templates/system/search.php` = search renderer.

### Resolver behavior

- `resolveContentTemplate(ContentType $type)` → `templates/content/{content_type}.php`, fallback `templates/index.php`
- `resolveCollectionTemplate(ContentType $type)` → `templates/collections/{content_type}.php`, fallback `templates/system/404.php`
- `resolveSystemTemplate(string $route)` → `templates/system/{route}.php`, fallback `templates/system/404.php`
- `resolveNotFound()` → `resolveSystemTemplate('404')`

### Layout behavior

Templates set `$layout` (currently `layouts/default.php` in core runtime templates), and `TemplateRenderer` resolves that file under `templates/`.

### Explicitly not current runtime behavior

The following are **not** part of active runtime template selection:

- `templates/pages/{slug}.php`
- `templates/pages/page.php`
- `templates/default.php` as route-resolved default
- WordPress-style fallback chains
- content-type template hierarchy as runtime selection logic
- slug-template hierarchy as runtime selection logic
- editor-selected template switching

Composition is pattern-driven; template dispatch is not hierarchy-driven.

---

## Pattern system (current implementation)

Patterns are filesystem-defined blocks:

- `patterns/{key}/pattern.json`
- `patterns/{key}/pattern.php`

Runtime services:

- `PatternRegistry` for deterministic discovery
- `PatternRenderer` for rendering
- `PatternDataValidator` for field validation

Current supported inline-edit field types in patterns: `text`, `textarea`.

### Content composition model

Content items store ordered `pattern_blocks` JSON blocks, each shaped like:

```json
{ "pattern": "slug", "data": { "field": "value" } }
```

Rendering path:

1. `ContentController` loads published content item.
2. `templates/index.php` iterates `pattern_blocks` in stored order.
3. Each block renders through `PatternRenderer`.

---

## Editor Mode (current implementation)

Editor Mode is session-based and role-gated (`superadmin`, `admin`, `editor`).

### Current capabilities

- enable / disable editor mode
- editor banner + editor assets when active
- inline save endpoint for allowlisted fields

### Current allowlisted inline edits

- content item fields:
  - `title`
  - `meta_title`
  - `meta_description`
  - `og_image`
  - `canonical_url`
  - `noindex`
- pattern block fields of type `text` and `textarea`

### Current hard boundaries

- no template/layout file editing
- no pattern definition editing (`pattern.json`/`pattern.php`)
- no CSS/JS editing
- no routing/PHP source editing
- no unrestricted arbitrary JSON mutation outside validator constraints

---

## Dev Mode (current implementation)

Dev Mode is session-based and role-gated (`superadmin`, `admin`).

### Editable roots

- `templates/`
- `patterns/`
- `public/assets/css/`
- `public/assets/js/`

### Safety model

- allowlisted roots and supported-file discovery
- path normalization + traversal rejection
- blocked sensitive paths (`src`, `vendor`, `.env`, `config`, etc.)
- bounded file size for edits
- edit history logging (`storage/logs/dev-mode-edits.log`)

### Current capabilities

- browse allowlisted editable files
- edit/save allowlisted files
- export snapshot endpoint (`POST /admin/dev/export`) that returns:
  - composition snapshot export result
  - OCF export result when OCF exporter is available

### Not current behavior

- no unrestricted repository editing
- no migration editing
- no package/dependency management workflows
- no guaranteed bidirectional sync/update pipeline beyond current export endpoint behavior

---


## Content relationships system (current implementation)

A dedicated `content_item_relationships` table provides explicit content-to-content links that are separate from navigation hierarchy and separate from category tagging.
Allowed combinations are constrained by `content_type_relationship_rules` (`from_content_type_id`, `to_content_type_id`, `relation_type`).

Boundary rules:

- relationships = free-form directional links between two content items
- relationships are not hierarchy (`content_items.parent_id`)
- relationships are not categories (`content_item_categories`)

Current repository capabilities (`ContentRelationshipRepositoryInterface`):

- `findOutgoingRelationships(ContentItem $item)`
- `findIncomingRelationships(ContentItem $item)`
- `findByType(ContentItem $item, string $relationType)`
- `attach(ContentItem $from, ContentItem $to, string $relationType, int $sortOrder = 0)`
- `detach(ContentItem $from, ContentItem $to, string $relationType)`
- `allowRelationship(ContentType $from, ContentType $to, string $relationType)`
- `isRelationshipAllowed(ContentType $from, ContentType $to, string $relationType): bool`
- `removeRelationshipRule(ContentType $from, ContentType $to, string $relationType)`

Validation and integrity behavior:

- relation type must be non-empty
- self-referential relationships are currently rejected
- duplicate identical triples (`from_content_item_id`, `to_content_item_id`, `relation_type`) are prevented
- relationship writes are rejected unless a matching content-type rule exists

Usage examples modeled for runtime/application layer:

- article -> author (allowed by rule)
- article -> related-article
- event -> venue (allowed by rule)
- page -> page (allowed by rule)
- team-member -> department-page

---

## Category Group gating by Content Type (current implementation)

Blueprint supports explicit Category Group availability per Content Type using `content_type_category_groups` (`content_type_id`, `category_group_id`).

Current behavior:

- content type repository loads allowed category group IDs with each content type load
- repository helpers exist to manage mappings:
  - `getAllowedCategoryGroups(ContentType $type): array`
  - `attachCategoryGroup(ContentType $type, CategoryGroup $group): void`
  - `detachCategoryGroup(ContentType $type, CategoryGroup $group): void`
- category attachment validation enforces that a content item's content type allows the category's group before insert into `content_item_categories`

Editor impact:

- category selectors can be filtered to only groups mapped to the active content type
- invalid cross-type category assignment is blocked at repository validation level

## Category admin management (current implementation)

Admin provides a dedicated Categories management surface with a vertical split layout:

- left: category group listing and group CRUD
- right: selected group category tree and category CRUD

Current behavior:

- nested categories are displayed as an indented tree
- category groups cannot be deleted when they are in use by:
  - existing categories
  - content type group mappings (`content_type_category_groups`)
- categories cannot be deleted when:
  - assigned to content items (`content_item_categories`)
  - child categories still exist

Security boundaries:

- all category admin routes are behind `RequireRoleMiddleware` via `AdminRouteRegistrar`
- all write operations are CSRF protected

Example mapping:

- `BlogPost` -> `Blog categories`
- `Event` -> `Locations`

---

## Install flow and install-state logic (current implementation)

Install sequence:

1. run environment checks
2. validate installer input
3. connect to target DB
4. run Phinx migrations
5. create initial admin user
6. create/update `settings.install_completed`
7. persist `settings.installed_version` from runtime app version
8. run demo seeder (`BlueprintDemoSeeder`)
9. verify install state and redirect to `/admin/login`

Install state is complete only when all checks pass:

- required tables exist (`phinxlog`/configured migrations table, `users`, `content_types`, `settings`)
- admin/superadmin user exists
- install flag is truthy in `settings.install_completed`
- installed runtime version is stored in `settings.installed_version` for upgrade comparisons

---


## Version-awareness and upgrade-state foundation (current implementation)

Current source of truth for application identity/version:

- `config/app.php` defines `app.name` and `app.version`
- `AppVersion` provides explicit accessors (`applicationName()`, `currentVersion()`)

Upgrade-state model:

- installer writes `settings.installed_version` once installation succeeds
- `UpgradeState` compares current code version vs installed version
- admin context receives upgrade-ready flags/data (`upgradeRequired`, current version, installed version)

This enables a future in-admin GitHub release updater while keeping current runtime scope intentionally narrow.

Planned future updater flow (not yet implemented):

1. check release
2. download artifact
3. replace application files safely
4. run upgrade tasks/migrations
5. preserve runtime state

Explicitly not implemented yet:

- release download
- file replacement
- GitHub API integration
- rollback
- automatic updates

---

## Upgrade lifecycle

Upgrade execution is separated from file delivery through a dedicated application service: `App\Infrastructure\Application\UpgradeRunner`.

### Runtime flow

1. deployment/updater replaces application files with a newer version
2. bootstrap builds persistence services and version state
3. `UpgradeRunner::runUpgradeIfNeeded()` compares `app.version` against `settings.installed_version`
4. if versions differ and installation is complete, `runUpgradeTasks()` executes upgrade hooks
5. on success, runtime persists `settings.installed_version = currentVersion` and keeps `settings.install_completed = true`

### Safety behavior

`UpgradeRunner` exits safely (no fatal upgrade failure) when:

- database connection is unavailable
- `settings` table is missing
- installation has not completed yet
- versions already match

### Future integration boundary

- **File updater concern (future GitHub release updater):** release check/download/unpack/file replacement
- **Upgrade runner concern:** post-replacement migrations, data fixes, cache invalidation, composition snapshot refresh, export regeneration, future schema upgrade tasks, and version persistence

This boundary keeps update orchestration and upgrade execution independently testable and safer to evolve.

---

## OCF / composition / Git boundary (current implementation)

Runtime architecture enforces strict separation:

- **OCF export = content only** (portable semantic content model)
- **Composition snapshot = blueprint-specific assembly metadata** (route composition + ordered patterns)
- **Git repository = presentation/runtime source of truth** (`templates`, `patterns`, assets, PHP runtime code, docs, skills)

This keeps portable content from absorbing blueprint-specific rendering/runtime concerns.

### OCF export structure (current)

`OCFExporter` emits content-only payloads with portable semantic structure:

- header metadata:
  - `export_format_version: 2`
  - `ocf_version: "0.1-draft"`
  - `generated_by`
  - `generated_at` (ISO timestamp)
- `content_types` including semantic field schemas
  - dynamic `fieldDefinitions()` are used when provided by the domain model
  - fallback schema remains available for compatibility
- `content_items` including:
  - semantic fields (`title`, `slug`, `status`)
  - explicit hierarchy fields (`parent_id`, `sort_order`) for optional tree-like content
  - `pattern_blocks` as structured semantic content data (not layout instructions)
  - relationships object with `content_type` and future-safe room for `related_items`
  - optional SEO metadata (`meta_title`, `meta_description`, `canonical_url`) when present
  - creation/update timestamps

Hierarchy rules:

- Hierarchy uses dedicated `parent_id` references and `sort_order` for sibling ordering.
- Hierarchy is optional capability support (e.g., pages/docs/navigation-oriented content), not a mandatory behavior across all content types.
- Hierarchy is separate from Category Group and Category concerns.
- Hierarchy is separate from the generic relationships payload.

### Boundary clarification

- OCF export includes portable structured content and semantics only.
- Composition snapshot export includes blueprint-specific composition/assembly context.
- Runtime rendering remains responsible for templates/layout/renderers/assets.
- OCF export does **not** include templates, layout files, renderer entrypoints, CSS classes, or pattern rendering instructions.

### Composition snapshot format (current)

`CompositionExporter` emits `export_format_version: 2` snapshots.

Scopes:

- `scope: content-routes` (per-content snapshot)
- `scope: system-routes` (system route snapshot)

Current content-route fields:

- `slug`
- `title`
- `route_type` (`content`)
- `renderer_entrypoint` (`templates/index.php`)
- `layout` (current exporter value: `templates/layout.php`)
- `patterns` (ordered pattern blocks)

Current system-route fields:

- `route`
- `renderer_entrypoint` (`templates/system/*.php`)
- `layout` (current exporter value: `templates/layout.php`)
- `patterns`

Notes:

- `renderer_entrypoint` is the canonical field (replacing older `template` naming).
- Snapshot metadata is tooling/export context and does not redefine runtime route matching rules.

---

## SEO runtime capabilities (current implementation)

### Sitemap

- `GET /sitemap.xml`
- includes published content items
- emits `loc` + `lastmod`
- uses `canonical_url` when available, otherwise `APP_URL + slug`

### robots.txt

- `GET /robots.txt` dynamic output
- `APP_ENV=production`: allows crawling and references sitemap
- privileged/operator paths disallowed in production (`/admin`, `/editor`, `/editor-mode`, `/dev`, `/install`)
- non-production: `Disallow: /`

### Social metadata + structured data

Handled through dedicated renderers coordinated by `TemplateRenderer` (not per-template duplication):

- `SeoMetaRenderer`
  - canonical tag
  - meta description
  - OpenGraph tags
  - Twitter card tags
- `StructuredDataRenderer`
  - JSON-LD graph output
  - WebSite, Organization, WebPage, Article (when applicable), BreadcrumbList (when data exists)

Architectural guardrail: `TemplateRenderer` coordinates rendering, but future cross-cutting rendering concerns must be implemented as dedicated services and delegated from the coordinator.

---

## Future roadmap (not current runtime behavior)

Only the items below are roadmap-level (planned/possible), not guaranteed current behavior:

### SEO roadmap

- sitemap index generation
- image sitemaps
- module-contributed sitemap entries
- multilingual sitemap variants
- richer robots customization (custom rules / overrides)
- per-content-type indexing policies

### Metadata roadmap

- site-level default OG image wiring from settings/config
- configurable `og:type` mapping by content type
- OG image dimension tags
- multilingual metadata variants
- expanded schema coverage (product, event, FAQ, author)

### Deployment ergonomics roadmap

- deployment orchestration improvements layered on top of current release artifact packaging

---

## Consolidated AI operating model (finalized)

This project is now structured so AI can reliably understand and operate the system from four complementary sources:

1. source code
2. docs
3. OCF export
4. composition snapshot export

### Layer responsibilities

- **Source code** is runtime/presentation truth (templates, patterns, assets, routing, services, controllers).
- **Docs** are architectural/operational truth for intent, boundaries, and workflows.
- **OCF export** is portable content truth (content types, content items, field values, SEO metadata where applicable).
- **Composition snapshot export** is blueprint-specific page assembly truth (route composition + ordered pattern blocks).

These layers must remain separate and be reasoned about together.

### Forward-looking execution model

- Future GitHub updater work will operate primarily on the **source layer** (release delivery and source replacement orchestration).
- Future editor-driven content export/import workflows will operate primarily on the **content layer** (OCF and related content-safe artifacts).
- Future AI-assisted site generation will combine:
  - blueprint manifests,
  - export artifacts (OCF + composition snapshot), and
  - repository docs/rules.

This separation is the foundation for safe AI automation and predictable architecture evolution.


## Category Groups and Categories

Blueprint includes a classification system built around **Category Groups** and **Categories**.

- A **Category Group** contains Categories (for example: Blog categories, Product categories, Locations, Departments).
- A **Category** classifies content items (for example: News, Events, Kirkenes, Senior team).
- Categories are not the same as content relationships/hierarchy between content items.
- Categories support optional hierarchy through `parent_id`, so nested categories are supported.

Data model summary:

- `category_groups`: stores the high-level grouping container.
- `categories`: stores individual categories and optional parent/child nesting.
- `content_item_categories`: pivot table linking content items to categories.

Domain and persistence summary:

- Domain models: `CategoryGroup`, `Category`.
- Repository contracts: `CategoryGroupRepositoryInterface`, `CategoryRepositoryInterface`.
- Infrastructure implementations: `MySqlCategoryGroupRepository`, `MySqlCategoryRepository`.

Repository helper methods include:

- `findAllGroups()`
- `findCategoriesByGroup(CategoryGroup $group)`
- `findRootCategoriesByGroup(CategoryGroup $group)`
- `findChildrenOf(Category $category)`
- `findCategoriesForContentItem(ContentItem $item)`
- `attachCategoryToContentItem(ContentItem $item, Category $category)`
- `detachCategoryFromContentItem(ContentItem $item, Category $category)`
