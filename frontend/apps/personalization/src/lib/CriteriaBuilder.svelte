<script lang="ts">
  import type { Criteria, CriterionType, Criterion, Role } from './types';

  export let criteria: Criteria = [];
  export let i18nSegments: string[];
  export let roles: Role[];

  function defaultForType(type: CriterionType): Criterion {
    switch (type) {
      case 'i18n':
        return { type, operator: '=', value: i18nSegments[0]?.toLowerCase() ?? '' };
      case 'session_key':
        return { type, operator: 'true', value: '' };
      case 'required_role':
        return { type, operator: '=', value: roles[0]?.name?.toLowerCase() ?? '' };
    }
  }

  export function addOrGroup() {
    criteria = [...criteria, [defaultForType('i18n')]];
  }

  function removeOrGroup(orIdx: number) {
    criteria = criteria.filter((_, i) => i !== orIdx);
  }

  function addAndCondition(orIdx: number) {
    criteria = criteria.map((group, i) =>
      i === orIdx ? [...group, defaultForType('i18n')] : group
    );
  }

  function removeAndCondition(orIdx: number, andIdx: number) {
    criteria = criteria.map((group, i) =>
      i === orIdx ? group.filter((_, j) => j !== andIdx) : group
    );
  }

  function handleTypeChange(orIdx: number, andIdx: number, e: Event) {
    const newType = (e.target as HTMLSelectElement).value as CriterionType;
    criteria = criteria.map((group, i) =>
      i === orIdx
        ? group.map((c, j) => j === andIdx ? defaultForType(newType) : c)
        : group
    );
  }

  function handleOperatorChange(orIdx: number, andIdx: number, e: Event) {
    const value = (e.target as HTMLSelectElement).value;
    criteria = criteria.map((group, i) =>
      i === orIdx
        ? group.map((c, j) => j === andIdx ? { ...c, operator: value } : c)
        : group
    );
  }

  function handleValueChange(orIdx: number, andIdx: number, e: Event) {
    const value = (e.target as HTMLSelectElement | HTMLInputElement).value;
    criteria = criteria.map((group, i) =>
      i === orIdx
        ? group.map((c, j) => j === andIdx ? { ...c, value } : c)
        : group
    );
  }
</script>

{#each criteria as group, orIdx}
  {#if orIdx > 0}
    <div class="or-separator">&mdash; OR &mdash;</div>
  {/if}

  <div class="or-block">
    <div class="level is-mobile or-block-header">
      <div class="level-left">
        <button
          type="button"
          class="button is-small is-light"
          on:click={() => addAndCondition(orIdx)}
        >
          <span class="icon is-small"><i class="fas fa-plus"></i></span>
          <span>Add "And" Criteria</span>
        </button>
      </div>
      <div class="level-right">
        <button
          type="button"
          class="button is-small is-danger is-light tooltip is-tooltip-left"
          data-tooltip="Remove this 'OR' block"
          on:click={() => removeOrGroup(orIdx)}
        >
          Remove <span class="icon is-small"><i class="fas fa-trash"></i></span>
        </button>
      </div>
    </div>

    {#each group as condition, andIdx}
      {#if andIdx > 0}
        <div class="and-separator">AND</div>
      {/if}

      <div class="columns is-mobile is-vcentered condition-row">
        <!-- Type select -->
        <div class="column is-narrow">
          <div class="select is-small">
            <select value={condition.type} on:change={(e) => handleTypeChange(orIdx, andIdx, e)}>
              <option value="i18n">URL i18n Segment</option>
              <option value="session_key">Session Variable</option>
              <option value="required_role">User Role</option>
            </select>
          </div>
        </div>

        {#if condition.type === 'i18n'}
          <div class="column is-narrow">
            <div class="select is-small">
              <select value={condition.operator} on:change={(e) => handleOperatorChange(orIdx, andIdx, e)}>
                <option value="=">IS</option>
                <option value="!=">IS NOT</option>
              </select>
            </div>
          </div>
          <div class="column is-narrow">
            <div class="select is-small">
              <select value={condition.value} on:change={(e) => handleValueChange(orIdx, andIdx, e)}>
                {#each i18nSegments as seg}
                  <option value={seg.toLowerCase()}>~/{seg.toUpperCase()}</option>
                {/each}
                <option value="">Not Set</option>
              </select>
            </div>
          </div>

        {:else if condition.type === 'session_key'}
          <div class="column">
            <input
              class="input is-small"
              type="text"
              placeholder="Session_Key"
              value={condition.value}
              on:input={(e) => handleValueChange(orIdx, andIdx, e)}
            />
          </div>
          <div class="column is-narrow">
            <div class="select is-small">
              <select value={condition.operator} on:change={(e) => handleOperatorChange(orIdx, andIdx, e)}>
                <option value="true">IS TRUE</option>
                <option value="false">IS FALSE</option>
                <option value="isset">Is Set (any value)</option>
                <option value="notset">Not Set</option>
              </select>
            </div>
          </div>

        {:else if condition.type === 'required_role'}
          <div class="column is-narrow">
            <div class="select is-small">
              <select value={condition.operator} on:change={(e) => handleOperatorChange(orIdx, andIdx, e)}>
                <option value="=">HAS ROLE</option>
                <option value="!=">IS NOT</option>
              </select>
            </div>
          </div>
          <div class="column is-narrow">
            <div class="select is-small">
              <select value={condition.value} on:change={(e) => handleValueChange(orIdx, andIdx, e)}>
                {#each roles as role}
                  <option value={role.name.toLowerCase()}>
                    {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                  </option>
                {/each}
              </select>
            </div>
          </div>
        {/if}

        <!-- Remove AND condition -->
        <div class="column is-narrow">
          <button
            type="button"
            class="delete-and icon has-text-grey-light"
            title="Remove this condition"
            on:click={() => removeAndCondition(orIdx, andIdx)}
          >
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    {/each}
  </div>
{/each}

<button
  type="button"
  class="button is-light mt-3"
  on:click={addOrGroup}
>
  <span class="icon is-small is-pulled-left"><i class="far fa-clone"></i></span>
  <span>Add New "Or" Criteria Block</span>
</button>

<style>
  .or-block {
    border: 1px solid #dbdbdb;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #fafafa;
  }

  .or-block-header {
    margin-bottom: 0.5rem;
  }

  .or-separator {
    text-align: center;
    font-weight: 600;
    color: #888;
    margin: 0.5rem 0;
    font-size: 0.85rem;
    letter-spacing: 0.05em;
  }

  .and-separator {
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #aaa;
    letter-spacing: 0.05em;
    margin: 0.25rem 0;
  }

  .condition-row {
    margin-bottom: 0;
  }

  .delete-and {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    opacity: 0.5;
    transition: opacity 0.15s;
  }

  .delete-and:hover {
    opacity: 1;
    color: #f14668 !important;
  }
</style>
