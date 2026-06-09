<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import type { Template, Role } from './types';
  import { quickNotice } from '@crud-shared/crudUtils';

  export let pageId: number;
  export let xhrPath: string;
  export let templates: Template[];
  export let roles: Role[];
  export let isDeveloperOrDesigner: boolean;

  const dispatch = createEventDispatcher<{
    save: { id: number; label: string; slug: string; active: boolean; displayInSitemap: boolean };
    close: void;
  }>();

  let loading = true;
  let saving = false;
  let error = '';

  // Form fields
  let label = '';
  let slug = '';
  let originalSlug = '';
  let slugManuallyEdited = false;
  let templateId = 1;
  let active = true;
  let displayInSitemap = true;
  let requiredRole = 0;

  async function loadProperties() {
    loading = true;
    error = '';
    try {
      const body = new URLSearchParams({ page_id: String(pageId) });
      const res = await fetch(`${xhrPath}pages/get-properties`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const data = await res.json();
      if (data.success) {
        label = data.label;
        slug = data.slug;
        originalSlug = data.slug;
        slugManuallyEdited = false;
        templateId = data.template_id;
        active = data.active;
        displayInSitemap = data.display_in_sitemap;
        requiredRole = data.required_role;
      } else {
        error = data.error ?? 'Failed to load page properties';
      }
    } catch {
      error = 'An error occurred loading page properties';
    } finally {
      loading = false;
    }
  }

  onMount(loadProperties);

  function scrubSlug(val: string): string {
    return (` ${val} `)
      .replace(/[^a-zA-Z0-9 -]/g, '')
      .replace(/\sthe\b/gi, '-')
      .replace(/\sand\b/gi, '-')
      .replace(/\sof\b/gi, '-')
      .replace(/\sfor\b/gi, '-')
      .replace(/\son\b/gi, '-')
      .replace(/\s+/gi, '-')
      .replace(/-+/gi, '-')
      .slice(1, -1)
      .toLowerCase();
  }

  function handleLabelInput() {
    if (!slugManuallyEdited) {
      slug = scrubSlug(label);
    }
  }

  function handleSlugInput() {
    slugManuallyEdited = slug !== '' && slug !== scrubSlug(label);
  }

  function resetSlug() {
    slug = originalSlug;
    slugManuallyEdited = false;
  }

  $: slugChanged = slug !== originalSlug && originalSlug !== '';

  async function handleSave() {
    saving = true;
    error = '';
    try {
      const body = new URLSearchParams({
        page_id: String(pageId),
        label,
        slug,
        template_id: String(templateId),
        active: active ? '1' : '0',
        display_in_sitemap: displayInSitemap ? '1' : '0',
        required_role: String(requiredRole),
      });
      const res = await fetch(`${xhrPath}pages/set-properties`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      });
      const data = await res.json();
      if (data.success) {
        quickNotice('Page properties saved');
        dispatch('save', { id: pageId, label, slug, active, displayInSitemap });
      } else {
        error = data.error ?? 'Failed to save page properties';
      }
    } catch {
      error = 'An error occurred saving page properties';
    } finally {
      saving = false;
    }
  }

  function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') dispatch('close');
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<div class="modal is-active" role="dialog" aria-modal="true" aria-label="Edit Page Properties">
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-background" on:click={() => dispatch('close')}></div>
  <div class="modal-card">

    <header class="modal-card-head">
      <p class="modal-card-title">Edit Page Properties</p>
      <button class="delete" aria-label="close" on:click={() => dispatch('close')}></button>
    </header>

    <section class="modal-card-body">
      {#if loading}
        <div class="has-text-centered py-5">
          <span class="icon is-large">
            <i class="fas fa-spinner fa-spin fa-2x has-text-grey-light"></i>
          </span>
        </div>

      {:else if error}
        <div class="notification is-danger is-light">{error}</div>

      {:else}
        <!-- Nav label -->
        <div class="field">
          <label class="label" for="pm-label">Nav Label</label>
          <div class="control">
            <input
              class="input"
              type="text"
              id="pm-label"
              bind:value={label}
              on:input={handleLabelInput}
              placeholder="e.g. About Us"
            />
          </div>
          <p class="help">The text shown in navigation menus.</p>
        </div>

        <!-- URL slug -->
        <div class="field">
          <label class="label" for="pm-slug">
            URL Slug
            {#if slugChanged}
              <button
                class="button is-ghost is-small has-text-info py-0 px-2"
                type="button"
                title="Reset to original value"
                on:click={resetSlug}
              >
                <span class="icon is-small"><i class="fas fa-undo fa-xs"></i></span>
                <span class="is-size-7">reset</span>
              </button>
            {/if}
          </label>
          <div class="control">
            <input
              class="input"
              class:is-warning={slugChanged}
              type="text"
              id="pm-slug"
              bind:value={slug}
              on:input={handleSlugInput}
              placeholder="about-us"
            />
          </div>
          {#if slugChanged}
            <p class="help is-warning">
              Changing the slug will break existing links to this page.
            </p>
          {:else}
            <p class="help">Lowercase letters, numbers, and hyphens only.</p>
          {/if}
        </div>

        <!-- Layout -->
        <div class="field">
          <label class="label" for="pm-template">Layout</label>
          <div class="control">
            <div class="select">
              <select id="pm-template" bind:value={templateId}>
                {#each templates as tmpl (tmpl.id)}
                  {#if tmpl.available || isDeveloperOrDesigner}
                    <option value={tmpl.id}>{tmpl.name}</option>
                  {:else}
                    <option value={tmpl.id} disabled>{tmpl.name} (locked)</option>
                  {/if}
                {/each}
              </select>
            </div>
          </div>
        </div>

        <!-- Active & Navigitable side by side -->
        <div class="columns">
          <div class="column">
            <div class="field">
              <span class="label">Visibility</span>
              <label class="checkbox">
                <input type="checkbox" bind:checked={active} />
                Active — page is publicly accessible
              </label>
              <p class="help">Uncheck to make this page return a 404 error.</p>
            </div>
          </div>
          <div class="column">
            <div class="field">
              <span class="label">Navigation</span>
              <label class="checkbox">
                <input type="checkbox" bind:checked={displayInSitemap} />
                Show in menus &amp; sitemap
              </label>
              <p class="help">Uncheck to hide from auto-generated menus.</p>
            </div>
          </div>
        </div>

        <!-- Access role -->
        <div class="field">
          <label class="label" for="pm-role">Required Login</label>
          <div class="control">
            <div class="select">
              <select id="pm-role" bind:value={requiredRole}>
                <option value={0}>Public — no login required</option>
                {#each roles as role (role.id)}
                  <option value={role.id}>{role.name}</option>
                {/each}
              </select>
            </div>
          </div>
          <p class="help">Users must be logged in with this role to access the page.</p>
        </div>
      {/if}
    </section>

    <footer class="modal-card-foot">
      <button
        class="button is-primary"
        disabled={saving || loading || !!error}
        on:click={handleSave}
      >
        {#if saving}
          <span class="icon"><i class="fas fa-spinner fa-spin"></i></span>
        {:else}
          <span class="icon"><i class="fas fa-save"></i></span>
        {/if}
        <span>Save Changes</span>
      </button>
      <button class="button" on:click={() => dispatch('close')}>Cancel</button>
    </footer>

  </div>
</div>
