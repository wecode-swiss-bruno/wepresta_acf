/**
 * Webpack Encore Configuration
 *
 * Build assets modernes pour PrestaShop
 * - SCSS compilation
 * - TypeScript support
 * - PostCSS + Autoprefixer
 * - Minification production
 * 
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license AFL-3.0
 */

const Encore = require('@symfony/webpack-encore');
const webpack = require('webpack');
const path = require('path');

const licenseBanner = `/**
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
`;

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    // PrestaShop Addons requires JS in /views/js/
    .setOutputPath('views/js/')

    // public path used by the web server to access the output path
    .setPublicPath('/modules/wepresta_acf/views/js')

    // manifest.json for cache busting
    .setManifestKeyPrefix('wepresta_acf')

    // CSS output to views/css/ for PrestaShop Addons compliance
    .configureMiniCssExtractPlugin(
        (loaderOptions) => { },
        (pluginOptions) => {
            pluginOptions.filename = '../css/[name].css';
        }
    )

    /*
     * ENTRY CONFIG
     */
    .addEntry('admin', './_dev/js/admin.js')

    // enables the Symfony UX Stimulus bridge (used in Symfony projects)
    // .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    // Disabled chunk splitting for PrestaShop Addons compatibility
    .disableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    // Clean only JS output files, keep existing assets and admin/ folder (Vue app source)
    // Note: CSS and images outside views/js/ are not cleaned (clean-webpack-plugin limitation)
    .cleanupOutputBeforeBuild([
        'admin.js',
        'manifest.json',
        'entrypoints.json'
    ])
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // Disable versioning for PrestaShop modules (simpler asset loading)
    .enableVersioning(false)

    // configure Babel
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.36';
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // enables PostCSS support
    .enablePostCssLoader((config) => {
        config.postcssOptions = {
            plugins: [
                require('autoprefixer'),
            ],
        };
    })

    // uncomment to enable TypeScript
    // .enableTypeScriptLoader()

    // Copy static images to views/img/ for PrestaShop Addons compliance
    .copyFiles({
        from: './_dev/images',
        to: '../img/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
    })

    // Add aliases
    .addAliases({
        '@': path.resolve(__dirname, '_dev/js'),
        '@scss': path.resolve(__dirname, '_dev/scss'),
    })

    // Add license banner
    .addPlugin(new webpack.BannerPlugin({
        banner: licenseBanner.replace(/^\/\*\*?/, '/*!'),
        raw: true,
        entryOnly: true
    }))

    // Prevent license extraction
    .configureTerserPlugin((options) => {
        options.extractComments = false;
        options.terserOptions = {
            format: {
                comments: /^\**!/i,
            },
        };
    });

module.exports = Encore.getWebpackConfig();

