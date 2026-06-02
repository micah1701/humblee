<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let title: string;
  export let subtitle: string = '';
  export let items: Array<{ id: number; name: string }> = [];
  export let selectedId: number | null = null;
  export let loading: boolean = false;
  export let saving: boolean = false;
  export let errors: string[] = [];
  export let saveLabel: string = 'Save';
  export let newLabel: string = 'New';

  const dispatch = createEventDispatcher<{
    select: number | null;
    'new': void;
    save: void;
    delete: void;
  }>();

  let searchQuery = '';

  $: filtered = items.filter(i =>
    i.name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  $: isNew = selectedId === null;
</script>

<div class="crud-shell">
  <div class="crud-header">
    <div>
      <h2 class="title is-4 mb-1">{title}</h2>
      {#if subtitle}
        <p class="subtitle is-6 has-text-grey">{subtitle}</p>
      {/if}
    </div>
  </div>

  <div class="crud-body columns is-gapless">
    <!-- Left: item list -->
    <div class="column is-narrow crud-sidebar">
      <div class="crud-sidebar-inner">
        <button
          class="button is-primary is-fullwidth is-small mb-3"
          on:click={() => dispatch('new')}
          disabled={saving}
        >
          <span class="icon"><i class="fas fa-plus"></i></span>
          <span>{newLabel}</span>
        </button>

        <div class="control has-icons-left mb-3">
          <input
            class="input is-small"
            type="text"
            placeholder="Filter…"
            bind:value={searchQuery}
          />
          <span class="icon is-small is-left">
            <i class="fas fa-search"></i>
          </span>
        </div>

        {#if loading}
          <div class="has-text-centered has-text-grey-light py-4">
            <span class="icon is-medium"><i class="fas fa-spinner fa-spin"></i></span>
          </div>
        {:else if filtered.length === 0}
          <p class="has-text-grey-light is-size-7 has-text-centered py-4">
            {searchQuery ? 'No matches' : 'No items yet'}
          </p>
        {:else}
          <ul class="crud-list">
            {#each filtered as item (item.id)}
              <li>
                <button
                  class="crud-list-item"
                  class:is-active={item.id === selectedId}
                  on:click={() => dispatch('select', item.id)}
                >
                  {item.name}
                </button>
              </li>
            {/each}
          </ul>
        {/if}
      </div>
    </div>

    <!-- Right: form area -->
    <div class="column crud-form-area">
      <div class="crud-form-inner">
        {#if saving}
          <div class="crud-saving-overlay">
            <span class="icon is-large"><i class="fas fa-spinner fa-spin fa-2x"></i></span>
          </div>
        {/if}

        <div class="crud-form-header mb-4">
          <h3 class="title is-5 mb-0">
            {#if isNew}
              <span class="tag is-info is-light mr-2">New</span>{title.replace(/s$/, '')}
            {:else}
              Edit {title.replace(/s$/, '')}
            {/if}
          </h3>
        </div>

        <!-- Form slot -->
        <slot />

        <!-- Error list -->
        {#if errors.length > 0}
          <div class="notification is-danger is-light mt-4 mb-0">
            <ul>
              {#each errors as err}
                <li>{err}</li>
              {/each}
            </ul>
          </div>
        {/if}

        <!-- Action buttons -->
        <div class="crud-actions mt-4">
          <button
            class="button is-primary"
            on:click={() => dispatch('save')}
            disabled={saving}
          >
            <span class="icon"><i class="fas fa-save"></i></span>
            <span>{saveLabel}</span>
          </button>

          {#if !isNew}
            <button
              class="button is-danger is-light"
              on:click={() => dispatch('delete')}
              disabled={saving}
            >
              <span class="icon"><i class="fas fa-trash"></i></span>
              <span>Delete</span>
            </button>
          {/if}
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .crud-shell {
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .crud-header {
    padding: 1.25rem 1.5rem 1rem;
    border-bottom: 1px solid #e8e8e8;
  }

  .crud-body {
    flex: 1;
    min-height: 0;
  }

  .crud-sidebar {
    width: 280px;
    border-right: 1px solid #e8e8e8;
    background: #fafafa;
  }

  .crud-sidebar-inner {
    padding: 1rem;
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .crud-list {
    list-style: none;
    margin: 0;
    padding: 0;
    overflow-y: auto;
    flex: 1;
  }

  .crud-list-item {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.55rem 0.75rem;
    border-radius: 6px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 0.875rem;
    color: #363636;
    transition: background 0.1s, color 0.1s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .crud-list-item:hover {
    background: #efefef;
  }

  .crud-list-item.is-active {
    background: #3273dc;
    color: #fff;
    font-weight: 500;
  }

  .crud-form-area {
    position: relative;
  }

  .crud-form-inner {
    padding: 1.5rem;
    position: relative;
  }

  .crud-saving-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 4px;
  }

  .crud-form-header {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 0.75rem;
  }

  .crud-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    border-top: 1px solid #f0f0f0;
    padding-top: 1rem;
  }

  .crud-actions .button:last-child {
    margin-left: auto;
  }

  @media (max-width: 768px) {
    .crud-body {
      flex-direction: column;
    }
    .crud-sidebar {
      width: 100%;
      border-right: none;
      border-bottom: 1px solid #e8e8e8;
    }
    .crud-sidebar-inner {
      max-height: 220px;
    }
  }
</style>
