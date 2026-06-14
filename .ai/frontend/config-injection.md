# PHP → Svelte Config Injection

## The Pattern

PHP sets a global object on `window` before the Svelte module loads. Svelte reads from it in `App.svelte`. No hardcoded environment values exist in Svelte/TS source.

## PHP Side (view template)

```php
<!-- In humblee/views/admin/my-feature.php -->
<script>
window.__MY_APP_CONFIG__ = {
    hasAdminRole:    <?php echo json_encode((bool)$hasAdminRole) ?>,
    hasContentRole:  <?php echo json_encode((bool)$hasContentRole) ?>,
    XHR_PATH:        "<?php echo _app_path ?>core-request/",
    WEB_ROOT:        "<?php echo _app_path ?>",
    csrf:            <?php echo json_encode(\Humblee\Model\Crypto::get_hmac_pair()) ?>
};
</script>
<div id="app"></div>
```

**Rules:**
- Inject only scalar values or simple objects — no PHP objects, no circular references
- Use `json_encode()` for all values (handles escaping and type coercion)
- Cast booleans explicitly: `(bool)$phpVar`
- Include an initial HMAC pair (`csrf`) so the first request is ready immediately

## Svelte Side (App.svelte)

```svelte
<script lang="ts">
  import MyApp from './lib/MyApp.svelte'

  const config = (window as any).__MY_APP_CONFIG__

  const xhrPath: string = config.XHR_PATH
  const hasAdminRole: boolean = config.hasAdminRole
  let csrf = config.csrf
</script>

<MyApp {xhrPath} {hasAdminRole} bind:csrf />
```

Pass config values as typed props. Never read `window.__*` outside of `App.svelte`.

## HMAC Refresh Pattern

After any successful POST, the server returns a fresh HMAC pair. Update `csrf` so the next request works:

```ts
// In a service function
const res = await fetch(xhrPath + 'content/save', {
  method: 'POST',
  body: formData,
})
const data = await res.json()

if (data.csrf) {
  csrf = data.csrf  // bind:csrf in parent updates the store
}
```

## Naming Convention

| Config global | App |
|---|---|
| `window.__MEDIA_CONFIG__` | media-manager |
| `window.__PAGE_EDITOR_CONFIG__` | page-editor |
| `window.__TEMPLATES_CONFIG__` | templates |
| `window.__PAGE_MANAGER_CONFIG__` | page-manager |
| `window.__ADMIN_HOME_CONFIG__` | admin-home |

Pattern: `window.__[APP_NAME_UPPER_SNAKE]_CONFIG__`

## What to Include

Always include:
- `XHR_PATH` — base URL for AJAX calls
- `WEB_ROOT` — app path for asset URLs
- `csrf` — initial HMAC pair `{ key: string, token: string }`

Include as needed:
- Role booleans (`hasAdminRole`, `hasContentRole`, `hasMediaRole`, etc.)
- Feature flags (`tinyPngEnabled`, `twilioEnabled`)
- Bootstrap data (current user ID, page ID, etc.)
