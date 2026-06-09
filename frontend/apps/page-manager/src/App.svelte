<script lang="ts">
  import PagesManager from './lib/PagesManager.svelte';
  import type { Template, Role } from './lib/types';

  const config = (window as Window & {
    __PAGES_CONFIG__: {
      xhrPath: string;
      templates: Template[];
      roles: Role[];
      isDeveloperOrDesigner: boolean;
    }
  }).__PAGES_CONFIG__;
</script>

<PagesManager
  xhrPath={config.xhrPath}
  templates={config.templates}
  roles={config.roles}
  isDeveloperOrDesigner={config.isDeveloperOrDesigner}
/>
