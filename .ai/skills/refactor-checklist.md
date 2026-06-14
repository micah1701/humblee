# Skill: Safe Refactor Checklist

## Before You Start

- [ ] Identify the full call graph — who calls the code you're changing? (grep for class name, method name)
- [ ] Check if the method is called from view templates (`humblee/views/`, `application/views/`) — views are not autoloaded and won't surface in IDE references
- [ ] Check if any method is referenced by name as a string (e.g., in `match()` dispatchers or dynamic calls)
- [ ] Run `./vendor/bin/phpunit` from `humblee/` to establish a baseline — all tests must pass before you start

## Do Not Touch (unless the explicit goal is to change these)

- `humblee/views/` — CMS admin/user/email templates
- `application/views/` — app-level templates
- Database table structure or column names
- `humblee/configuration/env_*.php` — environment config

## During Refactor

- Keep changes to one layer at a time (model, controller, or view) unless the task spans layers
- Preserve all existing public method signatures when refactoring internals — changing a method's parameter types or name is a breaking change
- If renaming a method: grep for its string references in view templates and match dispatchers
- If changing ORM queries: verify the column names match the actual DB schema (check the installer SQL in `public/humblee/install/`)
- Do not add parameters with default values to existing public methods without checking all callers

## Svelte / Frontend Refactors

- After any change to Svelte source, run `npm run build:[app-name]` and verify the built output in `public/humblee/js/admin/[app-name]/`
- Never change the `window.__[APP]_CONFIG__` shape without updating both the PHP view injection and the `App.svelte` reader
- Renaming a Svelte component file: update the import in its parent; Vite won't error silently on a missing import
- Changing the API shape (request/response fields): update the service function, the component that calls it, and the server-side handler together

## After Refactor

- [ ] `./vendor/bin/phpunit` passes (from `humblee/`)
- [ ] If frontend changed: `npm run build:[app-name]` succeeds; built files are committed
- [ ] Manually verify the affected admin page in a browser (tests don't cover UI flows)
- [ ] If a public method signature changed: check for callers in both PHP (autoloaded) and view templates (manually searched)
- [ ] No commented-out code left behind
- [ ] No backwards-compatibility shims (removed `_vars`, re-exported types, `// removed` comments) — delete unused code outright

## Grep Commands for Finding Callers

```bash
# Find PHP callers of a method
grep -rn "methodName" humblee/src/ application/

# Find view template references (not autoloaded — must search manually)
grep -rn "methodName" humblee/views/ application/views/

# Find string-based dispatch references
grep -rn "'methodName'" humblee/src/Controller/

# Find Svelte/TS references to a renamed API endpoint or config key
grep -rn "my_endpoint" frontend/apps/
```
