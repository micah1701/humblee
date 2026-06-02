<script lang="ts">
  import TemplatesManager from './lib/TemplatesManager.svelte';

  const config = (window as Window & {
    __TEMPLATES_CONFIG__: { XHR_PATH: string; theme: string }
  }).__TEMPLATES_CONFIG__;

  const xhrPath: string = config.XHR_PATH;
</script>

<TemplatesManager {xhrPath} />
