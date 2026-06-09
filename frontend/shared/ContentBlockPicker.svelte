<script lang="ts">
  import { onMount, createEventDispatcher } from 'svelte';

  export let pageId: number;
  export let xhrPath: string;
  export let appPath: string;
  export let useP13n: boolean = false;
  /**
   * Optional navigation override.
   * When provided, called instead of window.location navigation — useful for
   * toolbar/iframe contexts that want to open the editor differently.
   */
  export let onNavigate: ((url: string) => void) | undefined = undefined;

  const dispatch = createEventDispatcher<{ close: void }>();

  interface SlotInfo {
    templateBlockId: number; // negative = legacy (-(typeId))
    slotKey: string;
    label: string;
    contentTypeId: number;
    contentTypeName: string;
  }

  interface P13nVersion {
    id: number;
    name: string;
  }

  interface ContentRecord {
    id: number;
    typeId: number;
    templateBlockId: number;
    p13nId: number;
    live: boolean;
    revisionDate: string;
    hasContent: boolean;
  }

  interface PageMapResponse {
    pageId: number;
    pageLabel: string;
    slots: SlotInfo[];
    p13nVersions: P13nVersion[];
    contentRecords: ContentRecord[];
    error?: string;
  }

  let loading = true;
  let fetchError = '';
  let pageLabel = '';
  let slots: SlotInfo[] = [];
  let p13nVersions: P13nVersion[] = [];
  let contentRecords: ContentRecord[] = [];

  $: contentLookup = new Map<string, ContentRecord>(
    contentRecords.map(r => [`${r.templateBlockId}_${r.p13nId}`, r])
  );

  $: displayP13n = useP13n ? p13nVersions : p13nVersions.filter(v => v.id === 0);

  // All table rows = slots × p13n versions
  $: tableRows = slots.flatMap(slot =>
    displayP13n.map(p13n => ({ slot, p13n }))
  );

  $: hasMultipleP13n = useP13n && p13nVersions.length > 1;

  onMount(async () => {
    try {
      const body = new URLSearchParams({ page_id: String(pageId) });
      const res = await fetch(`${xhrPath}content/page-map`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const data: PageMapResponse = await res.json();
      if (data.error) {
        fetchError = data.error;
      } else {
        pageLabel      = data.pageLabel;
        slots          = data.slots;
        p13nVersions   = data.p13nVersions;
        contentRecords = data.contentRecords;
      }
    } catch {
      fetchError = 'Failed to load content blocks.';
    } finally {
      loading = false;
    }
  });

  function editUrl(slot: SlotInfo, p13n: P13nVersion, record: ContentRecord | undefined): string {
    if (record) {
      return `${appPath}admin/edit/${record.id}`;
    }
    if (slot.templateBlockId > 0) {
      return `${appPath}admin/edit/?page_id=${pageId}&template_block_id=${slot.templateBlockId}&p13n_id=${p13n.id}`;
    }
    // Legacy: negative templateBlockId encodes -(typeId)
    const typeId = Math.abs(slot.templateBlockId);
    return `${appPath}admin/edit/?page_id=${pageId}&content_type=${typeId}&p13n_id=${p13n.id}`;
  }

  function handleEdit(url: string) {
    if (onNavigate) {
      onNavigate(url);
    } else {
      window.location.href = url;
    }
  }

  function close() {
    dispatch('close');
  }

  function onBackdropClick(e: MouseEvent) {
    if (e.target === e.currentTarget) close();
  }

  function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') close();
  }

  function statusLabel(record: ContentRecord | undefined): string {
    if (!record || !record.hasContent) return 'Not Created';
    if (record.live) return 'Live';
    return 'Draft';
  }

  function statusTagClass(record: ContentRecord | undefined): string {
    if (!record || !record.hasContent) return 'tag is-light';
    if (record.live) return 'tag is-success';
    return 'tag is-warning';
  }
</script>

<svelte:window on:keydown={onKeydown} />

<!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
<div class="modal is-active" on:click={onBackdropClick}>
  <div class="modal-background"></div>
  <div class="modal-card content-picker-card">
    <header class="modal-card-head">
      <p class="modal-card-title">
        {#if loading}
          Loading content blocks…
        {:else if pageLabel}
          Content Blocks — {pageLabel}
        {:else}
          Content Blocks
        {/if}
      </p>
      <button class="delete" aria-label="close" on:click={close}></button>
    </header>

    <section class="modal-card-body">
      {#if loading}
        <div class="has-text-centered py-5">
          <span class="icon is-large has-text-grey">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
          </span>
        </div>
      {:else if fetchError}
        <div class="notification is-danger is-light">{fetchError}</div>
      {:else if tableRows.length === 0}
        <div class="notification is-info is-light">
          No content blocks are configured for this page's template.
        </div>
      {:else}
        <table class="table is-fullwidth is-striped is-hoverable">
          <thead>
            <tr>
              <th>Block</th>
              <th>Type</th>
              {#if hasMultipleP13n}
                <th>Personalization</th>
              {/if}
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {#each tableRows as { slot, p13n } (slot.templateBlockId + '_' + p13n.id)}
              {@const record = contentLookup.get(`${slot.templateBlockId}_${p13n.id}`)}
              <tr>
                <td class="is-vcentered">{slot.label || slot.contentTypeName}</td>
                <td class="is-vcentered is-size-7 has-text-grey">{slot.contentTypeName}</td>
                {#if hasMultipleP13n}
                  <td class="is-vcentered is-size-7">{p13n.name}</td>
                {/if}
                <td class="is-vcentered">
                  <span class={statusTagClass(record)}>{statusLabel(record)}</span>
                </td>
                <td class="is-vcentered">
                  <button
                    class="button is-small is-info"
                    on:click={() => handleEdit(editUrl(slot, p13n, record))}
                  >
                    <span class="icon is-small"><i class="fas fa-edit"></i></span>
                    <span>Edit</span>
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      {/if}
    </section>
  </div>
</div>

<style>
  .content-picker-card {
    width: 90%;
    max-width: 820px;
    max-height: 80vh;
  }

  .modal-card-body {
    overflow-y: auto;
  }
</style>
