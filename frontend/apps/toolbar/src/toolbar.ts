// Globals provided by the PHP page before this script is loaded
declare const toolbarData: { _app_path: string; js_load: string; name: string };
declare function setEscEvent(name: string, fn: () => void): void;
declare function unsetEscEvent(name: string): void;

const appPath = toolbarData._app_path;

// Load toolbar CSS
const cssLink = document.createElement('link');
cssLink.rel = 'stylesheet';
cssLink.type = 'text/css';
cssLink.href = `${appPath}humblee/css/admin/toolbar.css?${Date.now()}`;
document.head.appendChild(cssLink);

// Load admin.js (defines setEscEvent / unsetEscEvent used by click handler below)
const adminScript = document.createElement('script');
adminScript.src = `${appPath}humblee/js/admin/admin.js`;
document.head.appendChild(adminScript);

// Inject editor modal
document.body.insertAdjacentHTML('beforeend',
  '<div id="humbleeEditor" class="modal">'
  + '  <div class="modal-background"></div>'
  + '  <div class="modal-card">'
  + '    <header class="modal-card-head">'
  + '      <p class="modal-card-title">Content Editor</p>'
  + '      <button class="delete" aria-label="close"></button>'
  + '    </header>'
  + '    <section class="modal-card-body"></section>'
  + '  </div>'
  + '</div>'
);

function closeHumbleeEditor(): void {
  document.getElementById('humbleeEditor')?.classList.remove('is-active');
}

// Delegated mouseenter — show edit button when the cursor enters a .cms_block
document.addEventListener('mouseover', (e: MouseEvent) => {
  const block = (e.target as Element).closest<HTMLElement>('.cms_block');
  if (!block) return;
  // Ignore events that fire while moving between children (not a true entry)
  if (block.contains(e.relatedTarget as Node | null)) return;
  // Only add once per hover
  if (block.querySelector('.launch-editor')) return;

  const contentId = block.dataset.contentId ?? '';
  const blockName = block.dataset.blockName ?? '';

  const button = document.createElement('button');
  button.className = 'launch-editor';
  button.dataset.contentId = contentId;
  button.textContent = `Edit "${blockName}"`;
  button.style.opacity = '0';
  button.style.transition = 'opacity 0.2s';

  // Bulma's .content class adds margins to first/last child elements; injecting
  // directly into the block would shift those elements. Instead, nest into the
  // first child so Bulma's :first-child/:last-child selectors still apply correctly.
  if (block.parentElement?.closest('.content')) {
    block.firstElementChild?.appendChild(button);
  } else {
    block.appendChild(button);
  }

  // Force a reflow so the CSS transition plays from opacity 0 → 1
  button.getBoundingClientRect();
  button.style.opacity = '1';
});

// Delegated mouseleave — remove edit button when the cursor leaves a .cms_block
document.addEventListener('mouseout', (e: MouseEvent) => {
  const block = (e.target as Element).closest<HTMLElement>('.cms_block');
  if (!block) return;
  // Ignore events that fire while moving between children (not a true exit)
  if (block.contains(e.relatedTarget as Node | null)) return;
  block.querySelectorAll('.launch-editor').forEach(btn => btn.remove());
});

// Delegated click — open editor iframe in modal
document.addEventListener('click', (e: MouseEvent) => {
  const button = (e.target as Element).closest<HTMLElement>('.cms_block .launch-editor');
  if (!button) return;

  const contentId = button.dataset.contentId ?? '';
  const modalBody = document.querySelector('.modal-card-body');
  if (modalBody) {
    modalBody.innerHTML = `<iframe src="${appPath}admin/edit/${contentId}?iframe=true" border="0"></iframe>`;
  }

  const modal = document.getElementById('humbleeEditor');
  modal?.classList.add('is-active');

  setEscEvent('humbleeEditor', closeHumbleeEditor);
  modal?.querySelector('button.delete')?.addEventListener('click', () => {
    closeHumbleeEditor();
    unsetEscEvent('humbleeEditor');
  });
});
