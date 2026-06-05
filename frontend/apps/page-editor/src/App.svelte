<script lang="ts">
  import PageEditor from './lib/PageEditor.svelte';
  import type { PageEditorConfig } from './lib/types/editor';

  const config = (window as Window & { __PAGE_EDITOR_CONFIG__: PageEditorConfig }).__PAGE_EDITOR_CONFIG__;
</script>

<PageEditor {config} />
