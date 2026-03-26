# DEVELOPMENT_PLAN.md
Content PHP Blueprint

## Vision

Content PHP Blueprint is a modern, framework-light PHP architecture for structured content websites.

It provides:

- strong content modeling
- strict template control
- editor-safe admin UI
- deterministic routing
- production-ready deployment on standard PHP hosting
- AI-native development workflows

The blueprint is designed for agencies and developers who want the discipline of a headless CMS architecture without requiring a Node.js runtime.

Core philosophy:

Headless in architecture  
Template-driven in rendering  
Strict in structure  
Editor-safe by default  
AI-native in workflow

---

## Goals

Primary goals:

- Create a reusable architecture for structured content websites
- Support classic PHP hosting environments
- Avoid page builder complexity
- Prevent design breakage by editors
- Provide strong content modeling similar to headless CMS systems
- Enable deterministic template routing
- Support AI-assisted development with minimal prompt repetition
- Ensure long-term maintainability

---

## Non-Goals

This blueprint is NOT:

- a WordPress replacement
- a visual page builder
- a Laravel clone
- a plugin ecosystem platform
- a drag-and-drop editor

Instead:

it is a structured content platform blueprint.

---

## Technology Stack

Minimum PHP version:

PHP 8.3+

Core stack:

Composer  
PSR-4 autoloading  
PSR-12 coding style  
Strict typing enabled globally  
Environment configuration via .env  
Statically analyzable architecture  

Testing stack:

Pest  
PHPStan (level max)

Optional future:

Rector (automated refactoring)

---

## Architecture Overview

The blueprint follows a layered architecture.

Layers:

Domain  
Application  
Infrastructure  
Http  
Admin  
Templates  
Configuration  
Public entrypoint  

Directory structure:

project-root/

public/  
index.php  

src/  
Domain/  
Application/  
Infrastructure/  
Http/  
Admin/  

templates/  

config/  

database/  
migrations/  

storage/  

tests/  

docs/  

skills/

---

## Layer Responsibilities

### Domain

Contains:

entities  
value objects  
domain services  
business rules  

Does NOT contain:

database logic  
framework logic  
http logic  
html rendering  

Examples:

ContentItem.php  
Slug.php  
ContentType.php  

---

### Application Layer

Contains:

use cases  
application services  
DTO mapping  
validation orchestration  

Examples:

CreateContentItem.php  
UpdateContentItem.php  
PublishContentItem.php  

---

### Infrastructure Layer

Contains:

database access  
repositories  
filesystem services  
email services  
cache  
environment config loader  

Examples:

MySQLContentRepository.php  
FilesystemMediaStorage.php  
EnvLoader.php  

---

### Http Layer

Contains:

controllers  
request mapping  
middleware  
routing  
response builders  

Examples:

ContentController.php  
AdminController.php  
Router.php  

---

### Admin Layer

Contains:

editor UI logic  
form definitions  
field rendering  
admin routing  
permission handling  

Examples:

ContentEditorScreen.php  
FieldRenderer.php  
AdminRouter.php  

Admin must never contain domain logic.

---

### Templates Layer

Contains:

site rendering templates  
layout templates  
partial components  

Examples:

layouts/default.php  
pages/home.php  
pages/content.php  
components/hero.php  

Templates must never contain business logic.

---

## Routing Strategy

Routing is deterministic.

Example routes:

/about  
/services  
/news/article-title  

Routing pipeline:

Request  
Router  
Controller  
Application service  
Repository  
Template renderer  

---

## Content Model

The blueprint uses structured content types.

Examples:

Page  
Post  
TeamMember  
Event  
Sponsor  

Each content type defines:

fields  
validation rules  
template mapping  
admin editor schema  
routing behavior  

---

## Template System

Templates are mapped deterministically.

Example:

ContentType: Page  
Template: templates/pages/page.php  

Fallback chain:

templates/pages/{slug}.php  
templates/pages/page.php  
templates/default.php  

Editors cannot change templates.

---

## Database Strategy

Relational database (MySQL compatible)

Core tables:

content_types  
content_items  
content_fields  
content_field_values  
media  
users  
roles  
permissions  
slug_redirects  

Rules:

migrations are versioned  
schema must be deterministic  
no manual production-only schema drift  

---

## Admin Philosophy

Admin UI is structured and safe.

Editors can:

edit content  
upload media  
manage structured fields  
create content items  

Editors cannot:

edit templates  
edit routing logic  
edit schema  
edit system configuration  

---

## Authentication Model

Roles:

superadmin  
admin  
editor  

Permissions:

content.create  
content.edit  
content.publish  
media.upload  
settings.manage  
permissions.assign  

---

## Configuration Strategy

Configuration sources:

