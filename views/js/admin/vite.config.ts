import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig(({ mode }) => {
  const isProd = mode === 'production'
  
  return {
    plugins: [vue()],
    define: {
      'process.env': {}
    },
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
      // Generate manifest.json for asset resolution in templates
      manifest: true,
      rollupOptions: {
        input: {
          main: path.resolve(__dirname, 'src/main.ts'),
          cpt: path.resolve(__dirname, 'src/cpt-main.ts'),
        },
        output: {
          format: 'es',
          // Add hash in production for cache-busting, simple names in dev for debugging
          entryFileNames: isProd ? 'acf-[name].[hash].js' : 'acf-[name].js',
          chunkFileNames: isProd ? 'acf-[name].[hash].js' : 'acf-[name].js',
          assetFileNames: isProd ? 'acf-[name].[hash].[ext]' : 'acf-[name].[ext]',
        },
      },
    },
  }
})
