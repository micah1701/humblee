import { defineConfig, type Plugin } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import { resolve } from 'path'
import { rmSync } from 'fs'

function removeHtml(): Plugin {
  return {
    name: 'remove-html',
    closeBundle() {
      try {
        rmSync(resolve(__dirname, '../../../public/humblee/js/admin/templates/index.html'))
      } catch {}
    }
  }
}

export default defineConfig({
  plugins: [svelte(), removeHtml()],
  resolve: {
    alias: {
      '@crud-shared': resolve(__dirname, '../../shared'),
    },
  },
  build: {
    outDir: resolve(__dirname, '../../../public/humblee/js/admin/templates'),
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
