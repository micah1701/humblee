<script lang="ts">
  import { onMount, tick } from 'svelte';
  import type { ContentRecord, ContentType } from '../types/editor';

  export let content: ContentRecord;
  export let contentType: ContentType;

  interface FieldDef {
    key: string;
    label: string;
    inputHtml: string;
  }

  let fields: FieldDef[] = [];
  let fieldValues: Record<string, string> = {};
  let containerEl: HTMLElement;

  // Parse existing content and input_parameters
  let contentArray: Record<string, string> = {};
  try {
    contentArray = JSON.parse(content.content) || {};
  } catch {
    contentArray = {};
  }

  let inputDefs: Array<Record<string, { label: string; input: string }>> = [];
  try {
    inputDefs = JSON.parse(contentType.inputParameters) || [];
  } catch {
    inputDefs = [];
  }

  // Build field definitions, substituting {content} placeholder
  fields = inputDefs.map((row) => {
    const key = Object.keys(row)[0];
    const def = row[key];
    const currentValue = contentArray[key] ?? '';
    const inputHtml = def.input.replace(/\{content\}/g, escapeHtml(currentValue));
    fieldValues[key] = currentValue;
    return { key, label: def.label, inputHtml };
  });

  function escapeHtml(str: string): string {
    return str
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  onMount(async () => {
    await tick();
    // Attach change listeners to all rendered inputs/selects within the container
    if (!containerEl) return;
    const inputs = containerEl.querySelectorAll('input:not([type=hidden]), select, textarea');
    inputs.forEach((el) => {
      const name = (el as HTMLInputElement).name || (el as HTMLInputElement).id;
      if (name && fields.some(f => f.key === name)) {
        fieldValues[name] = (el as HTMLInputElement).value;
        el.addEventListener('input', () => {
          fieldValues[name] = (el as HTMLInputElement).value;
        });
        el.addEventListener('change', () => {
          fieldValues[name] = (el as HTMLInputElement).value;
        });
      }
    });
  });

  export function getContent(): string {
    return JSON.stringify(fieldValues);
  }
</script>

<div bind:this={containerEl} class="multifield-editor">
  {#each fields as field}
    <div class="columns mb-3">
      <div class="column is-one-third">
        <label class="label">{field.label}</label>
      </div>
      <div class="column">
        {@html field.inputHtml}
      </div>
    </div>
  {/each}
</div>

<style>
  .multifield-editor :global(input),
  .multifield-editor :global(select),
  .multifield-editor :global(textarea) {
    width: 100%;
    max-width: 100%;
  }
</style>
