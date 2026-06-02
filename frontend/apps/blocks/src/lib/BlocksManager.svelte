<script lang="ts">
  import CrudShell from '@crud-shared/CrudShell.svelte';
  import { createCrudApi } from '@crud-shared/crudApi';
  import { quickNotice, confirmation } from '@crud-shared/crudUtils';
  import type { Block } from './types';

  export let xhrPath: string;

  const api = createCrudApi(xhrPath);

  // ── State ────────────────────────────────────────────────────────────────
  let blocks: Block[] = [];
  let selectedId: number | null = null;
  let loading = true;
  let saving = false;
  let errors: string[] = [];

  // Form fields
  let name = '';
  let objectkey = '';
  let description = '';
  let output_type = '';
  let input_type = '';
  let input_parameters = '';

  // Track original params for reset functionality
  let originalParams = '';

  // ── Parameter templates keyed by input_type ───────────────────────────
  const paramTemplates: Record<string, string> = {
    wysiwyg:     '<div class="content" id="edit_content">{content}</div>\n<input type="hidden" id="content" name="content">',
    markdown:    '<textarea name="content" class="textarea" id="edit_content">{content}</textarea>',
    textfield:   '<input type="text" class="input" name="content" id="edit_content" value="{content}">',
    textarea:    '<textarea name="content" class="textarea" id="edit_content">{content}</textarea>',
    multifield:  '[\n  {"username": {"label": "User Name", "input": "<input type=\\"text\\" name=\\"username\\" value=\\"{content}\\" />"}},\n  {"age": {"label": "Age", "input": "<input type=\\"text\\" name=\\"age\\" value=\\"{content}\\" />"}},\n  {"confirmed": {"label": "Confirm?", "input": "<input type=\\"checkbox\\" name=\\"confirmed\\" value=\\"1\\" selected-data=\\"{content}\\" />"}}\n]',
    customform:  'admin/contentWidgets/widget/edit.php',
  };

  // ── Init ──────────────────────────────────────────────────────────────
  async function loadBlocks() {
    loading = true;
    try {
      blocks = await api.list<Block>('blocks/list');
    } catch {
      quickNotice('Failed to load content blocks', 'is-danger');
    } finally {
      loading = false;
    }
  }

  loadBlocks();

  // ── Helpers ───────────────────────────────────────────────────────────
  function clearForm() {
    name = '';
    objectkey = '';
    description = '';
    output_type = '';
    input_type = '';
    input_parameters = '';
    originalParams = '';
    errors = [];
  }

  function populateForm(block: Block) {
    name             = block.name;
    objectkey        = block.objectkey;
    description      = block.description;
    output_type      = block.output_type;
    input_type       = block.input_type;
    input_parameters = block.input_parameters;
    originalParams   = block.input_parameters;
    errors = [];
  }

  function handleSelect(id: number) {
    selectedId = id;
    const block = blocks.find(b => b.id === id);
    if (block) populateForm(block);
  }

  function handleNew() {
    selectedId = null;
    clearForm();
  }

  function handleInputTypeChange() {
    const template = paramTemplates[input_type];
    if (!template) return;

    const current = input_parameters.trim();
    if (current === '' || current === originalParams.trim()) {
      input_parameters = template;
      return;
    }

    confirmation(
      'Replace the current input parameters with the starter template for <strong>' + input_type + '</strong>?',
      () => { input_parameters = template; },
      () => {}
    );
  }

  function handleResetParams() {
    input_parameters = originalParams;
  }

  async function handleSave() {
    errors = [];
    saving = true;
    try {
      const payload: Record<string, unknown> = {
        name,
        objectkey,
        description,
        output_type,
        input_type,
        input_parameters,
      };
      if (selectedId !== null) payload.id = selectedId;

      const res = await api.save('blocks/save', payload);
      if (!res.success) {
        errors = res.errors ?? ['Save failed'];
      } else {
        const savedId = res.id!;
        await loadBlocks();
        selectedId = savedId;
        const updated = blocks.find(b => b.id === savedId);
        if (updated) populateForm(updated);
        quickNotice('Block saved successfully');
      }
    } catch {
      errors = ['An unexpected error occurred'];
    } finally {
      saving = false;
    }
  }

  async function handleDelete() {
    if (selectedId === null) return;
    const block = blocks.find(b => b.id === selectedId);
    confirmation(
      `Delete block <strong>${block?.name ?? ''}</strong>? This cannot be undone.`,
      async () => {
        saving = true;
        try {
          const res = await api.remove('blocks/delete', selectedId!);
          if (!res.success) {
            quickNotice('Delete failed', 'is-danger');
          } else {
            quickNotice('Block deleted', 'is-warning');
            selectedId = null;
            clearForm();
            await loadBlocks();
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
  title="Content Blocks"
  subtitle="Define the editable fields available on page templates"
  items={blocks}
  {selectedId}
  {loading}
  {saving}
  {errors}
  saveLabel="Save Block"
  newLabel="New Block"
  on:select={e => handleSelect(e.detail)}
  on:new={handleNew}
  on:save={handleSave}
  on:delete={handleDelete}
>
  <div class="field">
    <label class="label" for="block-name">Name <span class="has-text-danger">*</span></label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="block-name"
        placeholder="e.g. Page Title"
        bind:value={name}
      />
    </div>
  </div>

  <div class="field">
    <label class="label" for="block-objectkey">
      Object Key <span class="has-text-danger">*</span>
    </label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="block-objectkey"
        placeholder="e.g. page_title"
        bind:value={objectkey}
      />
    </div>
    <p class="help">Machine-readable key used in templates (no spaces)</p>
  </div>

  <div class="field">
    <label class="label" for="block-description">Description</label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="block-description"
        placeholder="Brief description of this block's purpose"
        bind:value={description}
      />
    </div>
  </div>

  <div class="columns">
    <div class="column">
      <div class="field">
        <label class="label" for="block-output-type">Output Type</label>
        <div class="control">
          <div class="select is-fullwidth">
            <select id="block-output-type" bind:value={output_type}>
              <option value="">Select…</option>
              <option value="content">Visible Content</option>
              <option value="meta">Hidden / Meta Data</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="column">
      <div class="field">
        <label class="label" for="block-input-type">Input Type</label>
        <div class="control">
          <div class="select is-fullwidth">
            <select
              id="block-input-type"
              bind:value={input_type}
              on:change={handleInputTypeChange}
            >
              <option value="">Select…</option>
              <option value="wysiwyg">WYSIWYG Editor</option>
              <option value="markdown">Markdown Editor</option>
              <option value="textfield">Single-line Text Field</option>
              <option value="textarea">Block Text Area</option>
              <option value="multifield">Multiple Fields (JSON array)</option>
              <option value="customform">Custom PHP Form (include path)</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="field">
    <label class="label" for="block-params">
      Input Parameters
      {#if input_parameters !== originalParams && originalParams !== ''}
        <button
          class="reset-params-btn"
          type="button"
          title="Reset to saved parameters"
          on:click={handleResetParams}
        >
          <span class="icon is-small has-text-info"><i class="fas fa-undo"></i></span>
          <span class="is-size-7 has-text-info">Reset</span>
        </button>
      {/if}
    </label>
    <div class="control">
      <textarea
        class="textarea is-family-monospace"
        id="block-params"
        rows="6"
        placeholder="JSON parameters or include path depending on input type"
        bind:value={input_parameters}
      ></textarea>
    </div>
    <p class="help">
      Changing the Input Type above will offer a starter template for this field.
    </p>
  </div>
</CrudShell>

<style>
  .reset-params-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 0.25rem;
    vertical-align: middle;
    margin-left: 0.4rem;
  }

  .reset-params-btn:hover {
    opacity: 0.75;
  }
</style>
