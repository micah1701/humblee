<script lang="ts">
  import type { RecentContentItem } from './types/adminHome';

  export let items: RecentContentItem[];
  export let appPath: string;
  export let useP13n: boolean;

  function timeAgo(dateStr: string): string {
    // Revision dates are stored as UTC in the DB without a timezone suffix
    const date = new Date(dateStr.replace(' ', 'T') + 'Z');
    const diffSec = (Date.now() - date.getTime()) / 1000;
    if (diffSec < 60) return 'just now';
    if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
    if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
    if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d ago`;
    return date.toLocaleDateString();
  }

  function fullDate(dateStr: string): string {
    return new Date(dateStr.replace(' ', 'T') + 'Z').toLocaleString();
  }

  function statusLabel(item: RecentContentItem): string {
    if (item.live) return 'Live';
    if (item.publishDate) return 'Previously Published';
    return 'Draft';
  }

  function statusClass(item: RecentContentItem): string {
    if (item.live) return 'recent_content_live';
    if (item.publishDate) return 'recent_content_previsoulyLive';
    return 'recent_content_draft';
  }
</script>

{#if items.length === 0}
  <p class="has-text-grey">No recent content found.</p>
{:else}
  <table class="table is-striped is-hoverable" style="width:100%">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <th>Page Label</th>
        <th>Type</th>
        <th>Status</th>
        <th>&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      {#each items as item (item.id)}
        <tr>
          <td>
            <span class="tooltip" data-tooltip={fullDate(item.revisionDate)}>
              {timeAgo(item.revisionDate)}
            </span>
          </td>
          <td>{item.pageLabel}</td>
          <td>
            {item.typeName}
            {#if useP13n && item.p13nName}
              <span class="has-text-info">({item.p13nName})</span>
            {/if}
          </td>
          <td><span class={statusClass(item)}>{statusLabel(item)}</span></td>
          <td>
            <a href="{appPath}admin/edit/{item.id}" class="button is-info is-small">
              <span class="icon is-small"><i class="fas fa-edit"></i></span>
              <span>Edit</span>
            </a>
          </td>
        </tr>
      {/each}
    </tbody>
  </table>
{/if}
