export function friendlyFilesize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

export function dateFormat(format: string, timestamp: string): string {
  const date = new Date(timestamp);

  const months = ['January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November', 'December'];
  const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  const pad = (n: number) => n.toString().padStart(2, '0');

  const replacements: { [key: string]: string } = {
    'Y': date.getFullYear().toString(),
    'm': pad(date.getMonth() + 1),
    'd': pad(date.getDate()),
    'F': months[date.getMonth()],
    'M': monthsShort[date.getMonth()],
    'h': pad(date.getHours() % 12 || 12),
    'H': pad(date.getHours()),
    'i': pad(date.getMinutes()),
    's': pad(date.getSeconds()),
    'a': date.getHours() >= 12 ? 'pm' : 'am',
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
  notice.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
  notice.innerHTML = `
    <button class="delete"></button>
    ${message}
  `;

  document.body.appendChild(notice);

  const deleteBtn = notice.querySelector('.delete');
  const remove = () => {
    notice.style.opacity = '0';
    notice.style.transition = 'opacity 0.3s';
    setTimeout(() => notice.remove(), 300);
  };

  deleteBtn?.addEventListener('click', remove);
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
      <section class="modal-card-body">
        ${message}
      </section>
      <footer class="modal-card-foot">
        <button class="button is-danger" id="confirm-yes">Yes, Continue</button>
        <button class="button" id="confirm-no">Cancel</button>
      </footer>
    </div>
  `;

  document.body.appendChild(modal);

  const close = () => modal.remove();

  modal.querySelector('.delete')?.addEventListener('click', () => {
    close();
    onCancel();
  });

  modal.querySelector('.modal-background')?.addEventListener('click', () => {
    close();
    onCancel();
  });

  modal.querySelector('#confirm-yes')?.addEventListener('click', () => {
    close();
    onConfirm();
  });

  modal.querySelector('#confirm-no')?.addEventListener('click', () => {
    close();
    onCancel();
  });
}
