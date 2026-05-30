import { defineConfig, type Plugin } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import { resolve } from 'path'
import { rmSync } from 'fs'

function removeHtml(): Plugin {
  return {
    name: 'remove-html',
    closeBundle() {
      try {
        rmSync(resolve(__dirname, '../../public/humblee/js/admin/media-manager/index.html'))
      } catch {}
    }
  }
}

export default defineConfig({
  plugins: [svelte(), removeHtml()],
  build: {
    outDir: resolve(__dirname, '../../public/humblee/js/admin/media-manager'),
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'index.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: 'index.[ext]',
      }
    }
  }
})
