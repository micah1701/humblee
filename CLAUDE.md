# Humblee — Claude Code Context

## Project overview

Humblee is a PHP/MySQL CMS and framework. The backend PHP lives in `/humblee/`, the public web root in `/public/`, and the application extension layer in `/application/`. A pre-migration copy of the codebase is preserved on the `legacy` git branch (tagged `v0-legacy`).

## PHP version requirement

**PHP 8.3+** — `composer.json` enforces this. All code uses `declare(strict_types=1)`, typed properties, union types, and native sodium. Do not introduce patterns that require a polyfill or compatibility shim.

## Directory structure

```
/
├── application/                  # App-level extension layer (overrides/extends humblee core)
│   ├── Controller/               # App\Controller\ namespace (e.g. Request.php extends Xhr)
│   ├── Model/                    # App\Model\ namespace
│   └── views/                    # App view templates (PHP includes — not autoloaded)
│
├── frontend/                     # Frontend tool source (NOT web-accessible)
│   ├── package.json              # npm workspace root — "workspaces": ["apps/*"]
│   └── apps/
│       └── media-manager/        # Svelte + Vite + TypeScript media manager SPA
│           ├── vite.config.ts    # Builds to /public/humblee/js/admin/media-manager/
│           └── src/
│               ├── main.ts       # Reads PHP config from window.__MEDIA_CONFIG__
│               ├── App.svelte    # Root component
│               └── lib/          # MediaManager component, types, API service, utils
│
├── humblee/                      # CMS core
│   ├── src/                      # PSR-4 source — Humblee\ namespace
│   │   ├── Foundation/           # Core.php (routing/auth/views), Draw.php (view helpers)
│   │   ├── Controller/           # Admin, Media, Request, Template, User, Xhr
│   │   └── Model/                # Content, Crypto, Media, Pages, Personalization, Tools, Users
│   ├── views/                    # CMS admin/user/email view templates
│   ├── configuration/            # env_*.php files, crypto key file
│   ├── vendor/                   # Composer-managed dependencies
│   ├── composer.json
│   └── init.php                  # Bootstrap: loads Composer autoloader, routes requests
│
├── public/                       # Web root (Apache points here)
│   ├── humblee/                  # CMS frontend assets (JS, CSS, images)
│   │   └── js/admin/
│   │       ├── pages.js          # Admin page-ordering UI
│   │       └── media-manager/    # Built Svelte output (index.js + index.css — committed)
│   └── application/              # App frontend assets
│
└── storage/                      # User-uploaded media files (must be web-server writable)
```

## Autoloading

