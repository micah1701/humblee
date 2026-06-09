<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import RolesModal from './RolesModal.svelte';
  import { createUsersApi } from './services/usersApi';
  import type { User, Role, SortColumn, SortDirection } from './types/users';

  export let xhrPath: string;
  export let allRoles: Role[];
  export let isDeveloper: boolean;

  const api = createUsersApi(xhrPath);

  declare function quickNotice(msg: string, type?: string): void;
  declare function confirmation(msg: string, onConfirm: () => void): void;

  // ── Cache ──────────────────────────────────────────────────────────────────
  // Built by unfiltered page loads. Never touched by search/filter API calls.
  let cachedUsers: User[] = [];
  let totalCount = 0;              // unfiltered total from API
  $: cacheComplete = totalCount > 0 && cachedUsers.length >= totalCount;

  // ── Display ────────────────────────────────────────────────────────────────
  // What the table renders. Either a local slice of cachedUsers or an API result.
  let displayUsers: User[] = [];
  let displayTotal = 0;            // total for current view (drives infinite scroll gate)
  let inApiMode = false;           // true while showing API-fetched search results

  // ── UI state ───────────────────────────────────────────────────────────────
  let isLoading = false;
  let isLoadingMore = false;
  let searchQuery = '';
  let selectedRoleId = 0;
  let sortColumn: SortColumn = 'name';
  let sortDirection: SortDirection = 'asc';

  // Modal
  let modalUser: User | null = null;

  // Infinite scroll
  let sentinel: HTMLElement;
  let observer: IntersectionObserver;
  let debounceTimer: ReturnType<typeof setTimeout>;

  const LIMIT = 50;

  // ── Utilities ──────────────────────────────────────────────────────────────

  function timeAgo(dateStr: string): string {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return 'Never';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)       return `${diff}s ago`;
    if (diff < 3600)     return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)    return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800)   return `${Math.floor(diff / 86400)}d ago`;
    if (diff < 2592000)  return `${Math.floor(diff / 604800)}w ago`;
    if (diff < 31536000) return `${Math.floor(diff / 2592000)}mo ago`;
    return `${Math.floor(diff / 31536000)}y ago`;
  }

  function formatDate(dateStr: string): string {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return '';
    return new Date(dateStr).toLocaleString('en-US', {
      month: 'short', day: 'numeric', year: 'numeric',
      hour: 'numeric', minute: '2-digit',
    });
  }

  // ── Local filtering ────────────────────────────────────────────────────────
  // Always operates on cachedUsers. Instant — no API call.

  function filterAndSortLocally(): void {
    inApiMode = false;
    let result = [...cachedUsers];

    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      result = result.filter(u =>
        u.name.toLowerCase().includes(q) ||
        u.username.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q)
      );
    }

    if (selectedRoleId > 0) {
      result = result.filter(u => u.roles.some(r => r.id === selectedRoleId));
    }

    result.sort((a, b) => {
      const aVal = String(a[sortColumn as keyof User] ?? '');
      const bVal = String(b[sortColumn as keyof User] ?? '');
      const cmp  = aVal.localeCompare(bVal, undefined, { numeric: true });
      return sortDirection === 'asc' ? cmp : -cmp;
    });

    displayUsers = result;
    // When no filter is active and the cache is still growing, keep displayTotal
    // equal to totalCount so the infinite scroll sentinel can trigger more loads.
    const isFiltered = searchQuery.length > 0 || selectedRoleId > 0;
    displayTotal = isFiltered ? result.length : totalCount;
  }

  // ── API fetching ───────────────────────────────────────────────────────────
  // Used when the cache is incomplete and a precise filtered result is needed.

  async function loadFromApi(): Promise<void> {
    inApiMode = true;
    isLoading = true;
    displayUsers = [];
    displayTotal = 0;
    try {
      const data = await api.listUsers({
        search:    searchQuery.length >= 3 ? searchQuery : undefined,
        role_id:   selectedRoleId || undefined,
        offset:    0,
        limit:     LIMIT,
        sort:      sortColumn,
        direction: sortDirection,
      });
      displayUsers = data.users;
      displayTotal = data.total;
    } catch {
      quickNotice('Failed to load users.', 'danger');
    } finally {
      isLoading = false;
    }
  }

  // Initial / cache-extension load — populates cachedUsers, never filtered.
  async function loadCachePage(offset: number): Promise<void> {
    try {
      const data = await api.listUsers({ offset, limit: LIMIT, sort: 'name', direction: 'asc' });
      if (offset === 0) {
        cachedUsers = data.users;
      } else {
        cachedUsers = [...cachedUsers, ...data.users];
      }
      totalCount = data.total;
      filterAndSortLocally();
    } catch {
      quickNotice('Failed to load users.', 'danger');
    }
  }

  // ── Infinite scroll ────────────────────────────────────────────────────────

  async function loadMore(): Promise<void> {
    if (isLoadingMore || displayUsers.length >= displayTotal) return;
    isLoadingMore = true;
    try {
      if (inApiMode) {
        // Extend the API search result set
        const data = await api.listUsers({
          search:    searchQuery.length >= 3 ? searchQuery : undefined,
          role_id:   selectedRoleId || undefined,
          offset:    displayUsers.length,
          limit:     LIMIT,
          sort:      sortColumn,
          direction: sortDirection,
        });
        displayUsers = [...displayUsers, ...data.users];
        displayTotal = data.total;
      } else {
        // Extend the local cache with the next unfiltered page
        await loadCachePage(cachedUsers.length);
      }
    } catch {
      quickNotice('Failed to load more users.', 'danger');
    } finally {
      isLoadingMore = false;
    }
  }

  // ── Input handlers ─────────────────────────────────────────────────────────

  function onSearchInput(): void {
    clearTimeout(debounceTimer);

    if (cacheComplete || searchQuery.length < 3) {
      // Cache has everything (or search is too short to bother the API): filter locally.
      filterAndSortLocally();
      return;
    }

    // Cache is incomplete and the query is 3+ characters — call the API.
    debounceTimer = setTimeout(loadFromApi, 300);
  }

  function onRoleChange(): void {
    if (cacheComplete) {
      filterAndSortLocally();
    } else {
      loadFromApi();
    }
  }

  function setSort(col: SortColumn): void {
    if (sortColumn === col) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = col;
      sortDirection = 'asc';
    }

    if (cacheComplete) {
      filterAndSortLocally();
    } else if (inApiMode) {
      // Re-sort the active API search result
      loadFromApi();
    } else {
      // Sort what's in the cache locally (may be an incomplete set, but instant)
      filterAndSortLocally();
    }
  }

  // ── Mutations ──────────────────────────────────────────────────────────────

  function removeUser(user: User): void {
    confirmation(`Remove ${user.name}? This cannot be undone.`, async () => {
      const result = await api.removeUser(user.id);
      if (result.success) {
        cachedUsers  = cachedUsers.filter(u => u.id !== user.id);
        displayUsers = displayUsers.filter(u => u.id !== user.id);
        totalCount   = Math.max(0, totalCount - 1);
        displayTotal = Math.max(0, displayTotal - 1);
        quickNotice(`${user.name} has been removed.`);
      } else {
        quickNotice(result.error ?? 'Could not remove user.', 'danger');
      }
    });
  }

  async function saveRoles(userId: number, roleIds: number[]): Promise<void> {
    const result = await api.setRoles(userId, roleIds);
    if (result.success) {
      const roleMap  = new Map(allRoles.map(r => [r.id, r]));
      const newRoles = roleIds.map(id => roleMap.get(id)!).filter(Boolean);
      const patch    = (u: User) => u.id === userId ? { ...u, roles: newRoles } : u;
      cachedUsers  = cachedUsers.map(patch);
      displayUsers = displayUsers.map(patch);
      quickNotice('Roles updated.');
      modalUser = null;
    } else {
      quickNotice(result.error ?? 'Could not save roles.', 'danger');
    }
  }

  // ── Lifecycle ──────────────────────────────────────────────────────────────

  onMount(async () => {
    isLoading = true;
    await loadCachePage(0);
    isLoading = false;

    observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '200px' });

    if (sentinel) observer.observe(sentinel);
  });

  onDestroy(() => {
    observer?.disconnect();
    clearTimeout(debounceTimer);
  });

  // ── Helpers ────────────────────────────────────────────────────────────────

  const sortableColumns: Array<{ col: SortColumn; label: string }> = [
    { col: 'name',     label: 'Name' },
    { col: 'email',    label: 'E-Mail Address' },
    { col: 'username', label: 'Username' },
  ];

  function sortIcon(col: SortColumn): string {
    if (sortColumn !== col) return 'fa-sort';
    return sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
  }
