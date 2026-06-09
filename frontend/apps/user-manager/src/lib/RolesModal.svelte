<script lang="ts">
  import type { Role, User } from './types/users';

  export let user: User;
  export let allRoles: Role[];
  export let isDeveloper: boolean;
  export let onSave: (userId: number, roleIds: number[]) => Promise<void>;
  export let onClose: () => void;

  let saving = false;
  let selectedRoleIds: Set<number> = new Set(user.roles.map(r => r.id));

  function toggle(roleId: number) {
    if (selectedRoleIds.has(roleId)) {
      selectedRoleIds.delete(roleId);
    } else {
      selectedRoleIds.add(roleId);
    }
    selectedRoleIds = selectedRoleIds;
  }

  async function save() {
    saving = true;
    await onSave(user.id, [...selectedRoleIds]);
    saving = false;
  }

  function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') onClose();
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<div class="modal is-active">
  <!-- svelte-ignore a11y-click-events-have-key-events a11y-no-static-element-interactions -->
  <div class="modal-background" on:click={onClose}></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">Manage Roles — {user.name}</p>
      <button class="delete" aria-label="close" on:click={onClose}></button>
    </header>
    <section class="modal-card-body">
      <div class="columns is-multiline">
        {#each allRoles as role}
          {#if role.name !== 'developer' || isDeveloper}
            <div class="column is-one-quarter">
              <label class="checkbox">
                <input
                  type="checkbox"
                  checked={selectedRoleIds.has(role.id)}
                  on:change={() => toggle(role.id)}
                />
                {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
              </label>
            </div>
          {/if}
        {/each}
      </div>
    </section>
    <footer class="modal-card-foot">
      <button class="button is-info" class:is-loading={saving} on:click={save}>Save Roles</button>
      <button class="button" on:click={onClose}>Cancel</button>
    </footer>
  </div>
</div>
