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

## Deployment Strategy

Supports:

shared hosting  
VPS  
Docker (optional)  

Production deployment checklist:

composer install --no-dev --optimize-autoloader  
run migrations  
configure .env  
ensure writable storage  

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

redirect manager  
SEO fields  
draft/publish workflow  
scheduled publishing  
media transformations  
navigation builder  
site settings editor  

---

## Version 0.3 Planned Features

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
