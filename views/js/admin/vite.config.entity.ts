import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    define: {
        'process.env': {}
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src'),
        },
    },
    build: {
        outDir: 'dist',
        emptyOutDir: false, // Don't wipe dist as we might have other assets
        rollupOptions: {
            input: {
                'entity-fields': path.resolve(__dirname, 'src/entity-fields.ts'),
            },
            output: {
                format: 'iife',
                name: 'WeprestaAcfEntityFields',
                entryFileNames: 'entity-fields.js',
                assetFileNames: 'acf-entity-fields.[ext]', // Distinct name
                inlineDynamicImports: true
            },
        },
    },
})
