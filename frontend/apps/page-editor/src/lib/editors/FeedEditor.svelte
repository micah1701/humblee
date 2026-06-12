<script lang="ts">
  import { onMount } from 'svelte';
  import type { ContentRecord, FeedHmac, ArticleData, ArticleRevision, ArticleContents } from '../types/editor';
  import { createFeedApi } from '../services/feedApi';
  import { dateFormat, quickNotice, confirmation } from '../utils/editorUtils';

  export let content: ContentRecord;
  export let appPath: string;
  export let feedHmac: FeedHmac | null;

  const feedApi = createFeedApi(appPath);

  // State
  let articleData: ArticleData | null = null;
  let articleContent: ArticleRevision | null = null;
  let loading = true;
  let saving = false;
  let showRevisions = false;

  // Form field state (bound to inputs)
  let headline = '';
  let dateline = '';
  let body = '';
  let imageSrc = '';
  let imageAlt = '';
  let linkUrl = '';
  let linkLabel = '';
  let template = 'default';
  let displayDate = '';
  let endDate = '';

  // UI state
  let imageExpanded = false;
  let linkExpanded = false;
  let activeTemplate = 'default';

  // Media manager modal state
  let mediaManagerOpen = false;

  // Showdown converter (loaded from CDN)
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const showdown = (window as any).showdown;
  const converter = showdown ? new showdown.Converter() : null;

  $: renderedBody = converter ? converter.makeHtml(body) : body;

  // Character count helpers
  $: headlineLen = headline.length;
  $: datelineLen = dateline.length;

  // Link target (internal vs external)
  $: linkIsExternal = linkUrl && linkUrl.charAt(0) !== '/' && !linkUrl.includes(window.location.hostname);
  $: linkTarget = linkIsExternal ? '_blank' : '_self';

  async function loadArticle(articleId: number) {
    if (articleId === 0 || isNaN(articleId)) {
      articleContent = newEmptyArticle();
      articleData = null;
      loading = false;
      return;
    }

    try {
      loading = true;
      articleData = await feedApi.getArticle(articleId);
      articleContent = articleData.revisions[articleData.selected];
      populateFields(articleContent);
    } catch {
      quickNotice('Could not load article. Please refresh.', 'is-danger');
    } finally {
      loading = false;
    }
  }

  function newEmptyArticle(): ArticleRevision {
    return {
      id: 0,
      contents: {
        template: 'default', display_date: '', end_date: '',
        headline: '', dateline: '', content: '',
        image: { src: '', altText: '' },
        link: { url: '', label: '' },
      },
      revision_date: '',
      first_edition: true,
    };
  }

  function populateFields(article: ArticleRevision) {
    const c = article.contents;
    headline = c.headline ?? '';
    dateline = c.dateline ?? '';
    body = c.content ?? '';
    imageSrc = c.image?.src ?? '';
    imageAlt = c.image?.altText ?? '';
    linkUrl = c.link?.url ?? '';
    linkLabel = c.link?.label ?? '';
    template = c.template ?? 'default';
    displayDate = c.display_date ?? '';
    endDate = c.end_date ?? '';
    activeTemplate = template;

    // Expand sections if they have data
    imageExpanded = !!imageSrc;
    linkExpanded = !!linkUrl;
  }

  function getCurrentEdits(): ArticleContents {
    return {
      template,
      headline,
      dateline,
      content: body,
      image: { src: imageSrc, altText: imageAlt },
      link: { url: linkUrl, label: linkLabel, buttonClass: 'magenta' },
      display_date: displayDate,
      end_date: endDate,
    };
  }

  function isDirty(): boolean {
    if (!articleContent) return true;
    return JSON.stringify(articleContent.contents) !== JSON.stringify(getCurrentEdits());
  }

  async function save(publish = false, newDraft = false) {
    if (saving) return;

    if (!isDirty()) {
      quickNotice('No changes made. Nothing to save.', 'is-danger');
      return;
    }

    saving = true;
    try {
      const saveId = articleData?.id ?? 0;
      const saveParent = (articleContent && articleContent.first_edition !== true)
        ? articleContent.first_edition as number
        : saveId;

      const result = await feedApi.saveArticle({
        id: saveId,
        newDraft,
        parent_id: saveParent,
        publish,
        articleEdits: { contents: getCurrentEdits() },
        hmac_token: feedHmac?.token ?? '',
        hmac_key: feedHmac?.key ?? '',
      });

      if (result.success && result.new_id) {
        quickNotice(publish ? 'Article published!' : 'Changes saved!');
        window.history.pushState(null, '', `#${result.new_id}`);
        await loadArticle(result.new_id);
      } else {
        quickNotice(result.error ?? 'Save failed. Please try again.', 'is-danger');
      }
    } catch {
      quickNotice('Save failed. Please try again.', 'is-danger');
    } finally {
      saving = false;
    }
  }

  function onRevisionChange(e: Event) {
    const select = e.target as HTMLSelectElement;
    const id = parseInt(select.value);
    window.history.pushState(null, '', `#${id}`);
    loadArticle(id);
  }

  function onTemplateChange() {
    activeTemplate = template;
  }

  function openImagePicker() {
    mediaManagerOpen = true;
  }

  function closeMediaManager() {
    mediaManagerOpen = false;
  }

  // Handle message from media manager iframe
  function onMediaMessage(e: MessageEvent) {
    if (e.data?.type === 'media-selected') {
      const protocol = window.location.protocol;
      const host = window.location.hostname;
      imageSrc = encodeURI(`${protocol}//${host}${e.data.url}`);
      imageExpanded = true;
      closeMediaManager();
    }
  }

  function fmtRevDate(dt: string): string {
    return dateFormat('M j, Y g:ia', dt);
  }

  $: isNewArticle = !articleData;
  $: isPublished = articleContent?.status && articleContent.status !== 'Draft';
  $: isLatestRevision = articleContent?.latest_revision === true;

  onMount(() => {
    // Load from URL hash or use content.id
    const hash = location.hash.substring(1);
    const idToLoad = !isNaN(Number(hash)) && hash !== '' ? Number(hash) : content.id;
    loadArticle(idToLoad);

    const handleHash = () => {
      const h = location.hash.substring(1);
      if (!isNaN(Number(h))) loadArticle(Number(h));
    };
    window.addEventListener('hashchange', handleHash);
    window.addEventListener('message', onMediaMessage);
    return () => {
      window.removeEventListener('hashchange', handleHash);
      window.removeEventListener('message', onMediaMessage);
    };
  });
