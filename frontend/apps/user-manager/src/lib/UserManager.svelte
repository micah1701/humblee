<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import RolesModal from './RolesModal.svelte';
  import { createUsersApi } from './services/usersApi';
  import type { User, Role, SortColumn, SortDirection } from './types/users';

  export let xhrPath: string;
  export let allRoles: Role[];
  export let isDeveloper: boolean;

  const api = createUsersApi(xhrPath);

  // Declared ambient globals provided by the admin shell
  declare function quickNotice(msg: string, type?: string): void;
  declare function confirmation(msg: string, onConfirm: () => void): void;

  // State
  let users: User[] = [];
  let total = 0;
  let isLoading = false;
  let isLoadingMore = false;
  let searchQuery = '';
  let selectedRoleId = 0;
  let sortColumn: SortColumn = 'name';
  let sortDirection: SortDirection = 'asc';

  // Modal
  let modalUser: User | null = null;

  // Infinite scroll sentinel
  let sentinel: HTMLElement;
  let observer: IntersectionObserver;
  let debounceTimer: ReturnType<typeof setTimeout>;

  const LIMIT = 50;

  function timeAgo(dateStr: string): string {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return 'Never';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)     return `${diff}s ago`;
    if (diff < 3600)   return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)  return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    if (diff < 2592000) return `${Math.floor(diff / 604800)}w ago`;
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

  async function loadUsers() {
    isLoading = true;
    users = [];
    total = 0;
    try {
      const data = await api.listUsers({
        search: searchQuery || undefined,
        role_id: selectedRoleId || undefined,
        offset: 0,
        limit: LIMIT,
        sort: sortColumn,
        direction: sortDirection,
      });
      users = data.users;
      total = data.total;
    } catch {
      quickNotice('Failed to load users.', 'danger');
    } finally {
      isLoading = false;
    }
  }

  async function loadMore() {
    if (isLoadingMore || users.length >= total) return;
    isLoadingMore = true;
    try {
      const data = await api.listUsers({
        search: searchQuery || undefined,
        role_id: selectedRoleId || undefined,
        offset: users.length,
        limit: LIMIT,
        sort: sortColumn,
        direction: sortDirection,
      });
      users = [...users, ...data.users];
      total = data.total;
    } catch {
      quickNotice('Failed to load more users.', 'danger');
    } finally {
      isLoadingMore = false;
    }
  }

  function onSearchInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadUsers, 300);
  }

  function onRoleChange() {
    loadUsers();
  }

  function setSort(col: SortColumn) {
    if (sortColumn === col) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = col;
      sortDirection = 'asc';
    }
    loadUsers();
  }

  function removeUser(user: User) {
    confirmation(`Remove ${user.name}? This cannot be undone.`, async () => {
      const result = await api.removeUser(user.id);
      if (result.success) {
        users = users.filter(u => u.id !== user.id);
        total = Math.max(0, total - 1);
        quickNotice(`${user.name} has been removed.`);
      } else {
        quickNotice(result.error ?? 'Could not remove user.', 'danger');
      }
    });
  }

  async function saveRoles(userId: number, roleIds: number[]) {
    const result = await api.setRoles(userId, roleIds);
    if (result.success) {
      const roleMap = new Map(allRoles.map(r => [r.id, r]));
      users = users.map(u => {
        if (u.id !== userId) return u;
        return { ...u, roles: roleIds.map(id => roleMap.get(id)!).filter(Boolean) };
      });
      quickNotice('Roles updated.');
      modalUser = null;
    } else {
      quickNotice(result.error ?? 'Could not save roles.', 'danger');
    }
  }

  onMount(() => {
    loadUsers();

    observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '200px' });

    if (sentinel) observer.observe(sentinel);
  });

  onDestroy(() => {
    observer?.disconnect();
    clearTimeout(debounceTimer);
  });

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
      <p class="help">Filters the list as you type</p>
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
{:else if users.length === 0}
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
      {#each users as user (user.id)}
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
        Showing {users.length} of {total} user{total !== 1 ? 's' : ''}
        {#if users.length < total}— scroll down to load more{/if}
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
