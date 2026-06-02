<script lang="ts">
  import CrudShell from '@crud-shared/CrudShell.svelte';
  import { createCrudApi } from '@crud-shared/crudApi';
  import { quickNotice, confirmation } from '@crud-shared/crudUtils';
  import type { Template, Block } from './types';

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
  let selectedBlocks: Set<number> = new Set();

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
    selectedBlocks = new Set();
    errors = [];
  }

  function populateForm(t: Template) {
    name             = t.name;
    description      = t.description;
    page_type        = t.page_type || 'view';
    controller       = t.controller;
    controller_action = t.controller_action;
    default_view     = t.default_view;
    dynamic_uri      = t.dynamic_uri === 1;
    available        = t.available === 1;
    selectedBlocks   = new Set(
      t.blocks
        ? t.blocks.split(',').map(Number).filter(n => !isNaN(n) && n > 0)
        : []
    );
    errors = [];
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

  function toggleBlock(blockId: number) {
    const next = new Set(selectedBlocks);
    if (next.has(blockId)) {
      next.delete(blockId);
    } else {
      next.add(blockId);
    }
    selectedBlocks = next;
  }

  async function handleSave() {
    errors = [];
    saving = true;
    try {
      const payload: Record<string, unknown> = {
        name,
        description,
        page_type,
        available:   available ? '1' : '0',
        blocks:      Array.from(selectedBlocks),
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

  <!-- Content blocks -->
  <div class="field">
    <!-- svelte-ignore a11y-label-has-associated-control -->
    <label class="label">Included Content Blocks</label>
    {#if allBlocks.length === 0}
      <p class="has-text-grey is-size-7">No content blocks defined yet.</p>
    {:else}
      <div class="blocks-grid">
        {#each allBlocks as block (block.id)}
          <label
            class="block-checkbox-label"
            class:is-checked={selectedBlocks.has(block.id)}
            title={block.description}
          >
            <input
              type="checkbox"
              checked={selectedBlocks.has(block.id)}
              on:change={() => toggleBlock(block.id)}
            />
            <span>{block.name}</span>
          </label>
        {/each}
      </div>
    {/if}
    <p class="help mt-2">
      Unchecking a block removes it from the editor for this template, but existing content
      may still appear on the site if the view renders it.
    </p>
  </div>
</CrudShell>

<style>
  .blocks-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    padding: 0.5rem 0;
  }

  .block-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border: 1px solid #dbdbdb;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: border-color 0.1s, background 0.1s;
    background: #fff;
  }

  .block-checkbox-label:hover {
    border-color: #3273dc;
  }

  .block-checkbox-label.is-checked {
    border-color: #3273dc;
    background: #ebf3ff;
    color: #1e62cc;
  }

  .block-checkbox-label input[type="checkbox"] {
    accent-color: #3273dc;
  }
</style>
