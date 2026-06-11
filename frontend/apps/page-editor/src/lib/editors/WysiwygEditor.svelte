<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import type { ContentRecord } from '../types/editor';
  import { buildSummernoteConfig, registerMediaManagerHandler } from '@crud-shared/summernote';

  export let content: ContentRecord;

  let editorEl: HTMLElement;
  let editorValue = content.content;

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const jq = (window as any).jQuery || (window as any).$;

  onMount(() => {
    if (jq && editorEl) {
      jq(editorEl).summernote(
        buildSummernoteConfig({
          height: 350,
          onChange: (contents: string) => {
            editorValue = contents;
          },
        })
      );
      registerMediaManagerHandler();
    }
  });

  onDestroy(() => {
    if (jq && editorEl) {
      try { jq(editorEl).summernote('destroy'); } catch {}
    }
  });

  export function getContent(): string {
    if (jq && editorEl) {
      try { return jq(editorEl).summernote('code'); } catch {}
    }
    return editorValue;
  }

</script>

<div class="field">
  <div class="control">
    <div bind:this={editorEl} id="edit_content">
      {@html content.content}
    </div>
  </div>
</div>

<input type="hidden" name="content" id="content_wysiwyg_hidden" value={editorValue}>
