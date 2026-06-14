# Global Rules — Hard Constraints

These rules override everything else. Violating them breaks the system or introduces security issues.

## PHP

- **PHP 8.3+ only.** All files begin with `declare(strict_types=1)`. Use typed properties and union types. No polyfills.
- **No `eval()`, no `preg_replace` with `/e` modifier, no `include`/`require` on user input.** Static file paths only.
- **No raw SQL.** All database access uses `\ORM::for_table()`. The ORM handles escaping.
- **CSRF on every POST.** Every controller action that modifies data must call `$this->require_hmac()` (in Xhr subclasses) or validate `Crypto::check_hmac_pair()`. No exceptions.
- **Role check before data access.** Call `Core::auth($role)` or `$this->require_role($role)` before any privileged operation. Models must also enforce roles with `Core::auth()` internally.
- **Password hashing: Argon2ID only for new hashes.** Use `password_hash($password . '-' . $user_id, PASSWORD_ARGON2ID)`. The legacy sodium BLAKE2b path exists only for backward-compatible login — do not use `Users::stringToSaltedHash()` for any new feature.
- **Encryption: libsodium secretbox only.** Use `Crypto::encrypt()` / `Crypto::decrypt()`. The nonce is embedded in the ciphertext — do not add a nonce column to any table.
- **URL slugs validated before SQL.** Match against `/^[\w\-\.]+$/` before using a slug segment in a query. See `Pages::getPageFromURL()`.

## Database

- **Do not alter table structure in this repo.** Schema changes belong in a separate migration. Never add or remove columns, rename tables, or change constraints here.
- **Always use table-name constants.** Reference `_table_users`, `_table_pages`, `_table_content`, `_table_template_blocks`, etc. — never hardcode table names.

## Frontend

- **Never hardcode URLs or paths in Svelte/TS source.** All environment-specific values (`XHR_PATH`, `WEB_ROOT`, `_app_path`) are injected by PHP via `window.__[APP]_CONFIG__`. Read them from that global.
- **Built output is committed.** `public/humblee/js/admin/[app-name]/index.js` and `index.css` are committed to git. Always run a build after changing frontend source.
- **No hash suffixes on build output.** Filenames are fixed (`index.js`, `index.css`). The vite config must disable content hashing via `rollupOptions.output`.

## Views & Templates

- **Do not modify `humblee/views/` as a side effect.** Only touch CMS admin/user/email templates when the explicit goal is a view change.
- **Do not modify `application/views/` as a side effect.** Same rule for app-level templates.

## Code Style

- **No comments explaining what the code does.** Good names carry that. Add a comment only when the *why* is non-obvious (hidden constraint, workaround for a specific bug, subtle invariant).
- **No error handling for impossible cases.** Trust ORM guarantees, PHP type system, and internal invariants. Only validate at system boundaries (POST data, external APIs).
- **No backwards-compatibility shims.** If something is unused, delete it completely. No `// removed` comments, no renamed `_vars`.
