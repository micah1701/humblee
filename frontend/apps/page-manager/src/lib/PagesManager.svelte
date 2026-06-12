<script lang="ts">
  import type { PageFlat, PageNode, FlatItem, Template, Role } from './types';
  import { quickNotice, confirmation } from '@crud-shared/crudUtils';
  import PagePropertiesModal from './PagePropertiesModal.svelte';

  export let xhrPath: string;
  export let templates: Template[];
  export let roles: Role[];
  export let isDeveloperOrDesigner: boolean;

  // ── State ──────────────────────────────────────────────────────────────
  let pages: PageNode[] = [];
  let loading = true;
  let saving = false;
  let expanded = new Set<number>();
  let editPageId: number | null = null;

  // Drag-and-drop state
  let draggingId: number | null = null;
  let dropTarget: { id: number; pos: 'before' | 'after' | 'inside' } | null = null;

  // ── HTTP helpers ───────────────────────────────────────────────────────
  async function apiGet(endpoint: string): Promise<unknown> {
    const res = await fetch(`${xhrPath}${endpoint}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  async function apiPost(endpoint: string, data: Record<string, string | number>): Promise<Record<string, unknown>> {
    const body = new URLSearchParams();
    for (const [k, v] of Object.entries(data)) body.append(k, String(v));
    const res = await fetch(`${xhrPath}${endpoint}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json() as Promise<Record<string, unknown>>;
  }

  // ── Tree utilities ─────────────────────────────────────────────────────
  function buildTree(flat: PageFlat[]): PageNode[] {
    const map = new Map<number, PageNode>();
    for (const p of flat) map.set(p.id, { ...p, children: [] });

    const roots: PageNode[] = [];
    for (const node of map.values()) {
      const parent = map.get(node.parentId);
      if (parent) parent.children.push(node);
      else roots.push(node);
    }

    function sort(nodes: PageNode[]) {
      nodes.sort((a, b) => a.displayOrder - b.displayOrder);
      for (const n of nodes) sort(n.children);
    }
    sort(roots);
    return roots;
  }

  function findNode(nodes: PageNode[], id: number): PageNode | null {
    for (const n of nodes) {
      if (n.id === id) return n;
      const found = findNode(n.children, id);
      if (found) return found;
    }
    return null;
  }

  function isDescendantOrSelf(node: PageNode, targetId: number): boolean {
    if (node.id === targetId) return true;
    return node.children.some(c => isDescendantOrSelf(c, targetId));
  }

  function removeNode(nodes: PageNode[], id: number): [PageNode[], PageNode | null] {
    for (let i = 0; i < nodes.length; i++) {
      if (nodes[i].id === id) {
        const removed = nodes[i];
        return [[...nodes.slice(0, i), ...nodes.slice(i + 1)], removed];
      }
      const [newChildren, found] = removeNode(nodes[i].children, id);
      if (found) {
        const updated = { ...nodes[i], children: newChildren };
        return [[...nodes.slice(0, i), updated, ...nodes.slice(i + 1)], found];
      }
    }
    return [nodes, null];
  }

  function insertNode(
    nodes: PageNode[],
    nodeToInsert: PageNode,
    targetId: number,
    pos: 'before' | 'after' | 'inside'
  ): PageNode[] {
    const result: PageNode[] = [];
    for (let i = 0; i < nodes.length; i++) {
      const node = nodes[i];
      if (node.id === targetId) {
        if (pos === 'before') {
          result.push(nodeToInsert, node);
        } else if (pos === 'after') {
          result.push(node, nodeToInsert);
        } else {
          // auto-expand target when nesting inside
          expanded = new Set([...expanded, node.id]);
          result.push({ ...node, children: [nodeToInsert, ...node.children] });
        }
      } else {
        result.push({ ...node, children: insertNode(node.children, nodeToInsert, targetId, pos) });
      }
    }
    return result;
  }

  interface OrderEntry { id: number; parentId: number; displayOrder: number; }

  function serializeOrder(nodes: PageNode[], parentId = 0): OrderEntry[] {
    return nodes.flatMap((node, i) => [
      { id: node.id, parentId, displayOrder: i },
      ...serializeOrder(node.children, node.id),
    ]);
  }

  // ── Flat display list ──────────────────────────────────────────────────
  function flattenTree(nodes: PageNode[], expandedSet: Set<number>, level = 0): FlatItem[] {
    const result: FlatItem[] = [];
    for (const node of nodes) {
      result.push({
        id: node.id,
        label: node.label,
        slug: node.slug,
        active: node.active,
        displayInSitemap: node.displayInSitemap,
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

  // Both `pages` and `expanded` are listed explicitly so Svelte tracks both as dependencies.
  $: flatItems = flattenTree(pages, expanded);

  // ── Data loading ───────────────────────────────────────────────────────
  async function loadPages() {
    loading = true;
    try {
      const data = await apiGet('pages/list') as PageFlat[];
      pages = buildTree(data);
      // Expand top level by default
      expanded = new Set(pages.map(p => p.id));
    } catch {
      quickNotice('Failed to load pages', 'is-danger');
    } finally {
      loading = false;
    }
  }

  loadPages();

  // ── Actions ────────────────────────────────────────────────────────────
  async function addPage(parentId: number) {
    saving = true;
    try {
      const res = await apiPost('pages/add', { parent_id: parentId });
      if (res.success) {
        await loadPages();
        if (parentId !== 0) expanded = new Set([...expanded, parentId]);
        editPageId = res.page_id as number;
      } else {
        quickNotice((res.error as string) ?? 'Failed to add page', 'is-danger');
      }
    } catch {
      quickNotice('An error occurred', 'is-danger');
    } finally {
      saving = false;
    }
  }

  function deletePage(id: number, label: string) {
    confirmation(
      `Delete <strong>${label}</strong>?<br><br>All content for this page will be permanently lost and cannot be undone.`,
      async () => {
        saving = true;
        try {
          const res = await apiPost('pages/delete', { page_id: id });
          if (res.success) {
            quickNotice(`"${label}" deleted`, 'is-warning');
            await loadPages();
          } else {
            quickNotice((res.error as string) ?? 'Failed to delete page', 'is-danger');
          }
        } catch {
          quickNotice('An error occurred', 'is-danger');
        } finally {
          saving = false;
        }
      },
      () => {}
    );
  }

  // ── Edit save callback ─────────────────────────────────────────────────
  function handleEditSaved(e: CustomEvent<{ id: number; label: string; slug: string; active: boolean; displayInSitemap: boolean }>) {
    const { id, label, slug, active, displayInSitemap } = e.detail;
    function patch(nodes: PageNode[]): PageNode[] {
      return nodes.map(n =>
        n.id === id
          ? { ...n, label, slug, active, displayInSitemap }
          : { ...n, children: patch(n.children) }
      );
    }
    pages = patch(pages);
    editPageId = null;
  }

  // ── Drag-and-drop ──────────────────────────────────────────────────────
  function handleDragStart(e: DragEvent, id: number) {
    draggingId = id;
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
  }

  function handleDragOver(e: DragEvent, id: number) {
    e.preventDefault();
    if (!draggingId || draggingId === id) return;

    const draggingNode = findNode(pages, draggingId);
    if (draggingNode && isDescendantOrSelf(draggingNode, id)) return;

    const el = e.currentTarget as HTMLElement;
    const rect = el.getBoundingClientRect();
    const relY = (e.clientY - rect.top) / rect.height;

    let pos: 'before' | 'after' | 'inside';
    if (relY < 0.28) pos = 'before';
    else if (relY > 0.72) pos = 'after';
    else pos = 'inside';

    dropTarget = { id, pos };
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
  }

  function handleDragLeave(e: DragEvent) {
    const related = e.relatedTarget as Node | null;
    if (related && (e.currentTarget as HTMLElement).contains(related)) return;
    dropTarget = null;
  }

  function handleDragEnd() {
    draggingId = null;
    dropTarget = null;
  }

  async function handleDrop(e: DragEvent, id: number) {
    e.preventDefault();
    if (!draggingId || !dropTarget || dropTarget.id !== id) {
      draggingId = null;
      dropTarget = null;
      return;
    }

    const draggingNode = findNode(pages, draggingId);
    if (!draggingNode || isDescendantOrSelf(draggingNode, id)) {
      draggingId = null;
      dropTarget = null;
      return;
    }

    const capturedTarget = { ...dropTarget };
    const [withoutNode, removed] = removeNode(pages, draggingId);
    if (!removed) { draggingId = null; dropTarget = null; return; }

    pages = insertNode(withoutNode, removed, capturedTarget.id, capturedTarget.pos);
    draggingId = null;
    dropTarget = null;

    saving = true;
    try {
      const res = await apiPost('pages/order', { list_order: JSON.stringify(serializeOrder(pages)) });
      if (res.success) {
        quickNotice('Page order saved');
      } else {
        quickNotice('Failed to save order — reloading', 'is-danger');
        await loadPages();
      }
    } catch {
      quickNotice('An error occurred — reloading', 'is-danger');
      await loadPages();
    } finally {
      saving = false;
    }
  }

  // ── Expand/collapse all ────────────────────────────────────────────────
  function getAllIds(nodes: PageNode[]): number[] {
    return nodes.flatMap(n => [n.id, ...getAllIds(n.children)]);
  }

  function expandAll() { expanded = new Set(getAllIds(pages)); }
  function collapseAll() { expanded = new Set(); }

  $: hasExpanded = expanded.size > 0;
  $: hasCollapsed = flatItems.some(i => i.hasChildren && !expanded.has(i.id));
</script>

<div class="page-manager box p-0">
  <!-- Header -->
  <div class="page-manager-header px-5 py-4">
    <div>
      <h2 class="title is-4 mb-1">Site Pages</h2>
      <p class="subtitle is-6 has-text-grey">
        Manage the site's page structure. Drag rows to reorder or nest pages.
      </p>
    </div>
    <div class="header-actions">
      <button
        class="button is-primary"
        disabled={saving || loading}
        on:click={() => addPage(0)}
        title="Create a new top-level page"
      >
        <span class="icon"><i class="fas fa-plus"></i></span>
        <span>New Page</span>
      </button>
    </div>
  </div>

  <!-- Tree toolbar -->
  {#if !loading && flatItems.length > 0}
    <div class="tree-toolbar px-4 py-2">
      {#if hasCollapsed}
        <button class="button is-ghost is-small has-text-grey" on:click={expandAll}>
          <span class="icon is-small"><i class="fas fa-chevron-down"></i></span>
          <span>Expand all</span>
        </button>
      {/if}
      {#if hasExpanded}
        <button class="button is-ghost is-small has-text-grey" on:click={collapseAll}>
          <span class="icon is-small"><i class="fas fa-chevron-right"></i></span>
          <span>Collapse all</span>
        </button>
      {/if}
    </div>
  {/if}

  <!-- Tree container -->
  <div class="page-tree-box px-4 py-3" class:is-saving={saving}>
    {#if loading}
      <div class="tree-loading">
        <span class="icon is-large has-text-grey-light">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
        </span>
      </div>
    {:else if flatItems.length === 0}
      <div class="tree-empty">
        <p class="has-text-grey mb-3">No pages yet.</p>
        <button class="button is-primary is-small" on:click={() => addPage(0)}>
          <span class="icon"><i class="fas fa-plus"></i></span>
          <span>Create your first page</span>
        </button>
      </div>
    {:else}
      <div class="tree-list" role="list">
        {#each flatItems as item (item.id)}
          <!-- Drop indicator: before -->
          {#if dropTarget?.id === item.id && dropTarget.pos === 'before'}
            <div class="drop-line" style:margin-left="{item.level * 24 + 36}px"></div>
          {/if}

          <!-- Page row -->
          <div
            class="tree-row"
            class:is-dragging={draggingId === item.id}
            class:drop-inside={dropTarget?.id === item.id && dropTarget.pos === 'inside'}
            style:padding-left="{item.level * 24 + 8}px"
            role="listitem"
            draggable="true"
            on:dragstart={e => handleDragStart(e, item.id)}
            on:dragover={e => handleDragOver(e, item.id)}
            on:dragleave={handleDragLeave}
            on:dragend={handleDragEnd}
            on:drop={e => handleDrop(e, item.id)}
          >
            <!-- Drag handle -->
            <span class="drag-handle" title="Drag to reorder">
              <i class="fas fa-grip-vertical"></i>
            </span>

            <!-- Expand/collapse -->
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

            <!-- Page name + slug -->
            <span class="page-info">
              <span class="page-label has-text-weight-medium">{item.label}</span>
              {#if item.slug}
                <span class="page-slug is-size-7 has-text-grey">/{item.slug}</span>
              {/if}
            </span>

            <!-- Status badges -->
            <span class="page-badges">
              {#if !item.active}
                <span class="tag is-warning is-light">Inactive</span>
              {/if}
              {#if !item.displayInSitemap}
                <span class="tag is-info is-light">Hidden</span>
              {/if}
            </span>

            <!-- Actions -->
            <span class="page-actions">
              <button
                class="button is-small is-white tooltip is-tooltip-top edit-page-btn"
                data-tooltip="Edit page properties"
                disabled={saving}
                on:click|stopPropagation={() => { editPageId = item.id; }}
              >
                <span class="icon is-small"><i class="fas fa-edit"></i></span>
                <span>Edit</span>
              </button>
              <button
                class="button is-small is-white tooltip is-tooltip-top"
                data-tooltip="Add sub-page under {item.label}"
                disabled={saving}
                on:click|stopPropagation={() => addPage(item.id)}
              >
                <span class="icon is-small"><i class="fas fa-plus"></i></span>
              </button>
              <button
                class="button is-small is-white tooltip is-tooltip-top delete-page-btn"
                data-tooltip="Delete this page and all its content"
                disabled={saving}
                on:click|stopPropagation={() => deletePage(item.id, item.label)}
              >
                <span class="icon is-small"><i class="fas fa-trash"></i></span>
              </button>
            </span>
          </div>

          <!-- Drop indicator: after -->
          {#if dropTarget?.id === item.id && dropTarget.pos === 'after'}
            <div class="drop-line" style:margin-left="{item.level * 24 + 36}px"></div>
          {/if}
        {/each}
      </div>
    {/if}
  </div>

  <!-- Legend -->
  {#if !loading && flatItems.length > 0}
    <p class="tree-hint has-text-grey-light is-size-7 px-4 pb-3">
      <span class="icon is-small"><i class="fas fa-grip-vertical"></i></span>
      Drag a row to reorder or reparent it.
      &ensp;
      <span class="tag is-warning is-light is-small">Inactive</span> = returns 404.
      &ensp;
      <span class="tag is-info is-light is-small">Hidden</span> = excluded from navigation.
    </p>
  {/if}
</div>

<!-- Edit modal -->
{#if editPageId !== null}
  <PagePropertiesModal
    pageId={editPageId}
    {xhrPath}
    {templates}
    {roles}
    {isDeveloperOrDesigner}
    on:save={handleEditSaved}
    on:close={() => { editPageId = null; }}
  />
{/if}

<style>
  .page-manager {
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow: hidden; /* clips inner content to box border-radius */
  }

  .page-manager-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid var(--bulma-border-weak);
  }

  .header-actions {
    flex-shrink: 0;
  }

  .tree-toolbar {
    display: flex;
    gap: 0.25rem;
    border-bottom: 1px solid var(--bulma-border-weak);
  }

  .page-tree-box {
    transition: opacity 0.15s;
  }

  .page-tree-box.is-saving {
    opacity: 0.65;
    pointer-events: none;
  }

  .tree-loading,
  .tree-empty {
    padding: 3rem 1rem;
    text-align: center;
  }

  .tree-list {
    display: flex;
    flex-direction: column;
  }

  /* ── Tree row ────────────────────────────────────────────────────── */
  .tree-row {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding-top: 0.3rem;
    padding-bottom: 0.3rem;
    padding-right: 0.5rem;
    border-radius: 6px;
    cursor: default;
    user-select: none;
    transition: background 0.1s;
  }

  .tree-row:hover {
    background: var(--bulma-scheme-main-ter);
  }

  .tree-row.is-dragging {
    opacity: 0.4;
  }

  .tree-row.drop-inside {
    background: var(--bulma-link-light);
    outline: 2px solid var(--bulma-link);
    outline-offset: -2px;
  }

  /* ── Drag handle ─────────────────────────────────────────────────── */
  .drag-handle {
    color: var(--bulma-border);
    cursor: grab;
    padding: 0 4px;
    font-size: 0.85rem;
    flex-shrink: 0;
  }

  .drag-handle:active {
    cursor: grabbing;
  }

  /* ── Expand button ───────────────────────────────────────────────── */
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
    color: var(--bulma-text-soft);
    border-radius: 3px;
    padding: 0;
    transition: background 0.1s, color 0.1s;
  }

  .expand-btn:hover {
    background: var(--bulma-scheme-main-ter);
    color: var(--bulma-text-strong);
  }

  .expand-btn.is-invisible {
    visibility: hidden;
    pointer-events: none;
  }

  /* ── Page info ───────────────────────────────────────────────────── */
  .page-info {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: baseline;
    gap: 0.4rem;
    overflow: hidden;
  }

  .page-label {
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .page-slug {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-shrink: 1;
  }

  /* ── Badges ──────────────────────────────────────────────────────── */
  .page-badges {
    display: flex;
    gap: 0.25rem;
    flex-shrink: 0;
  }

  .page-badges .tag {
    font-size: 0.65rem;
  }

  /* ── Action buttons ──────────────────────────────────────────────── */
  .page-actions {
    display: flex;
    gap: 0.15rem;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.1s;
  }

  .tree-row:hover .page-actions,
  .tree-row:focus-within .page-actions {
    opacity: 1;
  }

  .page-actions .button {
    width: 28px;
    height: 28px;
    padding: 0;
  }

  .page-actions .edit-page-btn {
    width: auto;
    padding: 0 10px;
    gap: 0.25rem;
  }

  .page-actions .delete-page-btn {
    color: var(--bulma-border);
  }

  .page-actions .delete-page-btn:hover,
  .page-actions .delete-page-btn:focus {
    color: var(--bulma-danger);
    background-color: var(--bulma-danger-soft);
  }

  /* ── Drop indicator line ─────────────────────────────────────────── */
  .drop-line {
    height: 2px;
    background: var(--bulma-link);
    border-radius: 1px;
    margin-right: 0.5rem;
    pointer-events: none;
  }

  /* ── Footer hint ─────────────────────────────────────────────────── */
  .tree-hint {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    flex-wrap: wrap;
  }
</style>
