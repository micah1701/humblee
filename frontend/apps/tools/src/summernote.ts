// $ and $.summernote are provided by summernote-lite.min.js loaded as a classic script
// @types/jquery and @types/summernote provide compile-time types only — no runtime jQuery dep
declare const $: JQueryStatic

declare function mediamanager(): void

interface MediaFileData {
  url: string
  author: string
}

declare global {
  interface Window {
    handleMediaManagerSelect(fileData: MediaFileData): void
  }
}

// Summernote button plugin: CSS class picker (placeholder)
function makeImageSCAdrop(_context: unknown) {
  return $.summernote.ui.button({
    contents: 'SCA Drop',
    click: () => alert('you want to add a class!'),
  }).render()
}

// Summernote button plugin: open the Media Manager modal
function launchMediaManager(_context: unknown) {
  return $.summernote.ui.button({
    tooltip: 'Insert Image from Media Manager',
    contents: '<i class="fa fa-camera"></i> Media Manager',
    click: () => mediamanager(),
  }).render()
}

const editEl = document.getElementById('edit_content')

if (editEl) {
  $(editEl).summernote({
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'underline', 'clear']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'imageFromMediaManager', 'video']],
      ['view', ['fullscreen', 'codeview', 'help']],
    ],
    styleTags: [
      { title: 'Page Heading', tag: 'h1', value: 'h1' },
      { title: 'Sub Heading', tag: 'h2', value: 'h2' },
      { title: 'Paragraph Text', tag: 'p', value: 'p' },
    ],
    popover: {
      image: [
        ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone', 'scaStyle']],
        ['float', ['floatLeft', 'floatRight', 'floatNone']],
        ['remove', ['removeMedia']],
      ],
    },
    height: 500,
    minHeight: 500,
    buttons: {
      scaStyle: makeImageSCAdrop,
      imageFromMediaManager: launchMediaManager,
    },
    callbacks: {
      onInit() {
        document.querySelector('.note-editable')?.classList.add('content')
      },
    },
  })
}

// Called when a user selects a file from the Media Manager
window.handleMediaManagerSelect = function (fileData: MediaFileData) {
  const el = document.getElementById('edit_content')
  if (!el) return

  $(el).summernote('insertImage', fileData.url, ($image: JQuery<HTMLImageElement>) => {
    const img = $image[0]
    // Use natural width if available; fall back to 800 for images not yet rendered
    const width = img.naturalWidth || img.offsetWidth
    const maxWidth = width > 0 && width < 800 ? width : 800
    img.style.width = '100%'
    img.style.maxWidth = `${maxWidth}px`
    img.classList.add('cms-image')
    img.setAttribute('data-author', fileData.author)
  })
}
