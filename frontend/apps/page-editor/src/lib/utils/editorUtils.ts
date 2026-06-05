export function dateFormat(format: string, timestamp: string): string {
  const date = new Date(timestamp + (timestamp.includes('T') ? '' : 'Z'));

  const months = ['January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November', 'December'];
  const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const pad = (n: number) => n.toString().padStart(2, '0');

  const replacements: Record<string, string> = {
    'Y': date.getFullYear().toString(),
    'm': pad(date.getMonth() + 1),
    'd': pad(date.getDate()),
    'j': date.getDate().toString(),
    'F': months[date.getMonth()],
    'M': monthsShort[date.getMonth()],
    'n': (date.getMonth() + 1).toString(),
    'h': pad(date.getHours() % 12 || 12),
    'H': pad(date.getHours()),
    'i': pad(date.getMinutes()),
    's': pad(date.getSeconds()),
    'a': date.getHours() >= 12 ? 'pm' : 'am',
    'g': (date.getHours() % 12 || 12).toString(),
  };

  let formatted = format;
  for (const [key, value] of Object.entries(replacements)) {
    formatted = formatted.replace(new RegExp(key, 'g'), value);
  }
  return formatted;
}

export function quickNotice(message: string, type: string = 'is-success', duration: number = 2500): void {
  const notice = document.createElement('div');
  notice.className = `notification ${type} is-light`;
  notice.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px; max-width: 400px;';
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

export function confirmation(
  message: string,
  onConfirm: () => void,
  onCancel: () => void
): void {
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
        <button class="button is-warning" id="confirm-yes">Yes, Continue</button>
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
