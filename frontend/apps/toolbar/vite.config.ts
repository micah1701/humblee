import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  build: {
    // Output directly into the admin JS directory alongside other admin scripts
    outDir: resolve(__dirname, '../../../public/humblee/js/admin'),
    emptyOutDir: false,
    rollupOptions: {
      input: resolve(__dirname, 'src/toolbar.ts'),
      output: {
        format: 'iife',
        entryFileNames: 'toolbar.js',
      }
    }
  }
})
