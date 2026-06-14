# Humblee — Claude Code

Full project documentation lives in [.ai/README.md](.ai/README.md). Read that index first, then pull only the layer relevant to your task.

## Always-on rules (abbreviated — see [.ai/global/rules.md](.ai/global/rules.md))

- No raw SQL — Idiorm ORM only (`\ORM::for_table()` with `_table_*` constants)
- Every state-changing POST requires HMAC validation (`$this->require_hmac()`)
- Do not modify `humblee/views/` or `application/views/` as a side effect
- Do not alter DB schema — no migrations live in this repo
- Frontend built output (`index.js` + `index.css`) is committed to git — rebuild after any Svelte change

## Claude Code operational notes

These are specific to running commands in this repo — not general project docs.

**PHPUnit** — always run from `humblee/`, not the project root:
```bash
cd humblee && ./vendor/bin/phpunit
```
The bootstrap path is relative to `humblee/`; it won't resolve from `/`.

**Frontend builds** — run from the project root:
```bash
npm run build:media-manager   # one app
npm run build                 # all apps
```

**No `eval()`** — it has been eliminated project-wide. Validators are callables (`fn($val): bool`); menu formatting uses callables; page ordering uses `json_decode`. If you're tempted to use `eval()`, don't.