</script>

<h1 class="title">Manage Users</h1>

<div class="columns">
  <div class="column">
    <div class="field">
      <label class="label" for="user_search">Search</label>
      <p class="control has-icons-right">
        <input
          class="input"
          type="text"
          id="user_search"
          placeholder="Name, username, or e-mail address"
          bind:value={searchQuery}
          on:input={onSearchInput}
        />
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="help">
        {#if cacheComplete}
          Filters instantly from cached list
        {:else}
          Type at least 3 characters to search
        {/if}
      </p>
    </div>
  </div>

  <div class="column">
    <div class="field">
      <label class="label" for="filter_role">Filter by role</label>
      <div class="control">
        <div class="select">
          <select id="filter_role" bind:value={selectedRoleId} on:change={onRoleChange}>
            <option value={0}>Show All</option>
            {#each allRoles as role}
              <option value={role.id}>{role.name.charAt(0).toUpperCase() + role.name.slice(1)}</option>
            {/each}
          </select>
        </div>
      </div>
    </div>
  </div>
</div>

{#if isLoading}
  <p class="has-text-grey"><span class="icon"><i class="fas fa-spinner fa-spin"></i></span> Loading…</p>
{:else if displayUsers.length === 0}
  <p class="is-size-5">No results found.</p>
{:else}
  <table class="table is-striped is-hoverable is-fullwidth">
    <thead>
      <tr>
        {#each sortableColumns as { col, label }}
          <th class="is-clickable" on:click={() => setSort(col)} title="Sort by {label}">
            {label}
            <span class="icon is-small"><i class="fas {sortIcon(col)}"></i></span>
          </th>
        {/each}
        <th>Roles</th>
        <th class="is-clickable" on:click={() => setSort('last_login')} title="Sort by Last Login">
          Last Login
          <span class="icon is-small"><i class="fas {sortIcon('last_login')}"></i></span>
        </th>
        <th class="is-clickable" on:click={() => setSort('logins')} title="Sort by Logins">
          Logins
          <span class="icon is-small"><i class="fas {sortIcon('logins')}"></i></span>
        </th>
        <th>&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      {#each displayUsers as user (user.id)}
        <tr>
          <td>{user.name}</td>
          <td>{user.email}</td>
          <td>{user.username}</td>
          <td>{user.roles.map(r => r.name.charAt(0).toUpperCase() + r.name.slice(1)).join(', ')}</td>
          <td>
            {#if user.last_login === '0000-00-00 00:00:00'}
              Never
            {:else}
              <span class="tooltip" title={formatDate(user.last_login)}>{timeAgo(user.last_login)}</span>
            {/if}
          </td>
          <td>{user.logins}</td>
          <td>
            {#if !user.is_current_user}
              <button
                class="button is-ghost is-small has-text-primary action-btn"
                title="Manage Roles"
                on:click={() => (modalUser = user)}
              >
                <span class="icon"><i class="fas fa-key"></i></span>
              </button>
              <button
                class="button is-ghost is-small has-text-danger action-btn"
                title="Remove User"
                on:click={() => removeUser(user)}
              >
                <span class="icon"><i class="fas fa-ban"></i></span>
              </button>
            {/if}
          </td>
        </tr>
      {/each}
    </tbody>
  </table>

  <div class="level">
    <div class="level-left">
      <p class="level-item has-text-grey is-size-7">
        Showing {displayUsers.length} of {displayTotal} user{displayTotal !== 1 ? 's' : ''}
        {#if displayUsers.length < displayTotal}— scroll down to load more{/if}
      </p>
    </div>
  </div>

  <!-- Infinite scroll sentinel -->
  <div bind:this={sentinel}></div>

  {#if isLoadingMore}
    <p class="has-text-grey has-text-centered">
      <span class="icon"><i class="fas fa-spinner fa-spin"></i></span> Loading more…
    </p>
  {/if}
{/if}

{#if modalUser}
  <RolesModal
    user={modalUser}
    {allRoles}
    {isDeveloper}
    onSave={saveRoles}
    onClose={() => (modalUser = null)}
  />
{/if}

<style>
  th.is-clickable {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
  }
  th.is-clickable:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }
  .action-btn {
    padding: 0 0.25rem;
    height: auto;
    border: none;
    background: none;
  }
</style>
