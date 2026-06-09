<script lang="ts">
  import UserManager from './lib/UserManager.svelte';
  import type { Role } from './lib/types/users';

  const config = (window as Window & {
    __USERS_CONFIG__: {
      xhrPath: string;
      roles: Role[];
      isDeveloper: boolean;
    }
  }).__USERS_CONFIG__;
</script>

<UserManager
  xhrPath={config.xhrPath}
  allRoles={config.roles}
  isDeveloper={config.isDeveloper}
/>
