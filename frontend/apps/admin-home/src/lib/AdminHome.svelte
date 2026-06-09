<script lang="ts">
  import type { AdminHomeConfig } from './types/adminHome';
  import ContentBlockPicker from '@crud-shared/ContentBlockPicker.svelte';
  import PageTree from './PageTree.svelte';
  import RecentlyEdited from './RecentlyEdited.svelte';

  export let config: AdminHomeConfig;

  let selectedPageId: number | null = null;

  function handlePageSelect(event: CustomEvent<number>) {
    selectedPageId = event.detail;
  }

  function closePicker() {
    selectedPageId = null;
  }
</script>

<div class="columns">
  <div id="editnav" class="column is-two-fifths">
    <p class="is-size-5">Edit Content by Page</p>
    <aside class="menu">
      <PageTree xhrPath={config.xhrPath} on:select={handlePageSelect} />
    </aside>
  </div>

  <div class="column">
    <p class="is-size-5">Recently Edited Content Elements:</p>
    <RecentlyEdited
      items={config.recentContents}
      appPath={config.appPath}
      useP13n={config.useP13n}
    />
  </div>
</div>

{#if selectedPageId !== null}
  <ContentBlockPicker
    pageId={selectedPageId}
    xhrPath={config.xhrPath}
    appPath={config.appPath}
    useP13n={config.useP13n}
    on:close={closePicker}
  />
{/if}
