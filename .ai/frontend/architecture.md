# Frontend Architecture

## Workspace Structure

Two separate npm contexts — they must remain separate (Bulma must not be hoisted):

```
/package.json                  ← Root workspace root: delegates build commands
/public/package.json           ← Bulma + CSS tools (web-accessible)
/frontend/package.json         ← Vite + Svelte + TypeScript (NOT web-accessible)
  └── apps/                    ← workspace: ["apps/*"]
      ├── admin-home/
      ├── blocks/
      ├── media-manager/
      ├── page-editor/
      ├── page-manager/
      ├── personalization/
      ├── session-monitor/
      ├── templates/
      ├── toolbar/
      ├── tools/
      └── user-manager/
```

## Build Commands (run from project root `/`)

```bash
npm run setup                  # Install all deps in public/ and frontend/
npm run build                  # Build ALL frontend apps
npm run build:media-manager    # Build one specific app
npm run dev:media-manager      # Vite dev server for media-manager
npm run dev:page-editor        # Vite dev server for page-editor
```

Each app name matches the directory name under `frontend/apps/`.

## Build Output

Each app builds to a **fixed-name** pair of files:

```
public/humblee/js/admin/[app-name]/
├── index.js     ← ES module (Svelte compiled + bundled)
└── index.css    ← Styles
```

**These files are committed to git.** Always run a build after changing Svelte source.

No content-hash suffixes — filenames are always `index.js` and `index.css`.

## Vite Config Requirements

Every app's `vite.config.ts` must:

```ts
import { resolve } from 'path'

export default {
  build: {
    outDir: resolve(__dirname, '../../../public/humblee/js/admin/[app-name]'),
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'index.js',
        chunkFileNames: 'index.js',
        assetFileNames: 'index.[ext]',   // → index.css
      }
    }
  },
  plugins: [
    svelte(),
    removeHtml(),   // Prevents Vite writing a stray index.html to public/
  ]
}
```

The `removeHtml()` plugin is defined inline in each `vite.config.ts` — copy from `media-manager/vite.config.ts`.

## How Assets Are Loaded

The PHP controller sets `$this->extra_head_code` in the admin action method:

```php
$this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/my-app/index.css">';
$this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/my-app/index.js"></script>';
```

The admin layout template (`humblee/views/admin/templates/template.php`) outputs `$extra_head_code` inside `<head>`.
