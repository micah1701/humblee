// $ and $.summernote are provided by summernote-lite.min.js loaded as a classic script
// @types/jquery and @types/summernote provide compile-time types only — no runtime jQuery dep
declare const $: JQueryStatic

import { buildSummernoteConfig, registerMediaManagerHandler } from '@crud-shared/summernote'

const editEl = document.getElementById('edit_content')

if (editEl) {
  $(editEl).summernote(buildSummernoteConfig())
}

registerMediaManagerHandler()
