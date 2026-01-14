import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler' // Use modern Sass API to avoid deprecation warnings
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src/main.ts'),
        'entity-fields': path.resolve(__dirname, 'src/entity-fields.ts'),
      },
      output: {
        format: 'es',
        entryFileNames: (chunkInfo) => {
          // Custom names for entry points
          if (chunkInfo.name === 'main') return 'acf-admin.js'
          if (chunkInfo.name === 'entity-fields') return 'entity-fields.js'
          return 'acf-[name].js'
        },
        chunkFileNames: 'acf-[name].js',
        assetFileNames: 'acf-[name].[ext]',
      },
    },
  },
})
