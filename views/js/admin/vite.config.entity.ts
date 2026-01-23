/**
 * Vite Configuration for ACF Entity Fields
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

// Plugin to prepend license banner to JS files after build
function prependLicenseBannerEntity() {
    return {
        name: 'prepend-license-banner-entity',
        writeBundle(options: any, bundle: any) {
            const outDir = options.dir || path.resolve(__dirname, 'dist')

            for (const fileName of Object.keys(bundle)) {
                if (fileName.endsWith('.js') && fileName.includes('entity-fields')) {
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
        plugins: [vue(), moveCSSToCssDirEntity(), prependLicenseBannerEntity()],
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
                    inlineDynamicImports: true,
                },
            },
        },
    }
})