Composer PSR-4 maps:
- `Humblee\` → `humblee/src/`
- `App\` → `application/`

The autoloader is loaded in `humblee/init.php` via `require_once 'humblee/vendor/autoload.php'`. There is no custom SPL autoloader.

## Routing

`humblee/init.php` reads the URI via `Core::getURIparts()` and routes on the first segment:

| URI prefix     | Controller                          |
|----------------|-------------------------------------|
| `request/`     | `App\Controller\Request`            |
| `admin/`       | `Humblee\Controller\Admin`          |
| `core-request/`| `Humblee\Controller\Request`        |
| `user/`        | `Humblee\Controller\User`           |
| `media/`       | `Humblee\Controller\Media`          |
| *(anything else)*| `Humblee\Controller\Template`    |

The second URI segment becomes the method name called on the controller.

## Key patterns

### ORM
All database access uses Idiorm (`j4mie/idiorm`). Always prefix with `\ORM::`. Table name constants are defined in the configuration (`_table_users`, `_table_pages`, `_table_content`, etc.).

### Auth / RBAC
Session-based. `Core::auth(int|string|array $role)` checks `$_SESSION[session_key]['has_roles']`. Roles are cached in session on first check. The `session_key` constant is defined in configuration.

### CSRF / HMAC
Every POST form must include `hmac_token` and `hmac_key` fields. `Crypto::get_hmac_pair()` generates them; `Crypto::check_hmac_pair()` validates. Controllers check this in `__construct()` before processing POST data.

### Passwords
Passwords are stored using `password_hash($password.'-'.$user_id, PASSWORD_ARGON2ID)`. `Users::logIn()` tries `password_verify()` first, then falls back to the legacy sodium BLAKE2b hash for accounts that haven't logged in since the migration, and auto-upgrades to Argon2ID on a successful legacy match. `Users::stringToSaltedHash()` is kept only for this backward-compatibility path — do not use it for new password storage.

### Encryption
`Crypto::encrypt()` / `Crypto::decrypt()` use `sodium_crypto_secretbox`. Both return the ciphertext and nonce as separate values; both must be stored for decryption.

### Personalization (p13n)
`Humblee\Model\Personalization` manages audience-segmented content variants. Content rows have a `p13n_id` foreign key; `0` means default (no personalization).

## Frontend build system

Admin UI tools that need a modern component framework live in `frontend/apps/`. Each app is a self-contained Vite project (Svelte, React, etc.) with its own `package.json` and `vite.config.*`. All apps share a workspace root at `frontend/package.json`.

### Build commands (run from `/frontend/`)

```bash
npm run build                   # build all apps
npm run build:media-manager     # build one app
npm run dev:media-manager       # Vite dev server for iteration
```

Built output (`index.js` + `index.css`, fixed filenames — no hash) is written directly to `public/humblee/js/admin/[app-name]/` and **committed to git**, consistent with other compiled assets in the repo.

### PHP ↔ Svelte/React config handoff

PHP passes runtime config to the JS app via a global injected before the module script. The view template sets `window.__MEDIA_CONFIG__` and provides a `<div id="app">` mount point:

```php
<script>
window.__MEDIA_CONFIG__ = {
    hasMediaRole: <?php echo json_encode((bool)$hasMediaRole) ?>,
    XHR_PATH:     "<?php echo _app_path ?>core-request/",
    ...
};
</script>
<div id="app"></div>
```

The Svelte/React entry point reads from that global. Never hardcode environment-specific values (URLs, roles) in the frontend source.

### Controller asset loading

The controller method for that page sets `$this->extra_head_code` to load the built module:

```php
$this->extra_head_code = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/[app-name]/index.css">';
$this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/[app-name]/index.js"></script>';
```

Use `type="module"` — Vite outputs ES modules.

### Adding a new tool

1. Scaffold: `cd frontend/apps && npm create vite@latest [tool-name] -- --template svelte-ts` (or `react-ts`)
2. Update `vite.config.*`:
   - Set `build.outDir` to `resolve(__dirname, '../../../public/humblee/js/admin/[tool-name]')`
   - Set `build.emptyOutDir: true`
   - Set fixed output filenames (no hash) via `rollupOptions.output`
   - Add the `removeHtml()` plugin from `media-manager/vite.config.ts` to prevent Vite from writing a stray `index.html` into the public directory
3. Update the relevant PHP view to inject `window.__[TOOL]_CONFIG__` and add `<div id="app"></div>`
4. Update the relevant controller method to load the CSS and JS from `extra_head_code`
5. Run `npm run build` from `/frontend/` — the new app is included automatically

## Hard constraints — do not change these

- **`humblee/views/`** — CMS admin/user/email templates. Do not modify as a side effect of backend changes. Intentional frontend replacements (e.g. migrating a view to a Svelte SPA) may update a view, but this should be the explicit purpose of the task.
- **Database schema** — table structure and column names are not managed in this repo; changes require a separate migration.
- **`application/views/`** — app-level view templates.

## Composer dependencies

| Package | Purpose |
|---------|---------|
| `j4mie/idiorm` | ORM (required) |
| `twilio/sdk` | SMS / 2FA (optional — gated by `$_ENV['config']['TWILIO_Enabled']`) |
| `tinify/tinify` | Image compression (optional) |
| `erusev/parsedown` | Markdown → HTML for content blocks |

`paragonie/sodium_compat` was removed — PHP 8.3 ships with native sodium.

## Security notes

- All URI slug segments are validated against `[\w\-\.]+` in `Pages::getPagefromURL()` before use in SQL.
- `eval()` has been eliminated. `Pages::drawMenu_UL()` accepts a `callable` for `li_format`; `Tools::CRUD()` validators must be callables `fn($val): bool`; page ordering uses JSON (`json_decode`) not eval.
- Admin-only AJAX actions live under `core-request/`; app-level AJAX under `request/`. Both extend `Humblee\Controller\Xhr` which provides `require_hmac()` and `require_role()`.
