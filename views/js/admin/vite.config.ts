/**
 * Vite Configuration for ACF Admin Vue.js App
 *
 * @author WePresta
 * @copyright 2024-2026 WePresta
 * @license AFL-3.0
 */

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import fs from 'fs'

// License banner for PrestaShop Addons compliance (must start with /*! to be preserved)
const licenseBanner = `/*!
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
`

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

// Plugin to prepend license banner to JS files after build
function prependLicenseBanner() {
    return {
        name: 'prepend-license-banner',
        writeBundle(options: any, bundle: any) {
            const outDir = options.dir || path.resolve(__dirname, 'dist')

            for (const fileName of Object.keys(bundle)) {
                if (fileName.endsWith('.js')) {
                    const filePath = path.join(outDir, fileName)
                    if (fs.existsSync(filePath)) {
                        const content = fs.readFileSync(filePath, 'utf-8')
                        // Only add if not already present
                        if (!content.startsWith('/*!')) {
                            fs.writeFileSync(filePath, licenseBanner + content, 'utf-8')
                            console.log(`  Added license banner to ${fileName}`)
                        }
                    }
                }
            }
        }
    }
}

export default defineConfig(({ mode }) => {
    const isProd = mode === 'production'

    return {
        plugins: [vue(), moveCSSToCssDir(), prependLicenseBanner()],
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
