<script lang="ts">
  import AdminHome from './lib/AdminHome.svelte';
  import type { AdminHomeConfig } from './lib/types/adminHome';

  const config = (window as Window & { __ADMIN_HOME_CONFIG__: AdminHomeConfig }).__ADMIN_HOME_CONFIG__;
</script>

<AdminHome {config} />
