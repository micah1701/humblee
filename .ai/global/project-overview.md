# Project Overview — Architecture & Routing

## What Humblee Is

Humblee is a PHP/MySQL CMS and framework. It manages pages, content blocks, templates, users, media, and personalization variants. The admin UI is a set of Svelte SPAs served as built ES modules.

A pre-migration snapshot of the codebase is preserved on the `legacy` git branch (tagged `v0-legacy`). Reference it when comparing against the original architecture.

## Directory Map

```
/
├── application/          # App\  namespace — overrides/extends core
│   ├── Controller/       # Custom AJAX endpoints (e.g. Request.php)
│   ├── Middleware/       # App middleware (auto-loaded by Kernel, must implement Contract)
│   ├── Model/            # App models
│   └── views/            # App-level PHP view templates
│
├── frontend/             # Frontend build sources (NOT web-accessible)
│   └── apps/             # One Vite/Svelte project per admin tool
│
├── humblee/              # Humblee\ namespace — CMS core
│   ├── src/
│   │   ├── Foundation/   # Core.php (auth, view, session), Draw.php (helpers)
│   │   ├── Middleware/   # Kernel, Package, Auth, Router, Contract
│   │   ├── Controller/   # Admin, User, Media, Template, Request, Xhr
│   │   │   └── Requests/ # Sub-controllers (static method groups)
│   │   └── Model/        # Content, Crypto, Media, Pages, Personalization, Tools, Users
│   ├── views/            # CMS admin/user/email PHP templates
│   ├── configuration/    # env_*.php + crypto/key.php
│   └── init.php          # Bootstrap: autoload → DB → Kernel::boot()
│
├── public/               # Apache web root
│   ├── index.php         # Public entry point
│   └── humblee/js/admin/ # Built Svelte output (committed)
│
└── storage/              # User-uploaded media (writable, not web-accessible)
```

## Request Lifecycle

```
Browser → Apache → public/index.php
  → humblee/init.php
    → config, autoload, DB, session_start, timezone
    → Kernel::boot()
        → Package::build()          # Normalize request data (GET/POST/PUT/DELETE/PATCH)
        → Auth::handle()            # Restore session from remember-me cookie if needed
        → App\Middleware\*::handle()# Any files in application/middleware/ (auto-discovered)
        → Router::handle()
            → Core::getURIparts()   # Parse URI into segments
            → match($uri[0]) {      # Route on first segment
                'request/'      → App\Controller\Request
                'admin/'        → Humblee\Controller\Admin
                'core-request/' → Humblee\Controller\Request
                'user/'         → Humblee\Controller\User
                'media/'        → Humblee\Controller\Media
                default         → Humblee\Controller\Template
              }
            → $controller->$method()# Second URI segment = method
```

## Routing Table

| URI Prefix | Controller | Namespace | Purpose |
|---|---|---|---|
| `request/` | Request | `App\Controller` | Custom app AJAX |
| `admin/` | Admin | `Humblee\Controller` | CMS admin UI pages |
| `core-request/` | Request | `Humblee\Controller` | Core CMS AJAX |
| `user/` | User | `Humblee\Controller` | Login, register, profile |
| `media/` | Media | `Humblee\Controller` | Media serving + decrypt |
| *(any)* | Template | `Humblee\Controller` | Public-facing pages |

## AJAX Sub-routing

`core-request/` dispatches by third URI segment to static method groups in `humblee/src/Controller/Requests/`:

| Third segment | Handler class |
|---|---|
| `blocks` | `Requests\Blocks` |
| `templates` | `Requests\Templates` |
| `media_files` | `Requests\MediaFiles` |
| `users` | `Requests\Users` |
| `pages` | `Requests\Pages` |
| `content` | `Requests\Content` |
| `personalization` | `Requests\Personalization` |

Example: `POST /core-request/content/save` → `Requests\Content::save($controller)`

## Namespace Map

| Namespace | Directory | Autoloaded by |
|---|---|---|
| `Humblee\Foundation\` | `humblee/src/Foundation/` | Composer |
| `Humblee\Middleware\` | `humblee/src/Middleware/` | Composer |
| `Humblee\Controller\` | `humblee/src/Controller/` | Composer |
| `Humblee\Model\` | `humblee/src/Model/` | Composer |
| `App\Controller\` | `application/Controller/` | Composer |
| `App\Middleware\` | `application/middleware/` | `require_once` by Kernel (file-based discovery) |
| `App\Model\` | `application/Model/` | Composer |

Views (PHP templates) are **not autoloaded** — they are `include`d by `Core::view()`.
