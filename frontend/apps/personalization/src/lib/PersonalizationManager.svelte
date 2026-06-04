<script lang="ts">
  import CrudShell from '@crud-shared/CrudShell.svelte';
  import { createCrudApi } from '@crud-shared/crudApi';
  import { quickNotice, confirmation } from '@crud-shared/crudUtils';
  import CriteriaBuilder from './CriteriaBuilder.svelte';
  import PrioritiesPanel from './PrioritiesPanel.svelte';
  import type { Persona, Role, Criteria } from './types';

  export let xhrPath: string;
  export let i18nSegments: string[];
  export let roles: Role[];

  const api = createCrudApi(xhrPath);

  // ── State ────────────────────────────────────────────────────────────────
  let personas: Persona[] = [];
  let selectedId: number | null = null;
  let loading = true;
  let saving = false;
  let errors: string[] = [];

  // Form fields
  let name = '';
  let description = '';
  let active = true;
  let criteria: Criteria = [];
  let originalCriteriaJson = '';

  $: criteriaChanged = JSON.stringify(criteria) !== originalCriteriaJson;

  // ── Init ──────────────────────────────────────────────────────────────
  async function loadPersonas() {
    loading = true;
    try {
      personas = await api.list<Persona>('personalization/list');
    } catch {
      quickNotice('Failed to load personas', 'is-danger');
    } finally {
      loading = false;
    }
  }

  loadPersonas();

  // ── Helpers ───────────────────────────────────────────────────────────
  function parseCriteria(json: string): Criteria {
    if (!json || json.trim() === '') return [];
    try {
      const parsed = JSON.parse(json);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  function clearForm() {
    name = '';
    description = '';
    active = true;
    criteria = [];
    originalCriteriaJson = '';
    errors = [];
  }

  function populateForm(persona: Persona) {
    name = persona.name;
    description = persona.description;
    active = persona.active === 1;
    criteria = parseCriteria(persona.criteria);
    originalCriteriaJson = JSON.stringify(criteria);
    errors = [];
  }

  function handleSelect(id: number) {
    selectedId = id;
    const persona = personas.find(p => p.id === id);
    if (persona) populateForm(persona);
  }

  function handleNew() {
    selectedId = null;
    clearForm();
  }

  function resetCriteria() {
    criteria = parseCriteria(originalCriteriaJson);
  }

  // ── Save ──────────────────────────────────────────────────────────────
  async function handleSave() {
    errors = [];
    saving = true;
    try {
      const payload: Record<string, unknown> = {
        name,
        description,
        active: active ? 1 : 0,
        criteria: criteria.length > 0 ? JSON.stringify(criteria) : '',
      };
      if (selectedId !== null) payload.id = selectedId;

      const res = await api.save('personalization/save', payload);
      if (!res.success) {
        errors = res.errors ?? ['Save failed'];
      } else {
        const savedId = res.id!;
        await loadPersonas();
        selectedId = savedId;
        const updated = personas.find(p => p.id === savedId);
        if (updated) populateForm(updated);
        quickNotice('Persona saved successfully');
      }
    } catch {
      errors = ['An unexpected error occurred'];
    } finally {
      saving = false;
    }
  }

  // ── Delete ────────────────────────────────────────────────────────────
  async function handleDelete() {
    if (selectedId === null) return;
    const persona = personas.find(p => p.id === selectedId);
    confirmation(
      `Delete persona <strong>${persona?.name ?? ''}</strong>? This cannot be undone.`,
      async () => {
        saving = true;
        try {
          const res = await api.remove('personalization/delete', selectedId!);
          if (!res.success) {
            quickNotice('Delete failed', 'is-danger');
          } else {
            quickNotice('Persona deleted', 'is-warning');
            selectedId = null;
            clearForm();
            await loadPersonas();
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

  $: generatedJson = criteria.length > 0 ? JSON.stringify(criteria, null, 2) : '';
</script>

<CrudShell
  title="Personas"
  subtitle="Define audience segments for personalized content"
  items={personas}
  {selectedId}
  {loading}
  {saving}
  {errors}
  saveLabel="Save Persona"
  newLabel="New Persona"
  on:select={e => handleSelect(e.detail)}
  on:new={handleNew}
  on:save={handleSave}
  on:delete={handleDelete}
>
  <!-- Name -->
  <div class="field">
    <label class="label" for="p13n-name">
      Name <span class="has-text-danger">*</span>
    </label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="p13n-name"
        placeholder="e.g. Spanish Speakers"
        bind:value={name}
      />
    </div>
  </div>

  <!-- Description -->
  <div class="field">
    <label class="label" for="p13n-description">Description</label>
    <div class="control">
      <input
        class="input"
        type="text"
        id="p13n-description"
        placeholder="Brief description of this persona"
        bind:value={description}
      />
    </div>
  </div>

  <!-- Active -->
  <div class="field">
    <label class="checkbox">
      <input type="checkbox" bind:checked={active} />
      &nbsp;Active — uncheck to disable this persona without deleting it
    </label>
  </div>

  <hr />

  <!-- Criteria builder -->
  <div class="field">
    <label class="label">
      Criteria
      {#if criteriaChanged && originalCriteriaJson !== ''}
        <button
          type="button"
          class="reset-criteria-btn tooltip is-tooltip-right"
          data-tooltip="Reset criteria to saved state"
          on:click={resetCriteria}
        >
          <span class="icon is-small has-text-info"><i class="fas fa-undo"></i></span>
          <span class="is-size-7 has-text-info">Reset</span>
        </button>
      {/if}
    </label>
  </div>

  <CriteriaBuilder bind:criteria {i18nSegments} {roles} />

  <!-- Generated JSON preview -->
  {#if generatedJson}
    <div class="field mt-4">
      <label class="label is-small has-text-grey" for="p13n-criteria-json">Generated Criteria JSON</label>
      <div class="control">
        <textarea
          class="textarea is-family-monospace is-small has-text-grey"
          id="p13n-criteria-json"
          rows="4"
          readonly
          value={generatedJson}
        ></textarea>
      </div>
    </div>
  {/if}
</CrudShell>

<!-- Priorities panel — only shown when there's more than one persona -->
{#if personas.length > 1}
  <PrioritiesPanel bind:personas {xhrPath} />
{/if}

<style>
  .reset-criteria-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 0.25rem;
    vertical-align: middle;
    margin-left: 0.4rem;
  }

  .reset-criteria-btn:hover {
    opacity: 0.75;
  }
</style>
