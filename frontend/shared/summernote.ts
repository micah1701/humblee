// $ and $.summernote are provided by summernote-lite.min.js loaded as a classic script.
// Typed as any here so both the tools app (which has @types/summernote) and other apps
// (which don't) can import this module without needing extra devDependencies.
declare const $: any
declare function mediamanager(): void

export interface MediaFileData {
  url: string
  author: string
}

export interface SummernoteOverrides {
  height?: number
  onChange?: (contents: string) => void
  // SPA context: callback-based media manager. Falls back to legacy global mediamanager().
  openMediaManager?: () => void
}

declare global {
  interface Window {
    handleMediaManagerSelect(fileData: MediaFileData): void
  }
}

function makeImageSCAdrop(_context: unknown): unknown {
  return $.summernote.ui.button({
    contents: 'SCA Drop',
    click: () => alert('you want to add a class!'),
  }).render()
}

export function buildSummernoteConfig(overrides: SummernoteOverrides = {}) {
  const { openMediaManager } = overrides

  // Defined inline so the click handler can close over openMediaManager.
  // tooltip omitted — summernote-lite's TooltipUI throws when positioning custom buttons.
  function makeMediaManagerButton(_context: unknown): unknown {
    return $.summernote.ui.button({
      contents: '<i class="fa fa-camera"></i> Media Manager',
      click: () => {
        if (openMediaManager) {
          openMediaManager()
        } else {
          mediamanager()
        }
      },
    }).render()
  }

  return {
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
    height: overrides.height ?? 500,
    minHeight: 500,
    buttons: {
      scaStyle: makeImageSCAdrop,
      imageFromMediaManager: makeMediaManagerButton,
    },
    callbacks: {
      onInit() {
        document.querySelector('.note-editable')?.classList.add('content')
      },
      ...(overrides.onChange ? { onChange: overrides.onChange } : {}),
    },
  }
}

// Used by the tools app (legacy PHP pages) where window.handleMediaManagerSelect
// is the integration point. Not used in the SPA page-editor.
export function registerMediaManagerHandler(): void {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const jq = (window as any).jQuery || (window as any).$

  window.handleMediaManagerSelect = function (fileData: MediaFileData) {
    const el = document.getElementById('edit_content')
    if (!el) return

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    jq(el).summernote('insertImage', fileData.url, ($image: any) => {
      const img = $image[0]
      const width = img.naturalWidth || img.offsetWidth
      const maxWidth = width > 0 && width < 800 ? width : 800
      img.style.width = '100%'
      img.style.maxWidth = `${maxWidth}px`
      img.classList.add('cms-image')
      img.setAttribute('data-author', fileData.author)
    })
  }
}
