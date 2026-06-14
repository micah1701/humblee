# Svelte App Structure

## Standard Directory Layout

Every app under `frontend/apps/[app-name]/src/` follows this layout:

```
src/
├── App.svelte              # Entry — reads window.__CONFIG__, passes props down
├── app.css                 # Global styles (imported in main.ts)
├── main.ts                 # Bootstraps Svelte, mounts to #app
├── vite-env.d.ts           # Vite type declarations
└── lib/
    ├── [Feature].svelte    # Primary component (e.g., MediaManager.svelte)
    ├── components/         # Sub-components (reusable UI pieces)
    ├── services/           # API client functions (fetch wrappers)
    │   └── [domain]Api.ts  # e.g., mediaApi.ts, contentApi.ts
    ├── types/              # TypeScript interfaces and type aliases
    │   └── [domain].ts     # e.g., media.ts, editor.ts
    └── utils/              # Pure helper functions
        └── [domain]Utils.ts
```

## main.ts

```ts
import './app.css'
import App from './App.svelte'

const app = new App({
  target: document.getElementById('app')!,
})

export default app
```

## App.svelte Pattern

```svelte
<script lang="ts">
  import MyFeature from './lib/MyFeature.svelte'

  const config = (window as any).__MY_APP_CONFIG__

  // Destructure typed values from config
  const xhrPath: string = config.XHR_PATH
  const hasAdminRole: boolean = config.hasAdminRole
  let csrf = config.csrf  // mutable — refreshed after each POST
</script>

<MyFeature
  {xhrPath}
  {hasAdminRole}
  bind:csrf
/>
```

## Service Layer (API calls)

```ts
// src/lib/services/myApi.ts

export async function saveRecord(
  xhrPath: string,
  csrf: { key: string; token: string },
  data: FormData
): Promise<{ status: string; csrf: { key: string; token: string } }> {
  data.append('hmac_key', csrf.key)
  data.append('hmac_token', csrf.token)

  const res = await fetch(xhrPath + 'my_group/save', {
    method: 'POST',
    body: data,
  })

  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return res.json()
}
```

Rules:
- Always append `hmac_key` and `hmac_token` from `csrf` to every POST body
- Use `FormData` (not JSON body) unless the endpoint explicitly expects JSON
- Return the parsed JSON — let the component handle state updates
- Update `csrf` from the response before the next call

## TypeScript Types

```ts
// src/lib/types/myDomain.ts

export interface MyRecord {
  id: number
  name: string
  created_at: string
}

export interface ApiResponse<T> {
  status: 'ok' | 'error'
  data?: T
  error?: string
  csrf?: { key: string; token: string }
}
```

## Svelte Component Conventions

- Props are typed with TypeScript
- Use `bind:csrf` to propagate HMAC refresh up to App.svelte
- Keep components small: if a `.svelte` file exceeds ~150 lines, extract a sub-component
- Side effects (fetch, DOM mutation) live in `onMount` or event handlers — not at top level
- Use Svelte stores only for state shared across non-parent-child components

## CSS

- Import Bulma from the PHP template (`public/node_modules/bulma/css/bulma.min.css`) — do not bundle it
- App-specific styles go in `app.css` (global) or `<style>` blocks in components (scoped)
- Use Bulma class names; extend with custom CSS variables for theming
