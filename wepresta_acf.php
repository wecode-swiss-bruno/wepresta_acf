<?php

/**
 * WePresta ACF - Advanced Custom Fields for PrestaShop 8.x / 9.x.
 *
 * ACF-style custom fields for products, categories, and other entities.
 * Features:
 * - Visual field builder (Vue.js)
 * - Multiple field types (text, select, image, file, repeater...)
 * - Conditional logic
 * - Multi-shop / multi-lang support
 * - Front-office display
 *
 * @author      Bruno Studer
 * @copyright   2024 WeCode
 * @license     MIT
 */

declare(strict_types=1);

if (! defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/autoload.php';

use WeprestaAcf\Application\Config\EntityHooksConfig;
use WeprestaAcf\Application\Hook\EntityFieldHooksTrait;
use WeprestaAcf\Application\Installer\ModuleInstaller;
use WeprestaAcf\Application\Installer\ModuleUninstaller;
use WeprestaAcf\Application\Service\AcfServiceContainer;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;

class WeprestaAcf extends Module
{
    use EntityFieldHooksTrait;

    public const VERSION = '1.0.0';

    public const DEFAULT_CONFIG = [
        'WEPRESTA_ACF_MAX_FILE_SIZE' => 10485760, // 10MB
        'WEPRESTA_ACF_DEBUG' => false,
        // Auto-sync settings
        'WEPRESTA_ACF_AUTO_SYNC_ENABLED' => false,
        'WEPRESTA_ACF_SYNC_LAST_UPDATE' => 0,
    ];

    private ?ConfigurationAdapter $config = null;

    public function __construct()
    {
        $this->name = 'wepresta_acf';
        $this->tab = 'administration';
        $this->version = self::VERSION;
        $this->author = 'WePresta';
        $this->need_instance = false;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('ACF (Advanced Custom Fields) for PrestaShop', [], 'Modules.Weprestaacf.Admin');
        $this->description = $this->trans('Advanced Custom Fields for PrestaShop: create custom fields and field groups with an ACF-style builder, display rules and a clean back-office UI.', [], 'Modules.Weprestaacf.Admin');        
        $this->confirmUninstall = $this->trans('Delete all custom fields and values?', [], 'Modules.Weprestaacf.Admin');

        // Initialize service container
        AcfServiceContainer::init($this);
        AcfServiceContainer::setModuleVersion(self::VERSION);
    }

    // =========================================================================
    // INSTALLATION
    // =========================================================================

    public function install(): bool
    {
        try {
            $installer = new ModuleInstaller($this, Db::getInstance());
            $hooks = $this->getAllHooks();

            return parent::install() && $this->registerHook($hooks) && $installer->install();
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    public function uninstall(): bool
    {
        try {
            $uninstaller = new ModuleUninstaller($this, Db::getInstance());

            // Execute uninstall FIRST, then clear cache to avoid service loading errors
            $result = $uninstaller->uninstall() && parent::uninstall();
            
            // Clear Symfony cache AFTER successful uninstall
            if ($result) {
                $this->clearSymfonyCache();
            }
            
            return $result;
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    /**
     * Clear Symfony cache after uninstall to remove module services.
     * 
     * IMPORTANT: This should be called AFTER uninstall, not before,
     * to avoid service loading errors during the uninstall process.
     */
    private function clearSymfonyCache(): void
    {
        try {
            // Method 1: Use Symfony cache clearer (PS9 preferred method)
            if (class_exists('\PrestaShop\PrestaShop\Adapter\SymfonyContainer')) {
                $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
                
                if ($container && $container->has('cache.clearer')) {
                    $cacheClearer = $container->get('cache.clearer');
                    if (method_exists($cacheClearer, 'clear')) {
                        $cacheClearer->clear('');
                    }
                }
            }

            // Method 2: Clear container cache files only (safer than deleting entire cache dir)
            $rootDir = defined('_PS_ROOT_DIR_') ? _PS_ROOT_DIR_ : dirname(_PS_ADMIN_DIR_);
            $cacheDir = $rootDir . '/var/cache';
            
            if (is_dir($cacheDir)) {
                // Only remove container cache files, not the entire cache directory
                // This prevents breaking other cached services
                foreach (['dev', 'prod'] as $env) {
                    $containerFiles = glob($cacheDir . '/' . $env . '/*Container*');
                    if ($containerFiles) {
                        foreach ($containerFiles as $file) {
                            if (is_file($file)) {
                                @unlink($file);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Silently ignore cache clearing errors during uninstall
            // Cache will be regenerated on next page load
        }
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    public function getContent(): string
    {
        try {
            $router = $this->getContainer()->get('router');
            Tools::redirectAdmin($router->generate('wepresta_acf_builder'));
        } catch (Exception $e) {
            return '<div class="alert alert-danger">Configuration error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        return '';
    }

    public function hookActionAdminControllerSetMedia(array $params): void
    {
        $controller = Tools::getValue('controller');

        // ACF Builder and Configuration pages - load Vue.js builder assets
        if (in_array($controller, ['AdminWeprestaAcfBuilder', 'AdminWeprestaAcfConfiguration', 'AdminWeprestaAcfSync'], true)) {
            if (file_exists($this->getLocalPath() . 'views/dist/admin.css')) {
                $this->context->controller->addCSS($this->_path . 'views/dist/admin.css');
            }

            if (file_exists($this->getLocalPath() . 'views/dist/admin.js')) {
                $this->context->controller->addJS($this->_path . 'views/dist/admin.js');
            }
        }

        // Load ACF field assets on ALL admin pages
        // The JS only activates if #acf-entity-fields is present on the page
        $this->context->controller->addCSS($this->_path . 'views/css/admin-fields.css');
        $this->context->controller->addJS($this->_path . 'views/js/acf-fields.js');
    }

    // =========================================================================
    // FRONT-OFFICE HOOKS
    // =========================================================================

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (! $this->isActive()) {
            return;
        }

        $controller = Tools::getValue('controller');
        // Register JS for V1 entity pages (product, category)
        $v1Controllers = ['product', 'category'];

        if (in_array($controller, $v1Controllers, true)) {
            $this->context->controller->registerJavascript('wepresta_acf-front', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 150]);
        }

        // Load ACF front-office CSS
        $this->context->controller->registerStylesheet(
            'wepresta_acf-front-css',
            'modules/' . $this->name . '/views/css/acf-front.css',
            ['priority' => 150]
        );
    }

    /**
     * Hook displayHeader - Initialize ACF Smarty wrapper and register plugins.
     *
     * This makes $acf available in ALL Smarty templates:
     *   {$acf->field('brand')}
     *   {$acf->render('image')}
     *   {foreach $acf->repeater('specs') as $row}...{/foreach}
     */
    public function hookDisplayHeader(array $params): string
    {
        if (! $this->isActive()) {
            return '';
        }

        try {
            // Load custom field types for front-office rendering
            AcfServiceContainer::loadCustomFieldTypes();

            // Get Smarty wrapper instance
            $acfWrapper = AcfServiceContainer::getSmartyWrapper();

            // Assign $acf to Smarty context
            $this->context->smarty->assign('acf', $acfWrapper);

            // Register Smarty plugins
            $this->registerSmartyPlugins();
        } catch (Exception $e) {
            $this->log('ACF header initialization failed: ' . $e->getMessage(), 2);
        }

        return '';
    }

    /**
     * Hook filterCmsContent - Parse shortcodes in CMS page content.
     *
     * @param array{object: CMS, type: string, content: string} $params
     */
    public function hookFilterCmsContent(array $params): array
    {
        if (! $this->isActive()) {
            return $params;
        }

        try {
            $content = $params['content'] ?? '';

            if (strpos($content, '[acf') !== false) {
                $parser = AcfServiceContainer::getShortcodeParser();

                // Try to detect CMS context
                $cmsId = (int) Tools::getValue('id_cms', 0);
                $entityType = $cmsId > 0 ? 'cms_page' : null;

                $params['content'] = $parser->parse($content, $entityType, $cmsId > 0 ? $cmsId : null);
            }
        } catch (Exception $e) {
            $this->log('ACF shortcode parsing failed: ' . $e->getMessage(), 2);
        }

        return $params;
    }

    /**
     * Hook filterCategoryContent - Parse shortcodes in category description.
     *
     * @param array{object: Category, type: string, content: string} $params
     */
    public function hookFilterCategoryContent(array $params): array
    {
        if (! $this->isActive()) {
            return $params;
        }

        try {
            $content = $params['content'] ?? '';

            if (strpos($content, '[acf') !== false) {
                $parser = AcfServiceContainer::getShortcodeParser();
                $categoryId = (int) Tools::getValue('id_category', 0);

                $params['content'] = $parser->parse($content, 'category', $categoryId > 0 ? $categoryId : null);
            }
        } catch (Exception $e) {
            $this->log('ACF shortcode parsing failed: ' . $e->getMessage(), 2);
        }

        return $params;
    }

    /**
     * Hook filterProductContent - Parse shortcodes in product description.
     *
     * @param array{object: Product, type: string, content: string} $params
     */
    public function hookFilterProductContent(array $params): array
    {
        if (! $this->isActive()) {
            return $params;
        }

        try {
            $content = $params['content'] ?? '';

            if (strpos($content, '[acf') !== false) {
                $parser = AcfServiceContainer::getShortcodeParser();
                $productId = (int) Tools::getValue('id_product', 0);

                $params['content'] = $parser->parse($content, 'product', $productId > 0 ? $productId : null);
            }
        } catch (Exception $e) {
            $this->log('ACF shortcode parsing failed: ' . $e->getMessage(), 2);
        }

        return $params;
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function getConfig(): ConfigurationAdapter
    {
        return $this->config ??= new ConfigurationAdapter();
    }

    public function getService(string $serviceId): ?object
    {
        try {
            $container = $this->getContainer();

            if ($container && $container->has($serviceId)) {
                return $container->get($serviceId);
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Alias for getService() for compatibility.
     *
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T|null
     */
    public function get($serviceId): ?object
    {
        return $this->getService($serviceId);
    }

    public function log(string $message, int $severity = 1, array $context = []): void
    {
        $msg = '[' . $this->name . '] ' . $message;

        if (! empty($context)) {
            $msg .= ' | ' . json_encode($context);
        }
        PrestaShopLogger::addLog($msg, $severity, null, 'Module', $this->id);
    }

    public function clearModuleCache(): void
    {
        $this->_clearCache('*');
    }

    public function getModulePath(): string
    {
        return $this->getLocalPath();
    }

    /**
     * Register Smarty plugin functions.
     */
    private function registerSmartyPlugins(): void
    {
        $smarty = $this->context->smarty;

        // Register only if not already registered
        $pluginsDir = __DIR__ . '/src/Application/Smarty/';

        // Function plugins
        $functions = ['acf_field', 'acf_render', 'acf_group'];

        foreach ($functions as $funcName) {
            $pluginFile = $pluginsDir . 'function.' . $funcName . '.php';

            if (file_exists($pluginFile) && ! isset($smarty->registered_plugins['function'][$funcName])) {
                require_once $pluginFile;
                $smarty->registerPlugin('function', $funcName, 'smarty_function_' . $funcName);
            }
        }

        // Block plugins
        $blocks = ['acf_foreach'];

        foreach ($blocks as $blockName) {
            $pluginFile = $pluginsDir . 'block.' . $blockName . '.php';

            if (file_exists($pluginFile) && ! isset($smarty->registered_plugins['block'][$blockName])) {
                require_once $pluginFile;
                $smarty->registerPlugin('block', $blockName, 'smarty_block_' . $blockName);
            }
        }
    }

    /**
     * Gets all hooks to register.
     *
     * Uses the centralized EntityHooksConfig to get all hooks for enabled entities.
     * V1: Product + Category only.
     *
     * @return array<string>
     */
    private function getAllHooks(): array
    {
        // Get all hooks from centralized configuration
        // This includes: SYSTEM_HOOKS + ADMIN_HOOKS + FRONT_HOOKS
        return EntityHooksConfig::getAllHooks();
    }

    // =========================================================================
    // ENTITY HOOKS - Managed by EntityFieldHooksTrait
    // =========================================================================

    // All entity hooks (display, action, formBuilder, formHandler) are handled by the trait.
    // See: src/Application/Hook/EntityFieldHooksTrait.php
    // V1 Entities: product, category, customer, order
    // Future versions: Add more entities to $v1Entities in trait

    private function saveProductFields(array $params): void
    {
        if (! $this->isActive()) {
            return;
        }

        $productId = (int) ($params['id_product'] ?? $params['object']?->id ?? 0);

        if ($productId <= 0) {
            return;
        }

        try {
            AcfServiceContainer::loadCustomFieldTypes();

            $valueHandler = AcfServiceContainer::getValueHandler();
            $fieldRepository = AcfServiceContainer::getFieldRepository();
            $fileUploadService = AcfServiceContainer::getFileUploadService();
            $languages = Language::getLanguages(true);
            $langIds = array_column($languages, 'id_lang');
            $shopId = (int) $this->context->shop->id;

            $values = [];
            $translatableValues = [];
            $processedSlugs = [];

            // =========================================================================
            // 1. Process Gallery & Files fields (existing items + new uploads)
            // =========================================================================
            $this->processMultiMediaFields($_POST, $_FILES, $fieldRepository, $fileUploadService, $productId, $shopId, $values, $processedSlugs);

            // =========================================================================
            // 2. Process Image & Video fields (complex multi-input)
            // =========================================================================
            $this->processSingleMediaFields($_POST, $_FILES, $fieldRepository, $fileUploadService, $productId, $shopId, $values, $processedSlugs);

            // =========================================================================
            // 3. Process simple file uploads (single file input)
            // =========================================================================
            foreach ($_FILES as $key => $file) {
                if (! str_starts_with($key, 'acf_')) {
                    continue;
                }
                $slug = substr($key, 4);

                // Skip if already processed or if it's a special suffix (e.g., _new, _alt, _poster)
                if (isset($processedSlugs[$slug]) || preg_match('/_(?:new|alt|poster|replace)$/i', $key)) {
                    continue;
                }

                // Check for upload error - handle both single files and arrays
                $hasFile = is_array($file['error'])
                    ? ! empty(array_filter($file['error'], fn ($e) => $e === UPLOAD_ERR_OK))
                    : $file['error'] === UPLOAD_ERR_OK;

                if (! $hasFile) {
                    continue;
                }

                $field = $fieldRepository->findBySlug($slug);

                if (! $field) {
                    continue;
                }

                $fieldId = (int) $field['id_wepresta_acf_field'];
                $type = in_array($field['type'], ['image', 'gallery'], true) ? 'images' : 'files';

                try {
                    $uploadResult = $fileUploadService->upload($file, $fieldId, $productId, $shopId, $type);
                    $values[$slug] = $uploadResult;
                    $processedSlugs[$slug] = true;
                } catch (Exception $e) {
                    $this->log('File upload failed for ' . $slug . ': ' . $e->getMessage(), 2);
                }
            }

            // =========================================================================
            // 4. Process regular POST values (text, select, etc.)
            // =========================================================================
            foreach ($_POST as $key => $value) {
                if (! str_starts_with($key, 'acf_')) {
                    continue;
                }

                $keyWithoutPrefix = substr($key, 4);

                // Skip special suffixes for media fields (image, video, gallery, files)
                // These suffixes are used for sub-inputs of media fields, not the main field value
                // Note: Do NOT skip simple field names that happen to end with these words
                if (preg_match('/_(items|new_\d+|title|delete|link_url|url_mode|link_mode|attachment|alt|poster|poster_url|url_alt|replace|delete_alt|delete_poster)(?:\[\d*\])?$/i', $key)) {
                    continue;
                }

                // Skip media URL import inputs (acf_fieldslug_url with _url_mode sibling)
                if (preg_match('/_url$/i', $key) && isset($_POST[$key . '_mode'])) {
                    continue;
                }

                // Check for translatable field (ends with _N)
                if (preg_match('/^(.+)_(\d+)$/', $keyWithoutPrefix, $matches)) {
                    $slug = $matches[1];
                    $langId = (int) $matches[2];

                    if (in_array($langId, $langIds, true)) {
                        $field = $fieldRepository->findBySlug($slug);

                        if ($field && (bool) $field['translatable']) {
                            // For richtext translatable fields, get raw HTML from POST
                            if ($field['type'] === 'richtext') {
                                $rawValue = $_POST[$key] ?? $value;
                                $translatableValues[$slug][$langId] = $rawValue;
                            } else {
                                $translatableValues[$slug][$langId] = $value;
                            }

                            continue;
                        }
                    }
                }

                // Skip if already processed (gallery, files, image, video)
                if (isset($processedSlugs[$keyWithoutPrefix])) {
                    continue;
                }

                // For richtext fields, preserve raw HTML - don't let PrestaShop clean it
                $field = $fieldRepository->findBySlug($keyWithoutPrefix);

                if ($field && $field['type'] === 'richtext') {
                    // Get raw value from POST to avoid any PrestaShop cleaning
                    $rawValue = $_POST[$key] ?? $value;
                    $values[$keyWithoutPrefix] = $rawValue;
                } else {
                    $values[$keyWithoutPrefix] = $value;
                }
            }

            // =========================================================================
            // 5. Save all values
            // =========================================================================
            $valueHandler->saveProductFieldValues($productId, $values, $shopId);

            foreach ($translatableValues as $slug => $langValues) {
                foreach ($langValues as $langId => $value) {
                    $valueHandler->saveFieldValue($productId, $slug, $value, $shopId, $langId);
                }
            }
        } catch (Exception $e) {
            $this->log('Error saving product fields: ' . $e->getMessage(), 3);
        }
    }

    /**
     * Process Gallery & Files fields (multi-item with existing + new uploads).
     */
    private function processMultiMediaFields(array $post, array $files, $fieldRepository, $fileUploadService, int $productId, int $shopId, array &$values, array &$processedSlugs): void
    {
        // Find all _items[] keys to identify gallery/files fields
        foreach ($post as $key => $val) {
            if (! str_starts_with($key, 'acf_') || ! str_ends_with($key, '_items')) {
                continue;
            }

            // Extract slug: acf_{slug}_items -> {slug}
            $slug = substr($key, 4, -6);

            if (isset($processedSlugs[$slug])) {
                continue;
            }

            $field = $fieldRepository->findBySlug($slug);

            if (! $field || ! in_array($field['type'], ['gallery', 'files'], true)) {
                continue;
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $type = $field['type'] === 'gallery' ? 'images' : 'files';
            $items = [];

            // 1. Parse existing items from hidden JSON inputs
            $existingItems = $post[$key] ?? [];

            if (is_array($existingItems)) {
                foreach ($existingItems as $idx => $jsonItem) {
                    $item = is_string($jsonItem) ? json_decode($jsonItem, true) : $jsonItem;

                    if (! is_array($item) || empty($item['url'])) {
                        continue;
                    }
                    // Update title/description if provided
                    $titleKey = 'acf_' . $slug . '_title';

                    if (isset($post[$titleKey][$idx])) {
                        $item['title'] = $post[$titleKey][$idx];
                    }
                    $descKey = 'acf_' . $slug . '_desc';

                    if (isset($post[$descKey][$idx])) {
                        $item['description'] = $post[$descKey][$idx];
                    }
                    $item['position'] = count($items);
                    $items[] = $item;
                }
            }

            // 2. Upload new files
            $newFilesKey = 'acf_' . $slug . '_new';

            if (isset($files[$newFilesKey]) && is_array($files[$newFilesKey]['name'])) {
                $count = count($files[$newFilesKey]['name']);

                for ($i = 0; $i < $count; ++$i) {
                    if ($files[$newFilesKey]['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $singleFile = [
                        'name' => $files[$newFilesKey]['name'][$i],
                        'type' => $files[$newFilesKey]['type'][$i],
                        'tmp_name' => $files[$newFilesKey]['tmp_name'][$i],
                        'error' => $files[$newFilesKey]['error'][$i],
                        'size' => $files[$newFilesKey]['size'][$i],
                    ];

                    try {
                        $uploaded = $fileUploadService->upload($singleFile, $fieldId, $productId, $shopId, $type);
                        $uploaded['position'] = count($items);
                        $items[] = $uploaded;
                    } catch (Exception $e) {
                        $this->log('Gallery upload failed: ' . $e->getMessage(), 2);
                    }
                }
            }

            $values[$slug] = ! empty($items) ? $items : null;
            $processedSlugs[$slug] = true;
        }
    }

    /**
     * Process Image & Video fields (single item with multiple sub-inputs).
     */
    private function processSingleMediaFields(array $post, array $files, $fieldRepository, $fileUploadService, int $productId, int $shopId, array &$values, array &$processedSlugs): void
    {
        // Collect all field slugs that have media-related keys
        $mediaFields = [];
        $suffixes = ['_delete', '_url', '_link_url', '_url_mode', '_link_mode', '_attachment', '_alt', '_poster', '_poster_url', '_url_alt', '_replace', '_delete_alt', '_delete_poster', '_title'];

        // Check POST keys for suffixed inputs
        foreach (array_keys($post) as $key) {
            if (! str_starts_with($key, 'acf_')) {
                continue;
            }

            foreach ($suffixes as $suffix) {
                if (str_ends_with($key, $suffix)) {
                    $slug = substr($key, 4, -strlen($suffix));
                    $mediaFields[$slug] = true;

                    break;
                }
            }
        }

        // Also check FILES for direct uploads (acf_{slug} without suffix)
        foreach (array_keys($files) as $key) {
            if (! str_starts_with($key, 'acf_')) {
                continue;
            }

            // Skip multi-media fields (handled elsewhere)
            if (str_ends_with($key, '_new')) {
                continue;
            }
            // Skip suffixed keys
            $hasSuffix = false;

            foreach ($suffixes as $suffix) {
                if (str_ends_with($key, $suffix)) {
                    $hasSuffix = true;

                    break;
                }
            }

            if ($hasSuffix) {
                continue;
            }

            $slug = substr($key, 4);
            $field = $fieldRepository->findBySlug($slug);

            if ($field && in_array($field['type'], ['image', 'video', 'file'], true)) {
                $mediaFields[$slug] = true;
            }
        }

        foreach (array_keys($mediaFields) as $slug) {
            if (isset($processedSlugs[$slug])) {
                continue;
            }

            $field = $fieldRepository->findBySlug($slug);

            if (! $field || ! in_array($field['type'], ['image', 'video', 'file'], true)) {
                continue;
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $prefix = 'acf_' . $slug;

            // Check for delete flag
            $deleteKey = $prefix . '_delete';

            if (! empty($post[$deleteKey]) && $post[$deleteKey] === '1') {
                $values[$slug] = null;
                $processedSlugs[$slug] = true;

                continue;
            }

            // Get existing value to merge updates
            $valueProvider = AcfServiceContainer::getValueProvider();
            $existingValue = $valueProvider->getProductFieldValues($productId, $shopId)[$slug] ?? null;

            if ($field['type'] === 'image') {
                $values[$slug] = $this->processImageField($prefix, $post, $files, $fieldId, $productId, $shopId, $fileUploadService, $existingValue);
            } elseif ($field['type'] === 'video') {
                $values[$slug] = $this->processVideoField($prefix, $post, $files, $fieldId, $productId, $shopId, $fileUploadService, $existingValue);
            } else {
                // file type
                $values[$slug] = $this->processFileField($prefix, $post, $files, $fieldId, $productId, $shopId, $fileUploadService, $existingValue);
            }

            $processedSlugs[$slug] = true;
        }
    }

    private function processFileField(string $prefix, array $post, array $files, int $fieldId, int $productId, int $shopId, $fileUploadService, mixed $existing): ?array
    {
        $result = is_array($existing) ? $existing : [];
        $key = $prefix;

        // Handle new file upload
        if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
            try {
                $result = $fileUploadService->upload($files[$key], $fieldId, $productId, $shopId, 'files');
            } catch (Exception $e) {
                $this->log('File upload failed: ' . $e->getMessage(), 2);
            }
        }

        // Update title/description if provided
        $titleKey = $prefix . '_title';

        if (isset($post[$titleKey]) && ! empty($result)) {
            $result['title'] = $post[$titleKey];
        }
        $descKey = $prefix . '_description';

        if (isset($post[$descKey]) && ! empty($result)) {
            $result['description'] = $post[$descKey];
        }

        return ! empty($result) ? $result : null;
    }

    private function processImageField(string $prefix, array $post, array $files, int $fieldId, int $productId, int $shopId, $fileUploadService, mixed $existing): ?array
    {
        $result = is_array($existing) ? $existing : [];
        $key = $prefix; // Direct file upload key

        // 1. Handle new file upload
        if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
            try {
                $result = $fileUploadService->upload($files[$key], $fieldId, $productId, $shopId, 'images');
            } catch (Exception $e) {
                $this->log('Image upload failed: ' . $e->getMessage(), 2);
            }
        }

        // 2. Handle URL import (downloads to server)
        $urlKey = $prefix . '_url';
        $urlModeKey = $prefix . '_url_mode';

        if (! empty($post[$urlKey]) && ($post[$urlModeKey] ?? '') === 'import') {
            try {
                $result = $fileUploadService->downloadFromUrl($post[$urlKey], $fieldId, $productId, $shopId, 'images');
            } catch (Exception $e) {
                $this->log('Image URL import failed: ' . $e->getMessage(), 2);
            }
        }

        // 3. Handle external link (stores URL as-is)
        $linkKey = $prefix . '_link_url';
        $linkModeKey = $prefix . '_link_mode';

        if (! empty($post[$linkKey]) && ($post[$linkModeKey] ?? '') === 'link') {
            $result = [
                'url' => $post[$linkKey],
                'external' => true,
                'source' => 'external_link',
            ];
        }

        // 4. Handle attachment selection
        $attachKey = $prefix . '_attachment';

        if (! empty($post[$attachKey])) {
            $attachId = (int) $post[$attachKey];

            /** @var Attachment $attachment */
            $attachment = new Attachment($attachId);

            if (Validate::isLoadedObject($attachment)) {
                $link = $this->context->link ?? Context::getContext()->link;
                $baseUrl = $link->getBaseLink();
                $result = [
                    'url' => $baseUrl . 'download?id_attachment=' . $attachId,
                    'attachment_id' => $attachId,
                    'original_name' => $attachment->name[$this->context->language->id] ?? $attachment->file_name,
                    'source' => 'attachment',
                ];
            }
        }

        // 5. Update title if provided
        $titleKey = $prefix . '_title';

        if (isset($post[$titleKey]) && ! empty($result)) {
            $result['title'] = $post[$titleKey];
        }

        return ! empty($result) ? $result : null;
    }

    private function processVideoField(string $prefix, array $post, array $files, int $fieldId, int $productId, int $shopId, $fileUploadService, mixed $existing): ?array
    {
        $result = is_array($existing) ? $existing : [];

        // Check delete flags for sub-parts
        if (! empty($post[$prefix . '_delete_alt']) && $post[$prefix . '_delete_alt'] === '1') {
            unset($result['sources']);
        }

        if (! empty($post[$prefix . '_delete_poster']) && $post[$prefix . '_delete_poster'] === '1') {
            unset($result['poster_url']);
        }

        // 1. Handle main video file upload or replacement
        $mainFileKey = $prefix;
        $replaceKey = $prefix . '_replace';
        $fileKeyToUse = isset($files[$replaceKey]) && $files[$replaceKey]['error'] === UPLOAD_ERR_OK ? $replaceKey : $mainFileKey;

        if (isset($files[$fileKeyToUse]) && $files[$fileKeyToUse]['error'] === UPLOAD_ERR_OK) {
            try {
                $uploaded = $fileUploadService->upload($files[$fileKeyToUse], $fieldId, $productId, $shopId, 'videos');
                $result = array_merge($result, $uploaded, ['source' => 'upload']);
            } catch (Exception $e) {
                $this->log('Video upload failed: ' . $e->getMessage(), 2);
            }
        }

        // 2. Handle alt video upload (WebM/Ogg)
        $altKey = $prefix . '_alt';

        if (isset($files[$altKey]) && $files[$altKey]['error'] === UPLOAD_ERR_OK) {
            try {
                $altUploaded = $fileUploadService->upload($files[$altKey], $fieldId, $productId, $shopId, 'videos');
                $result['sources'] = [
                    ['url' => $altUploaded['url'], 'mime' => $altUploaded['mime'] ?? 'video/webm'],
                ];
            } catch (Exception $e) {
                $this->log('Alt video upload failed: ' . $e->getMessage(), 2);
            }
        }

        // 3. Handle poster image upload
        $posterKey = $prefix . '_poster';

        if (isset($files[$posterKey]) && $files[$posterKey]['error'] === UPLOAD_ERR_OK) {
            try {
                $posterUploaded = $fileUploadService->upload($files[$posterKey], $fieldId, $productId, $shopId, 'images');
                $result['poster_url'] = $posterUploaded['url'];
            } catch (Exception $e) {
                $this->log('Poster upload failed: ' . $e->getMessage(), 2);
            }
        }

        // 4. Handle external video URL (YouTube/Vimeo/direct)
        $urlKey = $prefix . '_url';

        if (! empty($post[$urlKey]) && empty($result['url'])) {
            $url = $post[$urlKey];

            // Parse YouTube
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $m)) {
                $result = [
                    'source' => 'youtube',
                    'video_id' => $m[1],
                    'url' => $url,
                ];
            // Parse Vimeo
            } elseif (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]+\/)?videos\/|video\/|)(\d+)/i', $url, $m)) {
                $result = [
                    'source' => 'vimeo',
                    'video_id' => $m[1],
                    'url' => $url,
                ];
            // Direct video URL
            } else {
                $result = [
                    'source' => 'external',
                    'url' => $url,
                    'mime' => $this->getMimeFromUrl($url),
                ];
            }
        }

        // 5. Handle alt URL
        $urlAltKey = $prefix . '_url_alt';

        if (! empty($post[$urlAltKey])) {
            $result['sources'] = [
                ['url' => $post[$urlAltKey], 'mime' => $this->getMimeFromUrl($post[$urlAltKey])],
            ];
        }

        // 6. Handle poster URL
        $posterUrlKey = $prefix . '_poster_url';

        if (! empty($post[$posterUrlKey])) {
            $result['poster_url'] = $post[$posterUrlKey];
        }

        // 7. Metadata
        if (isset($post[$prefix . '_title'])) {
            $result['title'] = $post[$prefix . '_title'];
        }

        if (isset($post[$prefix . '_description'])) {
            $result['description'] = $post[$prefix . '_description'];
        }

        return ! empty($result) ? $result : null;
    }

    private function getMimeFromUrl(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        return match ($ext) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg', 'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            default => 'video/mp4',
        };
    }

    private function handleFileDownload(): void
    {
        $path = Tools::getValue('acf_download');
        $file = $this->getLocalPath() . 'uploads/' . pSQL($path);

        if (! file_exists($file) || ! is_readable($file)) {
            header('HTTP/1.0 404 Not Found');

            exit('File not found');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file) ?: 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);

        exit;
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * Get the base URL for API endpoints (Symfony routes).
     */
    private function getAdminApiBaseUrl(): string
    {
        // Try to use Symfony router
        try {
            $container = $this->getContainer();

            if ($container && $container->has('router')) {
                $router = $container->get('router');
                // Generate URL to a known route and extract base
                $url = $router->generate('wepresta_acf_api_relation_search');

                // Remove the /relation/search part to get base URL
                return preg_replace('/\/relation\/search$/', '', $url);
            }
        } catch (Exception $e) {
            // Fallback
        }

        // Fallback: build URL manually from admin link
        $adminUrl = $this->context->link->getAdminLink('AdminModules', true);
        // Extract base admin URL (before query string)
        $baseAdmin = preg_replace('/\?.*$/', '', $adminUrl);
        // Replace controller path with Symfony route path
        $baseAdmin = preg_replace('/\/sell\/.*$/', '', $baseAdmin);
        $baseAdmin = preg_replace('/\/configure\/.*$/', '', $baseAdmin);

        return rtrim($baseAdmin, '/') . '/modules/wepresta_acf/api';
    }
}

if (! class_exists('wepresta_acf', false)) {
    class_alias('WeprestaAcf', 'wepresta_acf');
}
