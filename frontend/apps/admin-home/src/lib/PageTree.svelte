<script lang="ts">
  import { onMount, createEventDispatcher } from 'svelte';
  import type { PageFlat, PageNode } from './types/adminHome';

  export let xhrPath: string;

  const dispatch = createEventDispatcher<{ select: number }>();

  interface FlatItem {
    id: number;
    label: string;
    active: boolean;
    level: number;
    hasChildren: boolean;
    parentId: number;
  }

  let pages: PageNode[] = [];
  let loading = true;
  let fetchError = '';
  let expanded = new Set<number>();

  function buildTree(flat: PageFlat[]): PageNode[] {
    const map = new Map<number, PageNode>();
    for (const p of flat) map.set(p.id, { ...p, depth: 0, children: [] });

    const roots: PageNode[] = [];
    for (const node of map.values()) {
      const parent = map.get(node.parentId);
      if (parent) parent.children.push(node);
      else roots.push(node);
    }

    function sort(items: PageNode[], depth: number) {
      items.sort((a, b) => a.displayOrder - b.displayOrder);
      for (const n of items) {
        n.depth = depth;
        sort(n.children, depth + 1);
      }
    }
    sort(roots, 0);
    return roots;
  }

  // Both `pages` and `expanded` are explicit parameters so Svelte tracks both as dependencies.
  function flattenTree(nodes: PageNode[], expandedSet: Set<number>, level = 0): FlatItem[] {
    const result: FlatItem[] = [];
    for (const node of nodes) {
      result.push({
        id: node.id,
        label: node.label,
        active: node.active,
        level,
        hasChildren: node.children.length > 0,
        parentId: node.parentId,
      });
      if (expandedSet.has(node.id)) {
        result.push(...flattenTree(node.children, expandedSet, level + 1));
      }
    }
    return result;
  }

  $: flatItems = flattenTree(pages, expanded);

  onMount(async () => {
    try {
      const res = await fetch(`${xhrPath}pages/content-list`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '',
      });
      const data = await res.json();
      if (Array.isArray(data)) {
        pages = buildTree(data as PageFlat[]);
        // Expand top-level nodes by default
        expanded = new Set(pages.map(p => p.id));
      } else {
        fetchError = (data as { error?: string }).error ?? 'Failed to load pages';
      }
    } catch {
      fetchError = 'Failed to load pages';
    } finally {
      loading = false;
    }
  });
</script>

{#if loading}
  <p class="has-text-grey is-size-7 py-2">Loading pages…</p>
{:else if fetchError}
  <div class="notification is-danger is-light is-size-7 p-2">{fetchError}</div>
{:else if flatItems.length === 0}
  <p class="has-text-grey is-size-7">No pages found.</p>
{:else}
  <div class="page-tree-list">
    {#each flatItems as item (item.id)}
      <div class="tree-row" style:padding-left="{item.level * 24 + 8}px">
        <!-- Expand/collapse toggle -->
        <button
          class="expand-btn"
          class:is-invisible={!item.hasChildren}
          on:click={() => {
            if (expanded.has(item.id)) {
              expanded.delete(item.id);
            } else {
              expanded.add(item.id);
            }
            expanded = expanded;
          }}
          aria-label={expanded.has(item.id) ? 'Collapse' : 'Expand'}
        >
          <i class="fas fa-chevron-{expanded.has(item.id) ? 'down' : 'right'} fa-xs"></i>
        </button>

        <!-- Page label — click to open block picker -->
        <button
          class="page-label-btn"
          title="View content blocks for this page"
          on:click={() => dispatch('select', item.id)}
        >
          {item.label}
        </button>

        {#if !item.active}
          <span class="tag is-warning is-light" style="font-size:0.65rem">Inactive</span>
        {/if}
      </div>
    {/each}
  </div>
{/if}

<style>
  .page-tree-list {
    display: flex;
    flex-direction: column;
  }

  .tree-row {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
    padding-right: 0.5rem;
    border-radius: 5px;
    transition: background 0.1s;
  }

  .tree-row:hover {
    background: #f5f5f5;
  }

  .expand-btn {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    cursor: pointer;
    color: #888;
    border-radius: 3px;
    padding: 0;
    transition: background 0.1s, color 0.1s;
  }

  .expand-btn:hover {
    background: #e8e8e8;
    color: #333;
  }

  .expand-btn.is-invisible {
    visibility: hidden;
    pointer-events: none;
  }

  .page-label-btn {
    flex: 1;
    min-width: 0;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.15rem 0.4rem;
    border-radius: 3px;
    text-align: left;
    font-size: 0.9rem;
    font-weight: 500;
    color: #363636;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background-color 0.15s, color 0.15s;
  }

  .page-label-btn:hover {
    background-color: #00d1b2;
    color: #fff;
  }
</style>
