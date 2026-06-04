<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import LoginModal from './lib/LoginModal.svelte';
  import { checkSession } from './lib/sessionApi';
  import type { SessionMonitorConfig } from './lib/types';

  const config = (window as Window & { __SESSION_MONITOR_CONFIG__: SessionMonitorConfig }).__SESSION_MONITOR_CONFIG__;
  const { XHR_PATH, checkIntervalMs } = config;

  let showModal = false;
  let hmacToken = '';
  let hmacKey = '';
  let intervalId: ReturnType<typeof setInterval> | null = null;

  async function poll() {
    if (showModal) return;
    try {
      const status = await checkSession(XHR_PATH);
      if (!status.loggedIn) {
        hmacToken = status.hmacToken ?? '';
        hmacKey = status.hmacKey ?? '';
        showModal = true;
      }
    } catch {
      // network error — don't interrupt the user
    }
  }

  function handleRestored() {
    showModal = false;
  }

  function handleVisibilityChange() {
    if (document.visibilityState === 'visible') {
      poll();
    }
  }

  onMount(() => {
    poll();
    intervalId = setInterval(poll, checkIntervalMs);
    document.addEventListener('visibilitychange', handleVisibilityChange);
  });

  onDestroy(() => {
    if (intervalId !== null) clearInterval(intervalId);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
  });
</script>

{#if showModal}
  <LoginModal
    xhrPath={XHR_PATH}
    {hmacToken}
    {hmacKey}
    on:restored={handleRestored}
  />
{/if}
