import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  resolve: {
    alias: {
      '@crud-shared': resolve(__dirname, '../../shared'),
    },
  },
  build: {
    // ../../../ = up from tools/ → apps/ → frontend/ → project root
    outDir: resolve(__dirname, '../../../public/humblee/js/tools'),
    emptyOutDir: false, // preserve dateformat.js, friendlyfilesize.js
    rollupOptions: {
      input: {
        summernote: resolve(__dirname, 'src/summernote.ts'),
        quill: resolve(__dirname, 'src/quill.ts'),
      },
      output: {
        format: 'es',
        entryFileNames: '[name].js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: '[name].[ext]',
      },
    },
  },
})
