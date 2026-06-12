<script lang="ts">
  import CrudShell from '@crud-shared/CrudShell.svelte';
  import { createCrudApi } from '@crud-shared/crudApi';
  import { quickNotice, confirmation } from '@crud-shared/crudUtils';
  import type { Template, Block, TemplateBlock } from './types';

  export let xhrPath: string;

  const api = createCrudApi(xhrPath);

  // ── State ────────────────────────────────────────────────────────────────
  let templates: Template[] = [];
  let allBlocks: Block[] = [];
  let selectedId: number | null = null;
  let loading = true;
  let saving = false;
  let errors: string[] = [];

  // Form fields
  let name = '';
  let description = '';
  let page_type = 'view';
  let controller = '';
  let controller_action = '';
  let default_view = '';
  let dynamic_uri = false;
  let available = true;
  let slotList: TemplateBlock[] = [];
  let blockToAdd = 0;

  // ── Init ──────────────────────────────────────────────────────────────
  async function loadAll() {
    loading = true;
    try {
      const [tmpl, blks] = await Promise.all([
        api.list<Template>('templates/list'),
        api.list<Block>('blocks/list'),
      ]);
      templates = tmpl;
      allBlocks = blks;
    } catch {
      quickNotice('Failed to load data', 'is-danger');
    } finally {
      loading = false;
    }
  }

  loadAll();

  // ── Helpers ───────────────────────────────────────────────────────────
  function clearForm() {
    name = '';
    description = '';
    page_type = 'view';
    controller = '';
    controller_action = '';
    default_view = '';
    dynamic_uri = false;
    available = true;
    slotList = [];
    blockToAdd = 0;
    errors = [];
  }

  function populateForm(t: Template) {
    name              = t.name;
    description       = t.description;
    page_type         = t.page_type || 'view';
    controller        = t.controller;
    controller_action = t.controller_action;
    default_view      = t.default_view;
    dynamic_uri       = t.dynamic_uri === 1;
    available         = t.available === 1;
    errors            = [];

    if (t.templateBlocks && t.templateBlocks.length > 0) {
      // New-style: use slot list from server
      slotList = t.templateBlocks.map(tb => ({ ...tb }));
    } else if (t.blocks) {
      // Legacy fallback: synthesize display list from comma-string (null IDs = will be created on save)
      slotList = t.blocks
        .split(',')
        .map(Number)
        .filter(n => !isNaN(n) && n > 0)
        .map((ctId, i) => ({
          id:            null,
          contentTypeId: ctId,
          label:         '',
          slotKey:       '',
          sortOrder:     i,
        }));
    } else {
      slotList = [];
    }

    blockToAdd = 0;
  }

  function handleSelect(id: number) {
    selectedId = id;
    const t = templates.find(t => t.id === id);
    if (t) populateForm(t);
  }

  function handleNew() {
    selectedId = null;
    clearForm();
  }

  function addSlot(blockId: number) {
    slotList = [
      ...slotList,
      { id: null, contentTypeId: blockId, label: '', slotKey: '', sortOrder: slotList.length },
    ];
  }

  function removeSlot(index: number) {
    slotList = slotList.filter((_, i) => i !== index);
  }

  function moveSlot(index: number, direction: -1 | 1) {
    const next = [...slotList];
    const swap = index + direction;
    if (swap < 0 || swap >= next.length) return;
    [next[index], next[swap]] = [next[swap], next[index]];
    slotList = next.map((s, i) => ({ ...s, sortOrder: i }));
  }

  async function handleSave() {
    errors = [];
    saving = true;
    try {
      const payload: Record<string, unknown> = {
        name,
        description,
        page_type,
        available:      available ? '1' : '0',
        templateBlocks: JSON.stringify(
          slotList.map((s, i) => ({
            id:            s.id ?? null,
            contentTypeId: s.contentTypeId,
            label:         s.label,
            slotKey:       s.slotKey,
            sortOrder:     i,
          }))
        ),
      };

      if (page_type === 'controller') {
        payload.controller        = controller;
        payload.controller_action = controller_action;
        payload.dynamic_uri       = dynamic_uri ? '1' : '0';
      } else if (page_type === 'view') {
        payload.default_view = default_view;
      }

      if (selectedId !== null) payload.id = selectedId;

      const res = await api.save('templates/save', payload);
      if (!res.success) {
        errors = res.errors ?? ['Save failed'];
      } else {
        const savedId = res.id!;
        await loadAll();
        selectedId = savedId;
        const updated = templates.find(t => t.id === savedId);
        if (updated) populateForm(updated);
        quickNotice('Template saved successfully');
      }
    } catch {
      errors = ['An unexpected error occurred'];
    } finally {
      saving = false;
    }
  }

  async function handleDelete() {
    if (selectedId === null) return;
    const tmpl = templates.find(t => t.id === selectedId);
    confirmation(
      `Delete template <strong>${tmpl?.name ?? ''}</strong>? Pages using this template will lose their template assignment.`,
      async () => {
        saving = true;
        try {
          const res = await api.remove('templates/delete', selectedId!);
          if (!res.success) {
            quickNotice('Delete failed', 'is-danger');
          } else {
            quickNotice('Template deleted', 'is-warning');
            selectedId = null;
            clearForm();
            await loadAll();
          }
        } catch {
          quickNotice('An unexpected error occurred', 'is-danger');
        } finally {
          saving = false;
        }
      },
      () => {}
    );
  }
