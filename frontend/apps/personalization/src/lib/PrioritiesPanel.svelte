<script lang="ts">
  import type { Persona } from './types';
  import { quickNotice } from '@crud-shared/crudUtils';

  export let personas: Persona[];
  export let xhrPath: string;

  let draggedIndex: number | null = null;
  let dragOverIndex: number | null = null;
  let saving = false;

  function onDragStart(e: DragEvent, idx: number) {
    draggedIndex = idx;
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
    }
  }

  function onDragOver(e: DragEvent, idx: number) {
    e.preventDefault();
    if (e.dataTransfer) {
      e.dataTransfer.dropEffect = 'move';
    }
    dragOverIndex = idx;
  }

  function onDragLeave() {
    dragOverIndex = null;
  }

  function onDrop(e: DragEvent, idx: number) {
    e.preventDefault();
    dragOverIndex = null;
    if (draggedIndex === null || draggedIndex === idx) {
      draggedIndex = null;
      return;
    }

    const reordered = [...personas];
    const [moved] = reordered.splice(draggedIndex, 1);
    reordered.splice(idx, 0, moved);
    personas = reordered;
    draggedIndex = null;

    saveOrder();
  }

  function onDragEnd() {
    draggedIndex = null;
    dragOverIndex = null;
  }

  async function saveOrder() {
    saving = true;
    try {
      const body = new URLSearchParams();
      for (const p of personas) {
        body.append('list_order[]', `personaID_${p.id}`);
      }
      const res = await fetch(`${xhrPath}content/p13n-order-priorities`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const data = await res.json();
      if (data.success) {
        quickNotice('Persona priority updated');
      } else {
        quickNotice('Error updating priorities', 'is-danger', 5000);
      }
    } catch {
      quickNotice('Error updating priorities', 'is-danger', 5000);
    } finally {
      saving = false;
    }
  }
</script>

<div class="panel priorities-panel">
  <div class="panel-heading">
    <span>Set Persona Priorities</span>
    {#if saving}
      <span class="icon is-small is-pulled-right has-text-grey-light">
        <i class="fas fa-spinner fa-spin"></i>
      </span>
    {/if}
  </div>

  <div class="panel-block">
    <p class="is-size-7 has-text-grey">
      The first persona to match its criteria will be used. Drag and drop to reorder — more specific criteria should be ranked higher.
    </p>
  </div>

  {#each personas as persona, idx}
    <div
      class="panel-block priority-item"
      class:is-dragging={draggedIndex === idx}
      class:is-dragover={dragOverIndex === idx && draggedIndex !== idx}
      draggable="true"
      role="listitem"
      on:dragstart={(e) => onDragStart(e, idx)}
      on:dragover={(e) => onDragOver(e, idx)}
      on:dragleave={onDragLeave}
      on:drop={(e) => onDrop(e, idx)}
      on:dragend={onDragEnd}
    >
      <span class="icon has-text-grey-light drag-handle">
        <i class="fas fa-sort"></i>
      </span>
      <span class="priority-name">{persona.name}</span>
      {#if persona.active === 0}
        <span class="tag is-light is-small ml-2 has-text-danger">&nbsp;inactive</span>
      {/if}
    </div>
  {/each}

  <div class="panel-block default-row">
    <span class="icon has-text-grey-lighter">
      <i class="fas fa-minus"></i>
    </span>
    <span class="has-text-grey">Default Content (No Persona)</span>
  </div>
</div>

<style>
  .priorities-panel {
    margin-top: 1.5rem;
  }

  .priority-item {
    cursor: grab;
    user-select: none;
    transition: background 0.1s, opacity 0.15s;
  }

  .priority-item:active {
    cursor: grabbing;
  }

  .priority-item.is-dragging {
    opacity: 0.4;
  }

  .priority-item.is-dragover {
    background-color: var(--bulma-link-light);
    border-top: 2px solid var(--bulma-link);
  }

  .drag-handle {
    margin-right: 0.5rem;
    flex-shrink: 0;
  }

  .priority-name {
    flex: 1;
  }

  .default-row {
    background: var(--bulma-scheme-main-ter);
    color: var(--bulma-text-soft);
    font-style: italic;
  }
</style>