</script>

{#if loading}
  <div class="has-text-centered py-6">
    <span class="icon is-large"><i class="fas fa-spinner fa-spin fa-2x"></i></span>
    <p class="mt-2">Loading article…</p>
  </div>
{:else}

<!-- ── Header row with save buttons ── -->
<div class="level mb-4">
  <div class="level-left">
    <div class="level-item">
      <a href="{appPath}admin/feed" class="icon-text is-size-7 tooltip" data-tooltip="Return to list without saving">
        <span class="icon"><i class="fas fa-arrow-circle-left"></i></span>
        <span>Return to Feed List</span>
      </a>
    </div>
  </div>
  <div class="level-right">
    <div class="level-item">
      <div class="buttons">
        {#if !isNewArticle && !isPublished}
          <button
            class="button is-primary is-small"
            class:is-loading={saving}
            on:click={() => save(false, false)}
            title="Save your changes to this revision"
          >
            <span class="icon"><i class="fas fa-save"></i></span>
            <span>Save Draft</span>
          </button>
        {/if}
        {#if !isNewArticle}
          <button
            class="button is-light is-small"
            class:is-loading={saving}
            on:click={() => save(false, true)}
            title="Save as a new draft revision"
          >
            <span class="icon"><i class="far fa-save"></i></span>
            <span>Save as New Draft</span>
          </button>
        {/if}
        <button
          class="button is-primary is-outlined is-small"
          class:is-loading={saving}
          on:click={() => save(true)}
          title="Publish this revision live to the site"
        >
          <span class="icon"><i class="fas fa-rocket"></i></span>
          <span>Save &amp; Publish</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Revision status ── -->
{#if articleContent && !isNewArticle}
  <div class="mb-4">
    {#if !isLatestRevision}
      <div class="notification is-warning is-light mb-2">
        <span class="icon"><i class="fa fa-info-circle"></i></span>
        A more recently saved revision of this article exists.
      </div>
    {/if}

    <div class="level is-mobile">
      <div class="level-left">
        {#if articleContent.revision_date}
          <div class="level-item">
            <p class="is-size-7">
              <strong>Saved:</strong> {fmtRevDate(articleContent.revision_date)}
              {#if articleContent.updated_by_name}
                &nbsp;<strong>By:</strong> {articleContent.updated_by_name}
              {/if}
            </p>
          </div>
        {/if}
        <div class="level-item">
          {#if articleContent.status === 'Draft'}
            <span class="tag is-info is-light">Unpublished Draft</span>
          {:else if articleContent.latest_published === true}
            <span class="tag is-success is-light">
              Active Article
              {#if articleContent.status === 'Published Future'}(Future Date){/if}
              {#if articleContent.status === 'Published Expired'} — Expired{/if}
            </span>
          {:else}
            <span class="tag is-danger is-light">Previously Published</span>
          {/if}
        </div>
      </div>
      <div class="level-right">
        {#if !isNewArticle}
          <div class="level-item">
            <button
              class="button is-small is-light"
              on:click={() => showRevisions = !showRevisions}
            >
              <span>History</span>
              <span class="icon"><i class="fas fa-history"></i></span>
            </button>
          </div>
        {/if}
      </div>
    </div>

    {#if showRevisions && articleData}
      <div class="revision-history-panel mb-3">
        <div class="select is-small">
          <select on:change={onRevisionChange}>
            {#each articleData.revisions as rev}
              <option value={rev.id} selected={rev.id === articleData.id}>
                {rev.revision_date ? fmtRevDate(rev.revision_date) : 'Unknown date'}
                ({rev.status ?? 'Draft'}
                {#if rev.latest_published === true} — LIVE{:else if rev.status !== 'Draft'} — Replaced{/if})
              </option>
            {/each}
          </select>
        </div>
      </div>
    {/if}
  </div>
{/if}

<hr class="mt-0 mb-4">

<!-- ── Editor columns ── -->
<div class="columns">
  <!-- Left: form inputs -->
  <div class="column">

    <div class="field">
      <label class="label" for="feed_headline">Headline</label>
      <div class="control">
        <input
          class="input"
          id="feed_headline"
          type="text"
          maxlength="50"
          bind:value={headline}
        >
      </div>
      <p class="char-counter" class:is-near-limit={headlineLen > 40} class:is-at-limit={headlineLen >= 50}>
        {headlineLen} / 50
      </p>
    </div>

    <div class="field">
      <label class="label" for="feed_dateline">
        Dateline
        <span class="icon has-text-info tooltip is-size-7" data-tooltip="Optional subheading">
          <i class="fas fa-info-circle"></i>
        </span>
      </label>
      <div class="control">
        <input
          class="input"
          id="feed_dateline"
          type="text"
          maxlength="50"
          bind:value={dateline}
          placeholder="e.g. 15 June 2025 — Windsor, CT"
        >
      </div>
      <p class="char-counter" class:is-near-limit={datelineLen > 40} class:is-at-limit={datelineLen >= 50}>
        {datelineLen} / 50
      </p>
    </div>

    <div class="field">
      <label class="label" for="feed_content">Content</label>
      <div class="control">
        <textarea
          class="textarea feed-textarea"
          id="feed_content"
          bind:value={body}
        ></textarea>
      </div>
      <p class="help">
        Formatting allowed using basic
        <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">Markdown</a>
      </p>
    </div>

    <!-- Image section (collapsible) -->
    <div class="box mb-3">
      <button
        class="button is-ghost is-fullwidth has-text-left is-size-5 collapse-trigger mb-0"
        on:click={() => imageExpanded = !imageExpanded}
      >
        <span class="icon is-small mr-1"><i class="fas fa-image"></i></span>
        Article Image
        <span class="icon is-small ml-2"><i class="fas fa-chevron-{imageExpanded ? 'up' : 'down'}"></i></span>
      </button>

      {#if imageExpanded}
        <div class="mt-3">
          <div class="field">
            <label class="label" for="feed_image_src">Image Path</label>
            <div class="field has-addons">
              <div class="control is-expanded">
                <input
                  class="input"
                  id="feed_image_src"
                  type="text"
                  bind:value={imageSrc}
                  placeholder="https://…"
                >
              </div>
              <div class="control">
                <button class="button is-info" type="button" on:click={openImagePicker}>
                  <span class="icon"><i class="fa fa-image"></i></span>
                  <span>Select</span>
                </button>
              </div>
            </div>
            <p class="help">Must be a fully qualified URL starting with <em>https://</em></p>
          </div>

          <div class="field">
            <label class="label" for="feed_image_alt">
              Accessibility Description
              <span class="icon has-text-info tooltip is-size-7" data-tooltip="Alt text for screen readers">
                <i class="fas fa-info-circle"></i>
              </span>
            </label>
            <div class="control">
              <input
                class="input"
                id="feed_image_alt"
                type="text"
                bind:value={imageAlt}
                placeholder='e.g. "Portrait of CEO Jane Smith"'
              >
            </div>
          </div>
        </div>
      {/if}
    </div>

    <!-- Link section (collapsible) -->
    <div class="box">
      <button
        class="button is-ghost is-fullwidth has-text-left is-size-5 collapse-trigger mb-0"
        on:click={() => linkExpanded = !linkExpanded}
      >
        <span class="icon is-small mr-1"><i class="fas fa-link"></i></span>
        Article Link
        <span class="icon is-small ml-2"><i class="fas fa-chevron-{linkExpanded ? 'up' : 'down'}"></i></span>
      </button>

      {#if linkExpanded}
        <div class="mt-3">
          <div class="field">
            <label class="label" for="feed_link_url">Link URL</label>
            <input
              class="input"
              id="feed_link_url"
              type="text"
              bind:value={linkUrl}
              placeholder="/internal-path or https://…"
            >
            <p class="help">Internal links can start with a forward-slash. External links must begin with <em>https://</em></p>
          </div>

          <div class="field">
            <label class="label" for="feed_link_label">
              Label
              <span class="icon has-text-info tooltip is-size-7" data-tooltip="Descriptive link text">
                <i class="fas fa-info-circle"></i>
              </span>
            </label>
            <input
              class="input"
              id="feed_link_label"
              type="text"
              bind:value={linkLabel}
              placeholder='e.g. "Learn More" or "View the Full Story"'
            >
          </div>
        </div>
      {/if}
    </div>
  </div>

  <!-- Right: template selector + live preview -->
  <div class="column">
    <div class="columns is-mobile mb-3">
      <div class="column">
        <div class="field">
          <label class="label" for="feed_template">Template</label>
          <div class="select is-fullwidth">
            <select
              id="feed_template"
              bind:value={template}
              on:change={onTemplateChange}
            >
              <option value="default">Default</option>
              <option value="profile">Profile</option>
              <option value="cta">Call to Action</option>
              <option value="highlight">Quick Highlight</option>
            </select>
          </div>
        </div>
      </div>
      <div class="column">
        <div class="field">
          <label class="label" for="feed_display_date">
            Release Date
            <span class="icon has-text-info tooltip is-size-7" data-tooltip="Article won't appear until this date">
              <i class="fas fa-info-circle"></i>
            </span>
          </label>
          <input class="input" id="feed_display_date" type="text" bind:value={displayDate} placeholder="YYYY-MM-DD HH:MM:SS">
        </div>
      </div>
      <div class="column">
        <div class="field">
          <label class="label" for="feed_end_date">
            Archive Date
            <span class="icon has-text-info tooltip is-size-7" data-tooltip="A past date removes this article from the feed">
              <i class="fas fa-info-circle"></i>
            </span>
          </label>
          <input class="input" id="feed_end_date" type="text" bind:value={endDate} placeholder="YYYY-MM-DD HH:MM:SS">
        </div>
      </div>
    </div>

    <!-- Live preview -->
    <div class="content">
      <!-- Default template -->
      {#if activeTemplate === 'default'}
        <p class="help">Default Template — General posts. Dateline, image, and link are optional.</p>
        <div class="card feed-preview-card">
          <div class="card-content">
            {#if headline}<h2>{headline}</h2>{/if}
            {#if dateline}<p class="has-text-weight-bold">{dateline}</p>{/if}
            {#if body}{@html renderedBody}{/if}
            {#if linkUrl}
              <a href={linkUrl} target={linkTarget}>{linkLabel || linkUrl}</a>
            {/if}
            {#if imageSrc}<img src={imageSrc} alt={imageAlt} class="is-fullwidth">{/if}
          </div>
        </div>
      {/if}

      <!-- Profile template -->
      {#if activeTemplate === 'profile'}
        <p class="help">Profile Template — Image floated right, no dateline, link is optional.</p>
        <div class="card feed-preview-card">
          <div class="card-content">
            {#if headline}<h2>{headline}</h2>{/if}
            {#if imageSrc}
              <img src={imageSrc} alt={imageAlt}
                style="float: right; padding: 0 0 10px 10px; max-width: 45%; width: 100%; border-radius: 0 20%;">
            {/if}
            {#if body}{@html renderedBody}{/if}
            {#if linkUrl}<a href={linkUrl} target={linkTarget}>{linkLabel || linkUrl}</a>{/if}
          </div>
        </div>
      {/if}

      <!-- CTA template -->
      {#if activeTemplate === 'cta'}
        <p class="help">CTA Template — Bold header, link becomes a button. Dateline and image optional.</p>
        <div class="card feed-preview-card">
          <div class="card-header cta-card-header">
            <h2 class="card-header-title has-text-white">{headline}</h2>
          </div>
          <div class="card-content">
            {#if dateline}<p class="has-text-weight-bold">{dateline}</p>{/if}
            {#if body}{@html renderedBody}{/if}
            {#if linkUrl}
              <a class="button is-primary" href={linkUrl} target={linkTarget}>{linkLabel || linkUrl}</a>
            {/if}
            {#if imageSrc}<img src={imageSrc} alt={imageAlt} class="is-fullwidth">{/if}
          </div>
        </div>
      {/if}

      <!-- Highlight template -->
      {#if activeTemplate === 'highlight'}
        <p class="help">Quick Highlight — Eye-catching header, no dateline or link. Image optional.</p>
        <div class="card feed-preview-card">
          <div class="card-header highlight-card-header">
            <h2 class="card-header-title has-text-white">{headline}</h2>
          </div>
          <div class="card-content">
            {#if body}{@html renderedBody}{/if}
            {#if imageSrc}<img src={imageSrc} alt={imageAlt} class="is-fullwidth">{/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>

<!-- ── Media Manager Modal ── -->
{#if mediaManagerOpen}
  <div class="modal is-active">
    <div class="modal-background" on:click={closeMediaManager} role="none"></div>
    <div class="modal-card preview-modal-card">
      <header class="modal-card-head">
        <p class="modal-card-title">Media Manager</p>
        <button class="delete" aria-label="close" on:click={closeMediaManager}></button>
      </header>
      <section class="modal-card-body preview-modal-body">
        <iframe
          src="{appPath}admin/media?iframe=true"
          title="Media Manager"
          class="preview-iframe"
        ></iframe>
      </section>
    </div>
  </div>
{/if}

{/if}

<style>
  .feed-textarea {
    height: 250px;
  }

  .collapse-trigger {
    cursor: pointer;
    user-select: none;
    padding-left: 0;
    justify-content: flex-start;
  }

  .collapse-trigger:hover {
    color: var(--bulma-link);
  }

  /* intentional: simulates preview appearance for content-designer-controlled card templates */
  .cta-card-header {
    background: #1a3a5c;
  }

  .highlight-card-header {
    background: #c0392b;
  }

  .preview-modal-card {
    width: 90%;
    max-width: 1200px;
  }

  .preview-modal-body {
    padding: 0;
    min-height: 500px;
  }

  .preview-iframe {
    display: block;
    width: 100%;
    height: 600px;
    border: none;
  }
</style>
