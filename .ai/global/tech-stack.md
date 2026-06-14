# Tech Stack Reference

## Backend

| Item | Detail |
|---|---|
| **PHP** | 8.3+ — strict types, union types, named arguments, native sodium |
| **ORM** | `j4mie/idiorm` ^1.5 — always prefixed `\ORM::` |
| **Password hashing** | `password_hash(..., PASSWORD_ARGON2ID)` |
| **Encryption** | Native `sodium_crypto_secretbox` (XSalsa20-Poly1305) via `Crypto` model |
| **Markdown** | `erusev/parsedown` ^1.8 |
| **SMS / 2FA** | `twilio/sdk` ^5.16 — gated by `$_ENV['config']['TWILIO_Enabled']` |
| **Image compression** | `tinify/tinify` ^1.6 — optional |
| **Autoloader** | Composer PSR-4 via `humblee/vendor/autoload.php` |
| **Namespaces** | `Humblee\` → `humblee/src/`; `App\` → `application/` |
| **Tests** | PHPUnit 12, SQLite in-memory, run from `humblee/` |

## Frontend

| Item | Detail |
|---|---|
| **Framework** | Svelte + TypeScript |
| **Build tool** | Vite (one project per admin SPA) |
| **Workspace** | npm workspaces — `frontend/package.json` → `apps/*` |
| **CSS framework** | Bulma (loaded from `public/node_modules/`) |
| **Build output** | `public/humblee/js/admin/[app-name]/index.js` + `index.css` — committed |
| **Module format** | ES modules (`type="module"`) |

## Infrastructure

| Item | Detail |
|---|---|
| **Web server** | Apache — `.htaccess` handles URL rewriting to `public/index.php` |
| **Entry point** | `public/index.php` → `humblee/init.php` |
| **Database** | MySQL (also supports PostgreSQL — see `install/database_pgsql.sql`) |
| **Storage** | `storage/` — must be web-server writable; not web-accessible |
| **Session** | PHP native sessions keyed by `session_key` constant |

## Constants (defined in `humblee/configuration/env_*.php`)

| Constant | Purpose |
|---|---|
| `_app_path` | Application root URL path (e.g. `/` or `/app/`) |
| `session_key` | Session namespace — `$_SESSION[session_key]` |
| `_table_users` | DB table: users |
| `_table_pages` | DB table: pages |
| `_table_content` | DB table: content |
| `_table_template_blocks` | DB table: template block slots |
| `_table_roles` | DB table: roles |
| `_table_user_roles` | DB table: user-role join |
| `_table_media` | DB table: media metadata |

Additional `_table_*` constants are defined in configuration. Never hardcode table name strings.