.env  
config/*.php  

Example environment variables:

APP_ENV  
APP_DEBUG  
APP_URL  

DB_HOST  
DB_PORT  
DB_NAME  
DB_USER  
DB_PASS  

SESSION_NAME  
SESSION_SECURE_COOKIE  

Rules:

no configuration values inside source code  
environment-specific values must stay outside src/  

---

## Error Handling Strategy

Development:

detailed error pages enabled  

Production:

safe error pages  
error logging enabled  
stack traces disabled  

---

## Logging Strategy

Log channels:

application  
security  
database  
errors  

Logs stored in:

storage/logs/

---

## Security Rules

Always:

strict_types enabled  
prepared SQL statements  
CSRF protection  
escaped output  
password hashing via password_hash()  
secure session handling  

Never:

direct SQL concatenation  
template logic execution from user input  
dynamic file inclusion without validation  

---

## Coding Standards

Required:

PSR-12  
strict typing  
constructor injection preferred  
interfaces for repositories  
value objects for identifiers  
DTO objects for structured requests  

Forbidden:

global helper sprawl  
static service locators  
hidden dependencies  
mixed architecture layers  
business logic in templates  
database queries in controllers  

---

## Testing Strategy

Testing stack:

Pest  

Test types:

unit tests  
repository tests  
application service tests  
routing tests  
permission tests  

Example structure:

tests/Domain/  
tests/Application/  
tests/Infrastructure/  
tests/Http/  

---

## Static Analysis

PHPStan required.

Minimum level:

max  

CI must fail on violations.

---

## AI Integration Strategy

Repository includes:

docs/  
skills/  

docs:

architecture.md  
content-model.md  
admin-rules.md  
development-plan.md  
coding-standards.md  
routing.md  

skills:

create-content-type.md  
create-admin-screen.md  
create-template.md  
add-field.md  
refactor-module.md  
write-migration.md  

These allow AI to operate deterministically inside the repository.

---

## AI-First Workflow

Intended workflow:

clone blueprint repository  
let AI generate first site version  
let AI create or extend content types, templates, and patterns  
hand off content operations to editors through safe admin/editor tools  
allow advanced users to extend presentation through dev mode  

Separation rules:

AI-generated structure and templates are managed as repository code  
editor-managed content is managed through approved content fields  
developer-managed presentation logic remains in templates/components/pattern code  

Long-term operating model:

AI builds the system  
Humans refine it  
Editors run it  

---

## Pattern System

Patterns are first-class building blocks of page composition.

Patterns are:

reusable, developer-defined presentation sections  
safe for editors to insert into pages  
aligned with the project design system  
controlled through typed configuration and template mapping  

Patterns are NOT:

freeform drag-and-drop layout construction  
unrestricted HTML/CSS editing for editors  

Ownership model:

editors select and order approved patterns  
developers create and evolve the pattern catalog  
AI may generate new patterns when it follows repository rules and naming conventions  

Target behavior is similar to WordPress patterns, but inside a stricter layered architecture.

---

## Editor Mode

Editor mode is a safe inline content-editing mode for non-technical users.

Editor mode principles:

edit text and approved fields directly while viewing the page  
limit edits to content fields explicitly exposed by schema/template mapping  
prevent layout-breaking capabilities for normal editor roles  
keep template, routing, and pattern implementation under developer control  

This preserves editor-safe defaults while reducing admin friction for routine updates.

---

## Dev Mode

Dev mode is an advanced, role-gated mode for site-building and presentation work.

Dev mode may expose controlled editing for:

patterns  
templates  
components  
CSS  
JS  
design tokens  
template-to-content mapping where appropriate  

Dev mode must NOT expose unrestricted editing of:

core domain logic  
auth internals  
persistence internals  
low-level application core  

Operational requirements:

all dev mode changes should be version-aware  
changes should be auditable  
rollback/history should be designed in from the start  

---

## Deployment and Installation Strategy

Recommended hosting mode:

web root points to public/  

Compatibility mode (optional):

root index.php delegates to public/index.php for limited shared-hosting scenarios  

Release direction:

production releases should be distributable as ready-to-upload zip packages  
production runtime should not require Composer on the server  

Future onboarding direction:

install wizard should guide first-run setup and environment validation  
setup flow should be as simple as realistically possible on standard PHP hosting  

Terminology distinction:

deployment = delivering built release artifacts to hosting  
installation = placing files/config in the target environment correctly  
first-run setup = guided initialization of app settings, database, and admin account  

### Install detection lifecycle

Boot sequence target behavior:

application boot  
detect install state  
if not installed, redirect protected setup-dependent routes (for example admin) to /install  
once setup completes, installer must be locked and normal routes remain active  

Install-state decision should stay lightweight and explicit (for example required-table detection aligned with migrations).

Planned installer responsibilities:

environment checks  
database setup and connectivity validation  
database migration execution  
initial admin account bootstrap  
configuration persistence for runtime use  

---

## Version 0.1 Scope

working router  
content type definition system  
content item CRUD  
admin login  
media upload  
template rendering  
slug routing  
migration system  
basic role system  
.env config loader  
error handling  
logging  
PSR-4 autoloading  
PHPStan config  
Pest setup  
docs structure  
skills structure  

---

## Version 0.2 Planned Features

pattern architecture (registry, schema, rendering contracts)  
editor mode foundation (safe inline field editing)  
dev mode foundation (role-gated presentation editing)  
redirect manager  
SEO fields  
draft/publish workflow  
scheduled publishing  
media transformations  
navigation builder  
site settings editor  
release packaging pipeline (zip artifact for production uploads)  
install wizard foundation (first-run setup flow)  

---

## Version 0.3 Planned Features

full pattern library lifecycle tooling  
editor mode expansion (workflow and review features)  
dev mode expansion (audit history, rollback UX, diff tooling)  
multisite support  
localization support  
plugin modules  
API layer  
headless mode  
OCF export support  

---

## Architectural Rules

Mandatory rules:

All production code must live under PSR-4 namespaces  
Public access goes through public/index.php  
Domain must not depend on Infrastructure  
Templates must not execute business logic  
Controllers must remain thin  
Repositories encapsulate persistence  
Config must be externalized  
Major features must be documented in docs/  
Reusable workflows must exist in skills/  

---

## Quality Bar

This project targets uncompromising quality:

explicit architecture  
minimal hidden magic  
testable business logic  
predictable routing  
secure defaults  
strict standards  
clean file placement  
AI-readable documentation  
production readiness from day one

## Install wizard v1 implemented

Installer v1 now provides a browser route at `/install` with explicit responsibilities:

- environment validation
- migration execution
- initial admin bootstrap
- install-state activation through existing `InstallState` detection rules

Planned future improvements:

- multi-step wizard UX
- configuration persistence UI
- broader extension and platform checks
- module/bootstrap setup hooks
