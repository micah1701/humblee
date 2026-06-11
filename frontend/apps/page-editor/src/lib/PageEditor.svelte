<script lang="ts">
  import { onMount } from 'svelte';
  import type { PageEditorConfig } from './types/editor';
  import { createContentApi } from './services/contentApi';
  import { dateFormat, quickNotice, confirmation } from './utils/editorUtils';
  import SeoEditor from './editors/SeoEditor.svelte';
  import FeedEditor from './editors/FeedEditor.svelte';
  import WysiwygEditor from './editors/WysiwygEditor.svelte';
  import MultifieldEditor from './editors/MultifieldEditor.svelte';
  import SimpleEditor from './editors/SimpleEditor.svelte';

  export let config: PageEditorConfig;

  const { content, contentType, pageData, revisions, allContentTypes, allSlots, currentTemplateBlockId,
          allP13nVersions, isInIframe, useP13n, xhrPath, appPath, feedHmac, domain } = config;

  const contentApi = createContentApi(xhrPath);

  // Feed widget path detection
  const FEED_WIDGET_PATH = 'contentWidgets/feed/edit.php';
  const SEO_WIDGET_PATH = 'seo';

  $: isFeedWidget = contentType.inputType === 'customform' && contentType.inputParameters.includes(FEED_WIDGET_PATH);
  $: isSeoWidget = contentType.inputType === 'customform' && contentType.inputParameters.includes(SEO_WIDGET_PATH);

  // Stale revision check
  $: isOldVersion = revisions.length > 1 && content.revisionDate !== revisions[0]?.revisionDate;

  // Revision history visibility
  let showRevisions = isOldVersion;

  // Sub-editor refs (for getContent())
  let seoEditorRef: SeoEditor;
  let wysiwygEditorRef: WysiwygEditor;
  let multifieldEditorRef: MultifieldEditor;
  let simpleEditorRef: SimpleEditor;

  // Media manager modal state
  let mediaManagerOpen = false;
  let mediaManagerCallback: ((url: string) => void) | null = null;
  let imagePropertiesOpen = false;

  function openMediaManager(callback: (url: string) => void) {
    mediaManagerCallback = callback;
    mediaManagerOpen = true;
  }

  function closeMediaManager() {
    mediaManagerOpen = false;
    mediaManagerCallback = null;
  }

  // Slot selector navigation (new-style: template_block_id; legacy: content_type)
  function onSlotChange(e: Event) {
    const select = e.target as HTMLSelectElement;
    const tbId = Number(select.value);
    const frameParam = isInIframe ? '&iframe' : '';
    if (tbId > 0) {
      window.location.href = `${appPath}admin/edit/?page_id=${content.pageId}&template_block_id=${tbId}&p13n_id=${content.p13nId}${frameParam}`;
    } else {
      // Legacy: value encodes typeId as negative to distinguish from template_block_ids
      const typeId = Math.abs(tbId);
      window.location.href = `${appPath}admin/edit/?page_id=${content.pageId}&content_type=${typeId}&p13n_id=${content.p13nId}${frameParam}`;
    }
  }

  // Content type selector navigation (legacy fallback)
  function onContentTypeChange(e: Event) {
    const select = e.target as HTMLSelectElement;
    const frameParam = isInIframe ? '&iframe' : '';
    window.location.href = `${appPath}admin/edit/?page_id=${content.pageId}&content_type=${select.value}&p13n_id=${content.p13nId}${frameParam}`;
  }

  // P13n selector navigation
  function onP13nChange(e: Event) {
    const select = e.target as HTMLSelectElement;
    const frameParam = isInIframe ? '&iframe' : '';
    if (content.templateBlockId > 0) {
      window.location.href = `${appPath}admin/edit/?page_id=${content.pageId}&template_block_id=${content.templateBlockId}&p13n_id=${select.value}${frameParam}`;
    } else {
      window.location.href = `${appPath}admin/edit/?page_id=${content.pageId}&content_type=${content.typeId}&p13n_id=${select.value}${frameParam}`;
    }
  }

  // Revision selector navigation
  function onRevisionChange(e: Event) {
    const select = e.target as HTMLSelectElement;
    const frameParam = isInIframe ? '?iframe' : '';
    window.location.href = `${appPath}admin/edit/${select.value}${frameParam}`;
  }

  // Format dates for display
  function fmtDate(dt: string): string {
    return dateFormat('M j, Y g:ia', dt);
  }

  // Save flow for non-feed content types
  let saving = false;

  async function handleSave(publish: boolean) {
    if (saving) return;
    saving = true;

    try {
      const result = await contentApi.checkLatestRevision(content.pageId, content.typeId, content.p13nId, content.templateBlockId);

      if (result.error) {
        quickNotice(result.error, 'is-danger');
        return;
      }

      if (!result.success) {
        quickNotice('Could not validate content at this time.', 'is-danger');
        return;
      }

      const doSubmit = () => submitForm(publish);

      if (result.content) {
        const pageLoadTime = new Date(content.revisionDate + 'Z');
        const latestRevisionTime = new Date(result.content.revision_date + 'Z');

        if (latestRevisionTime > pageLoadTime) {
          const action = result.content.live ? 'published live' : 'draft saved';
          const verb = publish ? 'publish' : 'save';
          const msg = `While you were editing, a more recent ${action} was made by <strong>${result.content.name}</strong> (${dateFormat('M j, Y g:ia', result.content.revision_date)}).<br><br>Do you still want to ${verb} your changes?`;
          confirmation(msg, doSubmit, () => {});
        } else {
          doSubmit();
        }
      } else {
        doSubmit();
      }
    } catch {
      quickNotice('Error validating content. Please try again.', 'is-danger');
    } finally {
      saving = false;
    }
  }

  function getSerializedContent(): string {
    if (isSeoWidget && seoEditorRef) return seoEditorRef.getContent();
    if (contentType.inputType === 'wysiwyg' && wysiwygEditorRef) return wysiwygEditorRef.getContent();
    if (contentType.inputType === 'multifield' && multifieldEditorRef) return multifieldEditorRef.getContent();
    if (simpleEditorRef) return simpleEditorRef.getContent();
    return '';
  }

  function submitForm(publish: boolean) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${appPath}admin/edit/${content.id}`;

    const fields: Record<string, string> = {
      content_id:        String(content.id),
      page_id:           String(content.pageId),
      p13n_id:           String(content.p13nId),
      content_type_id:   String(content.typeId),
      template_block_id: String(content.templateBlockId),
      content_type:      contentType.inputType,
      content:           getSerializedContent(),
      live:              publish ? '1' : '0',
    };

    // For customform/multifield PHP uses serialize_fields; we already built JSON above.
    // For customform (SEO), PHP expects serialize_fields — we pass the JSON directly as "content"
    // and omit serialize_fields since we're sending pre-built JSON.

    for (const [name, value] of Object.entries(fields)) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
  }

  // Media manager iframe message listener
  onMount(() => {
    const handleMessage = (e: MessageEvent) => {
      if (e.data?.type === 'media-selected' && mediaManagerCallback) {
        mediaManagerCallback(e.data.url);
        closeMediaManager();
      }
    };
    window.addEventListener('message', handleMessage);
    return () => window.removeEventListener('message', handleMessage);
  });
</script>

<!-- ── Header row ── -->
<div class="level mb-4">
  <div class="level-left">
    <div class="level-item">
      <h1 class="title is-4 mb-0">Edit Content</h1>
    </div>
    <div class="level-item">
      <a
        class="button is-light is-small tooltip"
        data-tooltip="Preview how this revision will appear on the site"
        href="{appPath}{pageData.url.replace(/^\//, '')}?preview={content.id}"
        target="_blank"
      >
        <span class="icon"><i class="fas fa-eye"></i></span>
        <span>Preview</span>
      </a>
    </div>
  </div>

  <div class="level-right">
    {#if !isInIframe && allSlots.length > 1}
      <div class="level-item">
        <span class="tooltip" data-tooltip="Select another content block for this page">
          <div class="select is-small">
            <select on:change={onSlotChange}>
              {#each allSlots as slot}
                <option
                  value={slot.templateBlockId}
                  selected={slot.templateBlockId === currentTemplateBlockId && (slot.templateBlockId > 0 || slot.contentTypeId === content.typeId)}
                >
                  {slot.label ? `${slot.label} (${slot.contentTypeName})` : slot.contentTypeName}
                </option>
              {/each}
            </select>
          </div>
        </span>
      </div>
    {:else if !isInIframe && allContentTypes.length > 1}
      <div class="level-item">
        <span class="tooltip" data-tooltip="Select another content block for this page">
          <div class="select is-small">
            <select on:change={onContentTypeChange}>
              {#each allContentTypes as ct}
                <option value={ct.id} selected={ct.id === content.typeId}>{ct.name}</option>
              {/each}
            </select>
          </div>
        </span>
      </div>
    {/if}

    {#if useP13n && allP13nVersions.length > 0}
      <div class="level-item">
        <span class="tooltip" data-tooltip="Select a personalization version of this content">
          <div class="select is-small">
            <select on:change={onP13nChange}>
              {#each allP13nVersions as p}
                <option value={p.id} selected={p.id === content.p13nId}>{p.name}</option>
              {/each}
            </select>
          </div>
        </span>
      </div>
    {/if}
  </div>
</div>

<!-- ── Stale revision warning ── -->
{#if isOldVersion}
  <div class="notification is-warning is-light mb-4">
    <span class="icon"><i class="fa fa-info-circle"></i></span>
    A more recently saved revision of this content exists.
  </div>
{/if}

<!-- ── Meta info row ── -->
<div class="columns mb-3">
  <div class="column">
    <p>
      Editing <strong>{contentType.name}</strong>
      {#if contentType.description}
        <span class="icon has-text-info tooltip is-size-7" data-tooltip="{contentType.description}">
          <i class="far fa-question-circle"></i>
        </span>
      {/if}
      <br>
      For page:
      {#if isInIframe}
        <strong>{pageData.label}</strong>
      {:else}
        <a href="{pageData.url}" target="_blank">{pageData.label}</a>
      {/if}
      {#if !pageData.active}
        <span class="tag is-warning is-light ml-1 tooltip" data-tooltip="This page is currently inactive">(inactive)</span>
      {/if}
    </p>
    {#if useP13n && content.p13nId !== 0}
      {#each allP13nVersions as p}
        {#if p.id === content.p13nId}
          <p class="mt-1">
            Specific to persona: <strong>{p.name}</strong>
            {#if p.description}
              <span class="icon has-text-warning tooltip is-size-7" data-tooltip="{p.description}">
                <i class="fas fa-user"></i>
              </span>
            {/if}
          </p>
        {/if}
      {/each}
    {/if}
  </div>

  <div class="column">
    {#if content.updatedBy !== 0}
      <p>
        <strong>Saved:</strong> {fmtDate(content.revisionDate)}
        &nbsp; <strong>By:</strong> {content.updatedByName}
      </p>
      <p>
        {#if content.publishDate === null}
          <span class="tag is-info is-light">Unpublished Draft</span>
          This content has not yet been published.
        {:else if content.live}
          <span class="tag is-success is-light" title="Published {fmtDate(content.publishDate)}">Live Version</span>
          This content is currently live on the site.
        {:else}
          <span class="tag is-danger is-light" title="Published {fmtDate(content.publishDate)}">Previously Published</span>
          This revision was previously live on the site.
        {/if}
      </p>
    {/if}

    <!-- Revision history -->
    {#if !isOldVersion}
      <button
        class="button is-small is-light mt-2 tooltip"
        data-tooltip="Show revision history"
        on:click={() => showRevisions = !showRevisions}
      >
        <span>History</span>
        <span class="icon"><i class="fas fa-history"></i></span>
      </button>
    {/if}

    {#if showRevisions || isOldVersion}
      <div class="revision-history-panel mt-2">
        <div class="select is-small">
          <select on:change={onRevisionChange}>
            {#each revisions as rev}
              <option value={rev.id} selected={rev.id === content.id}>
                {fmtDate(rev.revisionDate)}
                {#if rev.live} — LIVE
                {:else if rev.publishDate !== null} — Previously published
                {:else} — Draft (never published)
                {/if}
              </option>
            {/each}
            {#if revisions.length === 0}
              <option value="">No previous revisions found</option>
            {/if}
          </select>
        </div>
      </div>
    {/if}
  </div>
</div>

<hr class="mt-2 mb-4">

<!-- ── Editor area ── -->
<div class="editor-box">
  {#if isFeedWidget}
    <FeedEditor
      {content}
      {appPath}
      {feedHmac}
    />
  {:else if isSeoWidget}
    <SeoEditor
      bind:this={seoEditorRef}
      {content}
      {pageData}
      {domain}
      on:open-media={(e) => openMediaManager(e.detail)}
    />
  {:else if contentType.inputType === 'wysiwyg'}
    <WysiwygEditor
      bind:this={wysiwygEditorRef}
      {content}
      on:open-media={(e) => openMediaManager(e.detail)}
    />
  {:else if contentType.inputType === 'multifield'}
    <MultifieldEditor
      bind:this={multifieldEditorRef}
      {content}
      {contentType}
      on:open-media={(e) => openMediaManager(e.detail)}
    />
  {:else}
    <SimpleEditor
      bind:this={simpleEditorRef}
      {content}
      {contentType}
    />
  {/if}
</div>

<!-- ── Save / Publish buttons (non-feed types) ── -->
{#if !isFeedWidget}
  <div class="buttons mt-3">
    <button
      class="button is-primary"
      class:is-loading={saving}
      on:click={() => handleSave(false)}
    >
      <span class="icon"><i class="far fa-save"></i></span>
      <span>Save Draft</span>
    </button>
    <button
      class="button is-primary is-outlined"
      class:is-loading={saving}
      on:click={() => handleSave(true)}
    >
      <span class="icon"><i class="fas fa-rocket"></i></span>
      <span>Publish Live to Site</span>
    </button>
  </div>
{/if}

<!-- ── Media Manager Modal ── -->
{#if mediaManagerOpen}
  <div class="modal is-active">
    <div class="modal-background" on:click={closeMediaManager} role="none"></div>
    <div class="modal-card" style="width: 90%; max-width: 1200px;">
      <header class="modal-card-head">
        <p class="modal-card-title">Media Manager</p>
        <button class="delete" aria-label="close" on:click={closeMediaManager}></button>
      </header>
      <section class="modal-card-body" style="padding: 0; min-height: 500px;">
        <iframe
          src="{appPath}admin/media?iframe=true"
          title="Media Manager"
          style="width: 100%; height: 600px; border: none;"
        ></iframe>
      </section>
    </div>
  </div>
{/if}
