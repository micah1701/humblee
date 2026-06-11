// Quill is loaded from CDN as a classic script; declare its global here
declare const Quill: {
  new (selector: string | Element, options: Record<string, unknown>): QuillInstance
}

interface QuillRange {
  index: number
  length: number
}

interface QuillInstance {
  getModule(name: string): { addHandler(type: string, fn: () => void): void }
  on(event: string, fn: () => void): void
  getSelection(): QuillRange | null
  insertEmbed(index: number, type: string, value: unknown): void
  container: Element
}

declare function mediamanager(): void

declare global {
  interface Window {
    handleMediaManagerSelect(fileData: { url: string }): void
  }
}

const quill = new Quill('#edit_content', {
  theme: 'snow',
  modules: {
    toolbar: [
      ['bold', 'italic', 'underline', 'strike'],
      ['link', 'blockquote', 'image'],
      [{ header: [1, 2, 3, false] }],
      [{ list: 'ordered' }, { list: 'bullet' }],
      [{ script: 'sub' }, { script: 'super' }],
      ['clean'],
    ],
  },
})

quill.getModule('toolbar').addHandler('image', selectFromMediaManager)

quill.on('editor-change', () => {
  const contentInput = document.getElementById('content') as HTMLInputElement | null
  if (contentInput) {
    contentInput.value = (quill.container.firstElementChild as Element).innerHTML
  }
})

let insertPointIndex = 0

function selectFromMediaManager() {
  const range = quill.getSelection()
  insertPointIndex = range ? range.index : 0
  mediamanager()
}

// Double-click an image inside the editor to edit its properties
document.getElementById('edit_content')?.addEventListener('dblclick', (event) => {
  const target = event.target
  if (!(target instanceof HTMLImageElement)) return

  const declaredWidth = target.style.width
  const setWidth = declaredWidth === '' ? '100%' : declaredWidth

  const imageClass = document.getElementById('imageClass') as HTMLInputElement | null
  const imageWidth = document.getElementById('imageWidth') as HTMLInputElement | null
  const imageMaxWidth = document.getElementById('imageMaxWidth') as HTMLInputElement | null
  const imageProperties = document.getElementById('imageProperties')

  if (imageClass) imageClass.value = target.className
  if (imageWidth) imageWidth.value = setWidth
  // style.maxWidth reads the inline declaration (same as what we set on save)
  if (imageMaxWidth) imageMaxWidth.value = target.style.maxWidth
  imageProperties?.classList.add('is-active')

  function onSave() {
    if (imageClass) target.className = imageClass.value
    if (imageWidth) target.style.width = imageWidth.value
    if (imageMaxWidth) target.style.maxWidth = imageMaxWidth.value
    closeDialog()
  }

  function onCancel() {
    closeDialog()
  }

  function closeDialog() {
    imageProperties?.classList.remove('is-active')
    if (imageClass) imageClass.value = ''
    if (imageWidth) imageWidth.value = ''
    if (imageMaxWidth) imageMaxWidth.value = ''
    document.getElementById('imagePropertiesSave')?.removeEventListener('click', onSave)
    document.getElementById('imagePropertiesCancel')?.removeEventListener('click', onCancel)
  }

  document.getElementById('imagePropertiesSave')?.addEventListener('click', onSave)
  document.getElementById('imagePropertiesCancel')?.addEventListener('click', onCancel)
})

// Called when a user selects a file from the Media Manager
window.handleMediaManagerSelect = function (fileData) {
  quill.insertEmbed(insertPointIndex, 'image', fileData.url)
}
