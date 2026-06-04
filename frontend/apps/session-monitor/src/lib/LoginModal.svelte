<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { loginSession } from './sessionApi';

  export let xhrPath: string;
  export let hmacToken: string;
  export let hmacKey: string;

  const dispatch = createEventDispatcher<{ restored: void }>();

  let username = '';
  let password = '';
  let remember = false;
  let error = '';
  let submitting = false;

  async function handleSubmit() {
    if (!username || !password) {
      error = 'Please enter your username and password.';
      return;
    }
    submitting = true;
    error = '';
    try {
      const result = await loginSession(xhrPath, username, password, hmacToken, hmacKey, remember);
      if (result.success) {
        dispatch('restored');
      } else {
        error = result.message ?? 'Login failed. Please try again.';
      }
    } catch {
      error = 'A network error occurred. Please try again.';
    } finally {
      submitting = false;
    }
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter') handleSubmit();
  }
</script>

<div class="modal is-active" role="dialog" aria-modal="true" aria-label="Session expired">
  <div class="modal-background"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">Session Expired</p>
    </header>
    <section class="modal-card-body">
      <p class="mb-4">Your session has expired. Please sign in again to continue.</p>
      {#if error}
        <div class="notification is-danger is-light mb-4">{error}</div>
      {/if}
      <div class="field">
        <label class="label" for="sm-username">Username or Email</label>
        <div class="control">
          <input
            id="sm-username"
            class="input"
            type="text"
            bind:value={username}
            on:keydown={handleKeydown}
            disabled={submitting}
            autocomplete="username"
          />
        </div>
      </div>
      <div class="field">
        <label class="label" for="sm-password">Password</label>
        <div class="control">
          <input
            id="sm-password"
            class="input"
            type="password"
            bind:value={password}
            on:keydown={handleKeydown}
            disabled={submitting}
            autocomplete="current-password"
          />
        </div>
      </div>
      <div class="field">
        <label class="checkbox">
          <input type="checkbox" bind:checked={remember} disabled={submitting} />
          Remember me on this device
        </label>
      </div>
    </section>
    <footer class="modal-card-foot">
      <button
        class="button is-primary"
        class:is-loading={submitting}
        on:click={handleSubmit}
        disabled={submitting}
      >
        Sign In
      </button>
    </footer>
  </div>
</div>
