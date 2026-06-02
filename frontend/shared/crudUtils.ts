export function quickNotice(message: string, type: string = 'is-success', duration: number = 2500): void {
  const notice = document.createElement('div');
  notice.className = `notification ${type} is-light crud-notice`;
  notice.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 280px; max-width: 420px; box-shadow: 0 4px 16px rgba(0,0,0,0.15);';
  notice.innerHTML = `<button class="delete"></button>${message}`;

  document.body.appendChild(notice);

  const remove = () => {
    notice.style.opacity = '0';
    notice.style.transition = 'opacity 0.3s';
    setTimeout(() => notice.remove(), 300);
  };

  notice.querySelector('.delete')?.addEventListener('click', remove);
  setTimeout(remove, duration);
}

export async function confirmation(
  message: string,
  onConfirm: () => void,
  onCancel: () => void
): Promise<void> {
  const modal = document.createElement('div');
  modal.className = 'modal is-active';
  modal.innerHTML = `
    <div class="modal-background"></div>
    <div class="modal-card">
      <header class="modal-card-head">
        <p class="modal-card-title">Confirm Action</p>
        <button class="delete" aria-label="close"></button>
      </header>
      <section class="modal-card-body">${message}</section>
      <footer class="modal-card-foot">
        <button class="button is-danger" id="confirm-yes">Yes, Continue</button>
        <button class="button" id="confirm-no">Cancel</button>
      </footer>
    </div>
  `;
  document.body.appendChild(modal);

  const close = () => modal.remove();

  modal.querySelector('.delete')?.addEventListener('click', () => { close(); onCancel(); });
  modal.querySelector('.modal-background')?.addEventListener('click', () => { close(); onCancel(); });
  modal.querySelector('#confirm-yes')?.addEventListener('click', () => { close(); onConfirm(); });
  modal.querySelector('#confirm-no')?.addEventListener('click', () => { close(); onCancel(); });
}
