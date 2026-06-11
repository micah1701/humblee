<script lang="ts">
  import { onMount, onDestroy, createEventDispatcher } from 'svelte';
  import type { ContentRecord } from '../types/editor';
  import { buildSummernoteConfig } from '@crud-shared/summernote';

  export let content: ContentRecord;

  const dispatch = createEventDispatcher<{ 'open-media': (url: string) => void }>();

  let editorEl: HTMLElement;
  let editorValue = content.content;

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const jq = (window as any).jQuery || (window as any).$;

  function insertImage(url: string) {
    if (!jq || !editorEl) return;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    jq(editorEl).summernote('insertImage', url, ($image: any) => {
      const img = $image[0];
      const width = img.naturalWidth || img.offsetWidth;
      const maxWidth = width > 0 && width < 800 ? width : 800;
      img.style.width = '100%';
      img.style.maxWidth = `${maxWidth}px`;
      img.classList.add('cms-image');
    });
  }

  onMount(() => {
    if (jq && editorEl) {
      jq(editorEl).summernote(
        buildSummernoteConfig({
          height: 350,
          onChange: (contents: string) => {
            editorValue = contents;
          },
          openMediaManager: () => dispatch('open-media', insertImage),
        })
      );
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
