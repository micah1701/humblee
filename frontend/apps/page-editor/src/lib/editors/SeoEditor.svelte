<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import type { ContentRecord, PageData } from '../types/editor';

  export let content: ContentRecord;
  export let pageData: PageData;
  export let domain: string;

  const dispatch = createEventDispatcher<{ 'open-media': (url: string) => void }>();

  // Parse existing content JSON
  let parsed: Record<string, string> = {};
  try {
    parsed = JSON.parse(content.content) || {};
  } catch {
    parsed = {};
  }

  let pageTitle = parsed.page_title ?? '';
  let metaDescription = parsed.meta_description ?? '';
  let ogImage = parsed.og_image ?? '';
  let ogTitle = parsed.og_title ?? '';
  let ogDescription = parsed.og_description ?? '';

  // Reactive preview values
  $: googleTitle = pageTitle || 'Page Title Goes Here';
  $: googleUrl = domain + pageData.url;
  $: googleDescription = metaDescription || 'Description goes here';

  $: facebookImage = ogImage;
  $: facebookTitle = ogTitle || pageTitle || googleUrl;
  $: facebookDescription = ogDescription || metaDescription || '';

  // Char counters
  $: metaDescLen = metaDescription.length;
  $: ogDescLen = ogDescription.length;

  function openImagePicker() {
    dispatch('open-media', (url: string) => { ogImage = url; });
  }

  export function getContent(): string {
    return JSON.stringify({
      page_title: pageTitle,
      meta_description: metaDescription,
      og_image: ogImage,
      og_title: ogTitle,
      og_description: ogDescription,
    });
  }
</script>

<div class="columns">
  <!-- ── Input column ── -->
  <div class="column">
    <div class="field">
      <label class="label" for="seo_page_title">SEO Page Title</label>
      <div class="control">
        <input
          class="input"
          id="seo_page_title"
          type="text"
          bind:value={pageTitle}
          placeholder="Describe the page with your most important keywords"
        >
        <p class="help">Search engines display the first 60–70 characters of this title.</p>
      </div>
    </div>

    <div class="field">
      <label class="label" for="seo_meta_description">SEO Page Description</label>
      <div class="control">
        <textarea
          class="textarea"
          id="seo_meta_description"
          maxlength="300"
          bind:value={metaDescription}
          rows="3"
        ></textarea>
      </div>
      <p class="char-counter" class:is-near-limit={metaDescLen > 250} class:is-at-limit={metaDescLen >= 300}>
        {metaDescLen} / 300
      </p>
    </div>

    <!-- Open Graph -->
    <div class="box">
      <p class="is-size-5 mb-3">Open Graph Tags</p>
      <p class="help mb-3">Used by Facebook and other social sharing sites.</p>

      <div class="field">
        <label class="label" for="seo_og_image">
          Open Graph Image
          <span class="icon has-text-info tooltip is-size-7" data-tooltip="Primary image shown when sharing this page">
            <i class="fas fa-info-circle"></i>
          </span>
        </label>
        <div class="field has-addons mb-2">
          <div class="control is-expanded">
            <input
              class="input"
              id="seo_og_image"
              type="text"
              bind:value={ogImage}
              placeholder="https://{domain}/path/to/image.png"
            >
          </div>
          <div class="control">
            <button class="button is-info" type="button" on:click={openImagePicker}>
              <span class="icon"><i class="fa fa-image"></i></span>
              <span>Select</span>
            </button>
          </div>
        </div>
        <p class="help">Must be a fully qualified URL starting with <em>https://</em></p>
      </div>

      <div class="field">
        <label class="label" for="seo_og_title">Open Graph Title</label>
        <div class="control">
          <input
            class="input"
            id="seo_og_title"
            type="text"
            bind:value={ogTitle}
            placeholder="Overrides the SEO title when sharing on social media"
          >
        </div>
      </div>

      <div class="field">
        <label class="label" for="seo_og_description">Open Graph Description</label>
        <div class="control">
          <textarea
            class="textarea"
            id="seo_og_description"
            maxlength="160"
            bind:value={ogDescription}
            rows="3"
          ></textarea>
        </div>
        <p class="char-counter" class:is-near-limit={ogDescLen > 130} class:is-at-limit={ogDescLen >= 160}>
          {ogDescLen} / 160
        </p>
      </div>
    </div>
  </div>

  <!-- ── Preview column ── -->
  <div class="column">
    <!-- Google SERP preview -->
    <div class="box mb-4" id="google_sample">
      <span id="google_sample_title">{googleTitle}</span>
      <span id="google_sample_url">{googleUrl}</span>
      <span id="google_sample_description">{googleDescription}</span>
      <p><br><em>Sample of how this page may appear in Google search results.</em></p>
    </div>

    <!-- Facebook card preview -->
    <div class="box" id="facebook_sample">
      {#if facebookImage}
        <span id="facebook_sample_image">
          <img src="{facebookImage}" alt="OG preview">
        </span>
      {/if}
      <span id="facebook_sample_title">{facebookTitle}</span>
      <span id="facebook_sample_description">{facebookDescription}</span>
      <span id="facebook_sample_domain">{domain}</span>
      <p><br><em>Sample of how this page may appear on Facebook.</em></p>
    </div>
  </div>
</div>

<style>
  #google_sample {
    width: 560px;
    max-width: 100%;
    background-color: #fff;
    padding: 10px 20px;
    font-family: arial, sans-serif;
  }
  #google_sample #google_sample_title {
    display: block;
    font-size: 18px;
    color: #1a0dab;
    line-height: 24px;
  }
  #google_sample #google_sample_url {
    display: block;
    font-size: 14px;
    color: #006621;
  }
  #google_sample #google_sample_description {
    display: block;
    font-size: small;
    color: #545454;
  }

  #facebook_sample {
    width: 100%;
    max-width: 500px;
  }
  #facebook_sample #facebook_sample_image {
    display: block;
  }
  #facebook_sample #facebook_sample_image img {
    width: 100%;
    max-width: 500px;
  }
  #facebook_sample #facebook_sample_title {
    display: block;
    color: #1d2129;
    font-size: 18px;
    font-weight: 500;
    line-height: 22px;
  }
  #facebook_sample #facebook_sample_description {
    display: block;
    font-size: 12px;
    color: #90949c;
    line-height: 16px;
    max-height: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  #facebook_sample #facebook_sample_domain {
    display: block;
    padding-top: 9px;
    font-size: 11px;
    text-transform: uppercase;
    color: #90949c;
  }
</style>