</script>

<CrudShell
  title="Page Templates"
  subtitle="Define how pages are rendered and which content blocks they support"
  items={templates}
  {selectedId}
  {loading}
  {saving}
  {errors}
  saveLabel="Save Template"
  newLabel="New Template"
  on:select={e => handleSelect(e.detail)}
  on:new={handleNew}
  on:save={handleSave}
  on:delete={handleDelete}
>
  <div class="field">
    <label class="label" for="tmpl-name">Name <span class="has-text-danger">*</span></label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="tmpl-name"
        placeholder="e.g. Blog Post"
        bind:value={name}
      />
    </div>
  </div>

  <div class="field">
    <label class="label" for="tmpl-description">Description</label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="tmpl-description"
        placeholder="Brief description of this template"
        bind:value={description}
      />
    </div>
  </div>

  <!-- Method -->
  <div class="field">
    <!-- svelte-ignore a11y-label-has-associated-control -->
    <label class="label">Rendering Method</label>
    <div class="control">
      <label class="radio">
        <input type="radio" name="page_type" value="view" bind:group={page_type} />
        Passed to default controller (view file)
      </label>
      <br />
      <label class="radio mt-1">
        <input type="radio" name="page_type" value="controller" bind:group={page_type} />
        Hard-coded in a custom controller
      </label>
    </div>
  </div>

  <!-- Controller fields -->
  {#if page_type === 'controller'}
    <div class="box has-background-light p-4 mb-4">
      <div class="columns">
        <div class="column">
          <div class="field mb-0">
            <label class="label is-small" for="tmpl-controller">Controller</label>
            <div class="control">
              <input
                class="input is-small"
                type="text"
                id="tmpl-controller"
                placeholder="custom"
                bind:value={controller}
              />
            </div>
          </div>
        </div>
        <div class="column">
          <div class="field mb-0">
            <label class="label is-small" for="tmpl-action">Action</label>
            <div class="control">
              <input
                class="input is-small"
                type="text"
                id="tmpl-action"
                placeholder="index"
                bind:value={controller_action}
              />
            </div>
          </div>
        </div>
      </div>
      <div class="field mt-3 mb-0">
        <label class="checkbox">
          <input type="checkbox" bind:checked={dynamic_uri} />
          Dynamic URI — URL may include child page segments
        </label>
        <p class="help">Note: will not return 404 for invalid sub-paths</p>
      </div>
    </div>
  {/if}

  <!-- View field -->
  {#if page_type === 'view'}
    <div class="field">
      <label class="label" for="tmpl-view">Custom View Path</label>
      <div class="control">
        <input
          class="input"
          type="text"
          id="tmpl-view"
          placeholder="tier_pages/my-view"
          bind:value={default_view}
        />
      </div>
      <p class="help">Relative path from your views directory (no leading slash)</p>
    </div>
  {/if}

  <!-- Available -->
  <div class="field">
    <!-- svelte-ignore a11y-label-has-associated-control -->
    <label class="label">Availability</label>
    <label class="checkbox">
      <input type="checkbox" bind:checked={available} />
      This template is available for new pages
    </label>
    <p class="help">
      Unchecked = only developers can assign this template.
      Existing pages keep their assignment regardless.
    </p>
  </div>

  <!-- Content block slots -->
  <div class="field">
    <!-- svelte-ignore a11y-label-has-associated-control -->
    <label class="label">Content Block Slots</label>

    {#if slotList.length === 0}
      <p class="has-text-grey is-size-7 mb-3">No slots defined. Add blocks below.</p>
    {:else}
      <div class="slot-list mb-3">
        {#each slotList as slot, index (slot.id !== null ? slot.id : `new_${index}`)}
          <div class="slot-row box p-3 mb-2">
            <div class="columns is-vcentered is-mobile is-multiline">

              <!-- Sort controls -->
              <div class="column is-narrow">
                <div class="sort-buttons">
                  <button
                    class="button is-small"
                    type="button"
                    on:click={() => moveSlot(index, -1)}
                    disabled={index === 0}
                    title="Move up"
                  >↑</button>
                  <button
                    class="button is-small"
                    type="button"
                    on:click={() => moveSlot(index, 1)}
                    disabled={index === slotList.length - 1}
                    title="Move down"
                  >↓</button>
                </div>
              </div>

              <!-- Block type name -->
              <div class="column">
                <p class="is-size-7 has-text-grey mb-1">Block type</p>
                <strong>{allBlocks.find(b => b.id === slot.contentTypeId)?.name ?? 'Unknown'}</strong>
              </div>

              <!-- Label input -->
              <div class="column">
                <div class="field mb-0">
                  <label class="label is-small" for="slot-label-{index}">Label</label>
                  <div class="control">
                    <input
                      class="input is-small"
                      type="text"
                      id="slot-label-{index}"
                      placeholder="e.g. Main Content"
                      bind:value={slot.label}
                    />
                  </div>
                </div>
              </div>

              <!-- Slot key (read-only) -->
              <div class="column">
                <p class="is-size-7 has-text-grey mb-1">Slot key</p>
                {#if slot.slotKey}
                  <code class="is-size-7">{slot.slotKey}</code>
                {:else}
                  <span class="has-text-grey is-size-7 is-italic">auto-generated on save</span>
                {/if}
              </div>

              <!-- Remove button -->
              <div class="column is-narrow">
                <button
                  class="button is-danger is-small is-outlined"
                  type="button"
                  on:click={() => removeSlot(index)}
                  title="Remove slot"
                >✕</button>
              </div>

            </div>
          </div>
        {/each}
      </div>
    {/if}

    <!-- Add block row -->
    <div class="level">
      <div class="level-left">
        <div class="level-item">
          <div class="select is-small">
            <select bind:value={blockToAdd}>
              <option value={0}>— select a block to add —</option>
              {#each allBlocks as block (block.id)}
                <option value={block.id}>{block.name}</option>
              {/each}
            </select>
          </div>
        </div>
        <div class="level-item">
          <button
            class="button is-small is-info is-outlined"
            type="button"
            disabled={blockToAdd === 0}
            on:click={() => { addSlot(blockToAdd); blockToAdd = 0; }}
          >
            + Add Block
          </button>
        </div>
      </div>
    </div>

    <p class="help mt-1">
      The same block type can appear multiple times. Labels appear in the content editor.
      Slot keys are used in PHP view files: <code>Draw::content($content, 'slot_key')</code>
    </p>
  </div>
</CrudShell>

<style>
  .slot-list {
    border: 1px solid var(--bulma-border-weak);
    border-radius: 4px;
    padding: 0.5rem;
    background: var(--bulma-scheme-main-ter);
  }

  .slot-row {
    background: var(--bulma-scheme-main);
  }

  .sort-buttons {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
</style>
