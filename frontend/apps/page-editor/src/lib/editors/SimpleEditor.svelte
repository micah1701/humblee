<script lang="ts">
  import type { ContentRecord, ContentType } from '../types/editor';

  export let content: ContentRecord;
  export let contentType: ContentType;

  // Substitute {content} in the input_parameters template with current content
  const rendered = contentType.inputParameters
    ? contentType.inputParameters.replace(/\{content\}/g, escapeHtml(content.content))
    : '';

  function escapeHtml(str: string): string {
    return str
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  let containerEl: HTMLElement;

  export function getContent(): string {
    if (!containerEl) return content.content;
    const input = containerEl.querySelector<HTMLInputElement | HTMLTextAreaElement>('[name="content"]');
    return input ? input.value : content.content;
  }
</script>

<div class="field" bind:this={containerEl}>
  <label class="label" for="content">{contentType.name}</label>
  <div class="control">
    {@html rendered}
  </div>
</div>
