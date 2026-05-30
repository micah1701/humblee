<script lang="ts">
  import MediaManager from './lib/MediaManager.svelte';
  import type { AccessRole } from './lib/types/media';

  const config = (window as Window & { __MEDIA_CONFIG__: {
    hasMediaRole: boolean;
    isInIframe: boolean;
    accessRoles: AccessRole[];
    XHR_PATH: string;
    WEB_ROOT: string;
    appPath?: string;
  } }).__MEDIA_CONFIG__;

  const hasMediaRole: boolean = config.hasMediaRole;
  const isInIframe: boolean = config.isInIframe;
  const accessRoles: AccessRole[] = config.accessRoles;
  const XHR_PATH: string = config.XHR_PATH;
  const WEB_ROOT: string = config.WEB_ROOT;
  const appPath: string = config.appPath ?? '/';
</script>

<MediaManager
  {hasMediaRole}
  {isInIframe}
  {accessRoles}
  {XHR_PATH}
  {WEB_ROOT}
  {appPath}
/>
