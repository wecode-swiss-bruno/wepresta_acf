/**
 * Vite Configuration for ACF Admin Vue.js App
 *
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license AFL-3.0
 */

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import fs from 'fs'

// Plugin to move CSS files to views/css/admin/ and remove them from dist/
function moveCSSToCssDir() {
  return {
    name: 'move-css-to-views-css',
    closeBundle() {
      const srcDir = path.resolve(__dirname, 'dist')
      const destDir = path.resolve(__dirname, '../../css/admin')

      // Ensure destination directory exists
      if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true })
      }

      // Move all CSS files and remove from dist/
      const files = fs.readdirSync(srcDir)
      files.forEach(file => {
        if (file.endsWith('.css')) {
          const srcPath = path.join(srcDir, file)
          const destPath = path.join(destDir, file)
          fs.renameSync(srcPath, destPath)
          console.log(`  Moved ${file} to views/css/admin/`)
        }
      })
    }
  }
}

export default defineConfig(({ mode }) => {
  const isProd = mode === 'production'

  return {
    plugins: [vue(), moveCSSToCssDir()],
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
      // Only empty in production to avoid race conditions with entity config in dev
      emptyOutDir: isProd,
      // Generate manifest.json for asset resolution in templates
      manifest: true,
      rollupOptions: {
        input: {
          main: path.resolve(__dirname, 'src/main.ts'),
        },
        output: {
          format: 'es',
          // No hash in filenames for simpler asset loading
          entryFileNames: 'acf-[name].js',
          chunkFileNames: 'acf-[name].js',
          assetFileNames: 'acf-[name].[ext]',
        },
      },
    },
  }
})
