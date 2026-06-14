# Skill: Add a New Svelte Admin App

## Step 1 — Scaffold

```bash
cd frontend/apps
npm create vite@latest my-tool -- --template svelte-ts
```

This creates `frontend/apps/my-tool/` with the standard Vite + Svelte + TS layout.

## Step 2 — Configure vite.config.ts

Replace the generated `vite.config.ts` entirely:

```ts
import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import { resolve } from 'path'

// Prevents Vite from writing a stray index.html into the public directory
function removeHtml() {
  return {
    name: 'remove-html',
    generateBundle(_options: any, bundle: any) {
      Object.keys(bundle).forEach(key => {
        if (key.endsWith('.html')) delete bundle[key]
      })
    }
  }
}

export default defineConfig({
  plugins: [svelte(), removeHtml()],
  build: {
    outDir: resolve(__dirname, '../../../public/humblee/js/admin/my-tool'),
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, 'src/main.ts'),
      output: {
        entryFileNames: 'index.js',
        chunkFileNames: 'index.js',
        assetFileNames: 'index.[ext]',
      }
    }
  }
})
```

Replace `my-tool` with the actual app name (must match the directory name).

## Step 3 — Build Script in Root package.json

Add to `/package.json` scripts:

```json
"build:my-tool":  "npm run build --prefix frontend/apps/my-tool",
"dev:my-tool":    "npm run dev --prefix frontend/apps/my-tool"
```

Also verify `frontend/package.json` workspaces includes `apps/*` — it should already.

## Step 4 — App.svelte Entry

```svelte
<!-- frontend/apps/my-tool/src/App.svelte -->
<script lang="ts">
  import MyTool from './lib/MyTool.svelte'

  const config = (window as any).__MY_TOOL_CONFIG__

  const xhrPath: string = config.XHR_PATH
  const hasAdminRole: boolean = config.hasAdminRole
  let csrf = config.csrf
</script>

<MyTool {xhrPath} {hasAdminRole} bind:csrf />
```

Create `frontend/apps/my-tool/src/lib/MyTool.svelte` as the primary component.

## Step 5 — PHP View Template

Create `humblee/views/admin/my-tool.php`:

```php
<script>
window.__MY_TOOL_CONFIG__ = {
    hasAdminRole: <?php echo json_encode((bool)Core::auth(['admin', 'developer'])) ?>,
    XHR_PATH:     "<?php echo _app_path ?>core-request/",
    WEB_ROOT:     "<?php echo _app_path ?>",
    csrf:         <?php echo json_encode(\Humblee\Model\Crypto::get_hmac_pair()) ?>
};
</script>
<div id="app"></div>
```

Add any additional PHP-sourced values the app needs to the config object.

## Step 6 — Controller Action

In `humblee/src/Controller/Admin.php`, add:

```php
public function myTool(): void
{
    // Auth already checked in __construct()
    $this->page_title = 'My Tool';
    $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/my-tool/index.css">';
    $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/my-tool/index.js"></script>';
}
```

The admin template handles rendering — the method just sets view properties.

URL: `/admin/myTool`

## Step 7 — Build and Verify

```bash
npm run build:my-tool
```

Verify these files exist and are non-empty:
- `public/humblee/js/admin/my-tool/index.js`
- `public/humblee/js/admin/my-tool/index.css`

Commit both files to git.

## Checklist

- [ ] `vite.config.ts` has `removeHtml()` plugin
- [ ] Build output uses fixed names (`index.js`, `index.css`) — no hashes
- [ ] `outDir` points to correct `public/humblee/js/admin/[app-name]/`
- [ ] `App.svelte` reads config from `window.__[APP]_CONFIG__` only
- [ ] PHP view injects config before `<div id="app">`
- [ ] Controller uses `type="module"` for the script tag
- [ ] Built files committed to git
- [ ] Admin nav updated (if the tool needs a menu entry)
