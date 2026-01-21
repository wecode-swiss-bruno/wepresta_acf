/**
 * Vite Configuration for ACF Entity Fields
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
function moveCSSToCssDirEntity() {
  return {
    name: 'move-css-to-views-css-entity',
    closeBundle() {
      const srcDir = path.resolve(__dirname, 'dist')
      const destDir = path.resolve(__dirname, '../../css/admin')

      // Ensure destination directory exists
      if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true })
      }

      // Move entity-fields CSS files and remove from dist/
      const files = fs.readdirSync(srcDir)
      files.forEach(file => {
        if (file.includes('entity-fields') && file.endsWith('.css')) {
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
        plugins: [vue(), moveCSSToCssDirEntity()],
        define: {
            'process.env': {}
        },
        css: {
            preprocessorOptions: {
                scss: {
                    api: 'modern-compiler'
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
            emptyOutDir: false, // Don't wipe dist as we build after main config
            manifest: 'manifest-entity.json', // Separate manifest for entity fields
            rollupOptions: {
                input: {
                    'entity-fields': path.resolve(__dirname, 'src/entity-fields.ts'),
                },
                output: {
                    format: 'iife',
                    name: 'WeprestaAcfEntityFields',
                    // No hash in filenames for simpler asset loading
                    entryFileNames: 'entity-fields.js',
                    assetFileNames: 'acf-entity-fields.[ext]',
                    inlineDynamicImports: true
                },
            },
        },
    }
})
