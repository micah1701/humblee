<script lang="ts">
  import { onMount } from 'svelte';

  const config = (window as Window & {
    __TOOLBAR_CONFIG__: { appPath: string; name: string }
  }).__TOOLBAR_CONFIG__;

  const appPath = config.appPath;

  interface HoveredBlock {
    contentId: string;
    blockName: string;
    rect: DOMRect;
  }

  let hoveredBlock: HoveredBlock | null = null;
  let activeContentId: string | null = null;
  let opacity = 0;

  $: buttonTop  = hoveredBlock ? hoveredBlock.rect.top + 10 : 0;
  $: buttonRight = hoveredBlock ? window.innerWidth - hoveredBlock.rect.right + 10 : 0;

  function openEditor() {
    if (!hoveredBlock) return;
    activeContentId = hoveredBlock.contentId;
  }

  function closeEditor() {
    activeContentId = null;
  }

  onMount(() => {
    function onMouseover(e: MouseEvent) {
      const block = (e.target as Element).closest<HTMLElement>('.cms_block');
      if (!block) return;
      if (block.contains(e.relatedTarget as Node | null)) return;

      const contentId = block.dataset.contentId ?? '';
      const blockName = block.dataset.blockName ?? '';
      hoveredBlock = { contentId, blockName, rect: block.getBoundingClientRect() };
      opacity = 0;
      // Allow Svelte to render the button at opacity 0 before transitioning to 1
      requestAnimationFrame(() => { opacity = 1; });
    }

    function onMouseout(e: MouseEvent) {
      const block = (e.target as Element).closest<HTMLElement>('.cms_block');
      if (!block) return;
      if (block.contains(e.relatedTarget as Node | null)) return;
      // Don't clear when the mouse moves onto our fixed-position button overlay
      if ((e.relatedTarget as Element | null)?.closest('#toolbar-app')) return;
      hoveredBlock = null;
      opacity = 0;
    }

    function onKeydown(e: KeyboardEvent) {
      if (e.key === 'Escape') closeEditor();
    }

    document.addEventListener('mouseover', onMouseover);
    document.addEventListener('mouseout', onMouseout);
    document.addEventListener('keydown', onKeydown);

    return () => {
      document.removeEventListener('mouseover', onMouseover);
      document.removeEventListener('mouseout', onMouseout);
      document.removeEventListener('keydown', onKeydown);
    };
  });
</script>

{#if hoveredBlock}
  <button
    class="launch-editor"
    style="top: {buttonTop}px; right: {buttonRight}px; opacity: {opacity};"
    on:click={openEditor}
  >
    Edit "{hoveredBlock.blockName}"
  </button>
{/if}

{#if activeContentId}
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div id="humbleeEditor" class="modal is-active">
    <div class="modal-background" on:click={closeEditor}></div>
    <div class="modal-card">
      <header class="modal-card-head">
        <p class="modal-card-title">Content Editor</p>
        <button class="delete" aria-label="close" on:click={closeEditor}></button>
      </header>
      <section class="modal-card-body">
        <iframe
          title="Content Editor"
          src="{appPath}admin/edit/{activeContentId}?iframe=true"
        ></iframe>
      </section>
    </div>
  </div>
{/if}

<style>
  :global(.cms_block:hover) {
    outline: 1px dotted var(--bulma-link);
  }

  .launch-editor {
    position: fixed;
    background-color: var(--bulma-scheme-main);
    border: 1px solid var(--bulma-primary);
    border-radius: 5px;
    color: var(--bulma-primary);
    padding: 8px 10px;
    font-size: 0.875rem;
    font-family: sans-serif;
    font-weight: normal;
    line-height: 1.2;
    transition: opacity 0.2s, background-color 0.15s, color 0.15s;
    cursor: pointer;
    z-index: 9999;
  }

  .launch-editor:hover {
    background-color: var(--bulma-primary);
    color: var(--bulma-scheme-main);
  }

  :global(#humbleeEditor .modal-card) {
    width: 96% !important;
    height: 98vh;
  }

  :global(#humbleeEditor .modal-card .modal-card-body) {
    padding: 0;
    overflow: hidden;
  }

  :global(#humbleeEditor iframe) {
    width: 100%;
    height: calc(100vh - 105px);
    border: none;
  }
</style>
