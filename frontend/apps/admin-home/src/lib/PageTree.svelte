<script lang="ts">
  import { onMount, createEventDispatcher } from 'svelte';
  import type { PageFlat, PageNode } from './types/adminHome';

  export let xhrPath: string;

  const dispatch = createEventDispatcher<{ select: number }>();

  let nodes: PageNode[] = [];
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

  // Flatten tree for rendering — respects expanded state
  function flattenVisible(items: PageNode[]): PageNode[] {
    const result: PageNode[] = [];
    for (const node of items) {
      result.push(node);
      if (expanded.has(node.id) && node.children.length > 0) {
        result.push(...flattenVisible(node.children));
      }
    }
    return result;
  }

  $: visible = flattenVisible(nodes);

  onMount(async () => {
    try {
      const res = await fetch(`${xhrPath}pages/content-list`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '',
      });
      const data = await res.json();
      if (Array.isArray(data)) {
        nodes = buildTree(data as PageFlat[]);
        // Expand top-level nodes by default
        for (const n of nodes) expanded.add(n.id);
        expanded = new Set(expanded);
      } else {
        fetchError = (data as { error?: string }).error ?? 'Failed to load pages';
      }
    } catch {
      fetchError = 'Failed to load pages';
    } finally {
      loading = false;
    }
  });

  function toggle(id: number) {
    if (expanded.has(id)) expanded.delete(id);
    else expanded.add(id);
    expanded = new Set(expanded);
  }
</script>

{#if loading}
  <p class="has-text-grey is-size-7 py-2">Loading pages…</p>
{:else if fetchError}
  <div class="notification is-danger is-light is-size-7 p-2">{fetchError}</div>
{:else if visible.length === 0}
  <p class="has-text-grey is-size-7">No pages found.</p>
{:else}
  <ul class="menu-list">
    {#each visible as node (node.id)}
      <li style="padding-left: {node.depth * 1.1}rem">
        <div class="page-tree-item">
          {#if node.children.length > 0}
            <button
              class="toggle"
              aria-label={expanded.has(node.id) ? 'Collapse' : 'Expand'}
              on:click={() => toggle(node.id)}
            >
              <i class="fas fa-caret-{expanded.has(node.id) ? 'down' : 'right'}"></i>
            </button>
          {:else}
            <span style="width:1.2rem;flex-shrink:0"></span>
          {/if}

          <button
            class="page-label"
            title="Click to view content blocks for this page"
            on:click={() => dispatch('select', node.id)}
          >
            {node.label}
          </button>

          {#if !node.active}
            <span class="inactive-badge">inactive</span>
          {/if}
        </div>
      </li>
    {/each}
  </ul>
{/if}
