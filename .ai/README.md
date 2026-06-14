# Humblee AI Context System

A layered documentation system for AI-assisted development on the Humblee CMS.

## How to Use

Pick the layer that matches your task:

| Layer | Path | Use when… |
|---|---|---|
| **Global Rules** | `global/` | Starting any task — read rules first |
| **Backend** | `backend/` | Working on PHP controllers, models, views |
| **Frontend** | `frontend/` | Working on Svelte SPAs or the build pipeline |
| **Features** | `features/` | Adding/modifying a specific CMS subsystem |
| **Skills** | `skills/` | Following a step-by-step recipe for a common task |

## File Index

### Global
- [global/rules.md](global/rules.md) — Hard constraints and forbidden patterns
- [global/tech-stack.md](global/tech-stack.md) — Language versions, libraries, toolchain
- [global/project-overview.md](global/project-overview.md) — Architecture, routing, namespace map

### Backend
- [backend/patterns.md](backend/patterns.md) — ORM, auth, HMAC, encryption, JSON output
- [backend/controllers.md](backend/controllers.md) — Controller hierarchy and dispatch
- [backend/models.md](backend/models.md) — Model conventions, ORM query patterns
- [backend/views.md](backend/views.md) — PHP view rendering, Draw helper, asset loading

### Frontend
- [frontend/architecture.md](frontend/architecture.md) — Vite workspace, build pipeline, output paths
- [frontend/config-injection.md](frontend/config-injection.md) — PHP → Svelte config handoff
- [frontend/app-structure.md](frontend/app-structure.md) — Standard Svelte SPA layout

### Features
- [features/auth-rbac.md](features/auth-rbac.md) — Session auth, roles, Core::auth()
- [features/content-blocks.md](features/content-blocks.md) — Template slots, slot keys, serialization
- [features/media.md](features/media.md) — Upload, encryption, storage
- [features/personalization.md](features/personalization.md) — Audience segments, p13n_id
- [features/security.md](features/security.md) — CSRF/HMAC, input validation, XSS prevention

### Skills (step-by-step recipes)
- [skills/add-xhr-endpoint.md](skills/add-xhr-endpoint.md) — New AJAX endpoint
- [skills/add-svelte-app.md](skills/add-svelte-app.md) — New Svelte frontend SPA
- [skills/add-model-method.md](skills/add-model-method.md) — New model method with ORM
- [skills/add-admin-page.md](skills/add-admin-page.md) — New admin page (controller + view)
- [skills/write-tests.md](skills/write-tests.md) — PHPUnit test patterns
- [skills/refactor-checklist.md](skills/refactor-checklist.md) — Safe refactor checklist
