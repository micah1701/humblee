<script lang="ts">
  import BlocksManager from './lib/BlocksManager.svelte';

  const config = (window as Window & {
    __BLOCKS_CONFIG__: { XHR_PATH: string; theme: string }
  }).__BLOCKS_CONFIG__;

  const xhrPath: string = config.XHR_PATH;
</script>

<BlocksManager {xhrPath} />
