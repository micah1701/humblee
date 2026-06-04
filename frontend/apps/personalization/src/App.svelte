<script lang="ts">
  import PersonalizationManager from './lib/PersonalizationManager.svelte';
  import type { Role } from './lib/types';

  const config = (window as Window & { __P13N_CONFIG__: {
    XHR_PATH: string;
    theme: string;
    i18nSegments: string[];
    roles: Role[];
  } }).__P13N_CONFIG__;
</script>

<PersonalizationManager
  xhrPath={config.XHR_PATH}
  i18nSegments={config.i18nSegments}
  roles={config.roles}
/>
