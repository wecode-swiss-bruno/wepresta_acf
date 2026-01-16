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
