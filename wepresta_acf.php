<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
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
        'WEPRESTA_ACF_MAX_FILE_SIZE' => 10485760,
        'WEPRESTA_ACF_DEBUG' => false,
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
        $this->description = $this->trans('Advanced Custom Fields for PrestaShop with CPT support', [], 'Modules.Weprestaacf.Admin');
        $this->confirmUninstall = $this->trans('Delete all custom fields and values?', [], 'Modules.Weprestaacf.Admin');

        AcfServiceContainer::init($this);
        AcfServiceContainer::setModuleVersion(self::VERSION);
    }

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
            $result = $uninstaller->uninstall() && parent::uninstall();

            if ($result) {
                $this->clearSymfonyCache();
            }

            return $result;
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }
    }

    private function clearSymfonyCache(): void
    {
        try {
            if (class_exists('\PrestaShop\PrestaShop\Adapter\SymfonyContainer')) {
                $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();

                if ($container && $container->has('cache.clearer')) {
                    $cacheClearer = $container->get('cache.clearer');
                    if (method_exists($cacheClearer, 'clear')) {
                        $cacheClearer->clear('');
                    }
                }
            }

            $rootDir = defined('_PS_ROOT_DIR_') ? _PS_ROOT_DIR_ : dirname(_PS_ADMIN_DIR_);
            $cacheDir = $rootDir . '/var/cache';

            if (is_dir($cacheDir)) {
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
            // Silent fail
        }
    }

    public function getContent(): string
    {
        try {
            $router = $this->getContainer()->get('router');
            Tools::redirectAdmin($router->generate('wepresta_acf_builder'));
        } catch (Exception $e) {
            return '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        return '';
    }

    public function hookActionAdminControllerSetMedia(array $params): void
    {
        $controller = Tools::getValue('controller');

        if (in_array($controller, ['AdminWeprestaAcfBuilder', 'AdminWeprestaAcfConfiguration', 'AdminWeprestaAcfSync'], true)) {
            if (file_exists($this->getLocalPath() . 'views/dist/admin.css')) {
                $this->context->controller->addCSS($this->_path . 'views/dist/admin.css');
            }

            if (file_exists($this->getLocalPath() . 'views/dist/admin.js')) {
                $this->context->controller->addJS($this->_path . 'views/dist/admin.js');
            }
        }

        $this->context->controller->addCSS($this->_path . 'views/css/admin-fields.css');
        $this->context->controller->addJS($this->_path . 'views/js/acf-fields.js');
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $controller = Tools::getValue('controller');
        $v1Controllers = ['product', 'category'];

        if (in_array($controller, $v1Controllers, true)) {
            $this->context->controller->registerJavascript('wepresta_acf-front', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 150]);
        }

        $this->context->controller->registerStylesheet(
            'wepresta_acf-front-css',
            'modules/' . $this->name . '/views/css/acf-front.css',
            ['priority' => 150]
        );
    }

    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        try {
            AcfServiceContainer::loadCustomFieldTypes();
            $acfWrapper = AcfServiceContainer::getSmartyWrapper();
            $this->context->smarty->assign('acf', $acfWrapper);
            $this->registerSmartyPlugins();
        } catch (Exception $e) {
            $this->log('ACF header initialization failed: ' . $e->getMessage(), 2);
        }

        return '';
    }

    public function hookFilterCmsContent(array $params): array
    {
        if (!$this->isActive()) {
            return $params;
        }

        try {
            $content = $params['content'] ?? '';

            if (strpos($content, '[acf') !== false) {
                $parser = AcfServiceContainer::getShortcodeParser();
                $cmsId = (int) Tools::getValue('id_cms', 0);
                $entityType = $cmsId > 0 ? 'cms_page' : null;
                $params['content'] = $parser->parse($content, $entityType, $cmsId > 0 ? $cmsId : null);
            }
        } catch (Exception $e) {
            $this->log('Shortcode parsing failed: ' . $e->getMessage(), 2);
        }

        return $params;
    }

    public function hookFilterCategoryContent(array $params): array
    {
        if (!$this->isActive()) {
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
            $this->log('Shortcode parsing failed: ' . $e->getMessage(), 2);
        }

        return $params;
    }

    public function hookFilterProductContent(array $params): array
    {
        if (!$this->isActive()) {
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
            $this->log('Shortcode parsing failed: ' . $e->getMessage(), 2);
        }

        return $params;
    }

    public function hookModuleRoutes(): array
    {
        try {
            $cptTypeService = AcfServiceContainer::getTypeService();

            if (!$cptTypeService) {
                return [];
            }

            $types = $cptTypeService->getActiveTypes();
            $routes = [];

            foreach ($types as $type) {
                $urlPrefix = $type->getUrlPrefix();
                $typeSlug = $type->getSlug();

                if ($type->hasArchive()) {
                    $routes["module-wepresta_acf-cpt-{$typeSlug}-archive"] = [
                        'controller' => 'cptarchive',
                        'rule' => $urlPrefix,
                        'keywords' => ['type' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'type']],
                        'params' => ['fc' => 'module', 'module' => 'wepresta_acf', 'controller' => 'cptarchive', 'type' => $typeSlug],
                    ];
                }

                $routes["module-wepresta_acf-cpt-{$typeSlug}-single"] = [
                    'controller' => 'cptsingle',
                    'rule' => $urlPrefix . '/{slug}',
                    'keywords' => [
                        'slug' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'slug'],
                        'type' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'type'],
                    ],
                    'params' => ['fc' => 'module', 'module' => 'wepresta_acf', 'controller' => 'cptsingle', 'type' => $typeSlug],
                ];
            }

            return $routes;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function saveProductFields(array $params): void
    {
        if (!$this->isActive()) {
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

            $this->processMultiMediaFields($_POST, $_FILES, $fieldRepository, $fileUploadService, $productId, $shopId, $values, $processedSlugs);
            $this->processSingleMediaFields($_POST, $_FILES, $fieldRepository, $fileUploadService, $productId, $shopId, $values, $processedSlugs);

            foreach ($_FILES as $key => $file) {
                if (!str_starts_with($key, 'acf_')) {
                    continue;
                }
                $slug = substr($key, 4);

                if (isset($processedSlugs[$slug]) || preg_match('/_(?:new|alt|poster|replace)$/i', $key)) {
                    continue;
                }

                $hasFile = is_array($file['error'])
                    ? !empty(array_filter($file['error'], fn($e) => $e === UPLOAD_ERR_OK))
                    : $file['error'] === UPLOAD_ERR_OK;

                if (!$hasFile) {
                    continue;
                }

                $field = $fieldRepository->findBySlug($slug);

                if (!$field) {
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

            foreach ($_POST as $key => $value) {
                if (!str_starts_with($key, 'acf_')) {
                    continue;
                }

                $keyWithoutPrefix = substr($key, 4);

                if (preg_match('/_(items|new_\d+|title|delete|link_url|url_mode|link_mode|attachment|alt|poster|poster_url|url_alt|replace|delete_alt|delete_poster)(?:\[\d*\])?$/i', $key)) {
                    continue;
                }

                if (preg_match('/_url$/i', $key) && isset($_POST[$key . '_mode'])) {
                    continue;
                }

                if (preg_match('/^(.+)_(\d+)$/', $keyWithoutPrefix, $matches)) {
                    $slug = $matches[1];
                    $langId = (int) $matches[2];

                    if (in_array($langId, $langIds, true)) {
                        $field = $fieldRepository->findBySlug($slug);

                        if ($field && (bool) $field['translatable']) {
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

                if (isset($processedSlugs[$keyWithoutPrefix])) {
                    continue;
                }

                $field = $fieldRepository->findBySlug($keyWithoutPrefix);

                if ($field && $field['type'] === 'richtext') {
                    $rawValue = $_POST[$key] ?? $value;
                    $values[$keyWithoutPrefix] = $rawValue;
                } else {
                    $values[$keyWithoutPrefix] = $value;
                }
            }

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

    private function processMultiMediaFields(array $post, array $files, $fieldRepository, $fileUploadService, int $productId, int $shopId, array &$values, array &$processedSlugs): void
    {
        foreach ($post as $key => $val) {
            if (!str_starts_with($key, 'acf_') || !str_ends_with($key, '_items')) {
                continue;
            }

            $slug = substr($key, 4, -6);

            if (isset($processedSlugs[$slug])) {
                continue;
            }

            $field = $fieldRepository->findBySlug($slug);

            if (!$field || !in_array($field['type'], ['gallery', 'files'], true)) {
                continue;
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $type = $field['type'] === 'gallery' ? 'images' : 'files';
            $items = [];

            $existingItems = $post[$key] ?? [];

            if (is_array($existingItems)) {
                foreach ($existingItems as $idx => $jsonItem) {
                    $item = is_string($jsonItem) ? json_decode($jsonItem, true) : $jsonItem;

                    if (!is_array($item) || empty($item['url'])) {
                        continue;
                    }
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

            $values[$slug] = !empty($items) ? $items : null;
            $processedSlugs[$slug] = true;
        }
    }

    private function processSingleMediaFields(array $post, array $files, $fieldRepository, $fileUploadService, int $productId, int $shopId, array &$values, array &$processedSlugs): void
    {
        $mediaFields = [];
        $suffixes = ['_delete', '_url', '_link_url', '_url_mode', '_link_mode', '_attachment', '_alt', '_poster', '_poster_url', '_url_alt', '_replace', '_delete_alt', '_delete_poster', '_title'];

        foreach (array_keys($post) as $key) {
            if (!str_starts_with($key, 'acf_')) {
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

        foreach (array_keys($files) as $key) {
            if (!str_starts_with($key, 'acf_')) {
                continue;
            }

            if (str_ends_with($key, '_new')) {
                continue;
            }
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

            if (!$field || !in_array($field['type'], ['image', 'video', 'file'], true)) {
                continue;
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $prefix = 'acf_' . $slug;

            $deleteKey = $prefix . '_delete';

            if (!empty($post[$deleteKey]) && $post[$deleteKey] === '1') {
                $values[$slug] = null;
                $processedSlugs[$slug] = true;
                continue;
            }

            $valueProvider = AcfServiceContainer::getValueProvider();
            $existingValue = $valueProvider->getProductFieldValues($productId, $shopId)[$slug] ?? null;

            if ($field['type'] === 'image') {
                $values[$slug] = $this->processImageField($prefix, $post, $files, $fieldId, $productId, $shopId, $fileUploadService, $existingValue);
            } elseif ($field['type'] === 'video') {
                $values[$slug] = $this->processVideoField($prefix, $post, $files, $fieldId, $productId, $shopId, $fileUploadService, $existingValue);
            } else {
                $values[$slug] = $this->processFileField($prefix, $post, $files, $fieldId, $productId, $shopId, $fileUploadService, $existingValue);
            }

            $processedSlugs[$slug] = true;
        }
    }

    private function processFileField(string $prefix, array $post, array $files, int $fieldId, int $productId, int $shopId, $fileUploadService, mixed $existing): ?array
    {
        $result = is_array($existing) ? $existing : [];
        $key = $prefix;

        if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
            try {
                $result = $fileUploadService->upload($files[$key], $fieldId, $productId, $shopId, 'files');
            } catch (Exception $e) {
                $this->log('File upload failed: ' . $e->getMessage(), 2);
            }
        }

        $titleKey = $prefix . '_title';

        if (isset($post[$titleKey]) && !empty($result)) {
            $result['title'] = $post[$titleKey];
        }
        $descKey = $prefix . '_description';

        if (isset($post[$descKey]) && !empty($result)) {
            $result['description'] = $post[$descKey];
        }

        return !empty($result) ? $result : null;
    }

    private function processImageField(string $prefix, array $post, array $files, int $fieldId, int $productId, int $shopId, $fileUploadService, mixed $existing): ?array
    {
        $result = is_array($existing) ? $existing : [];
        $key = $prefix;

        if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
            try {
                $result = $fileUploadService->upload($files[$key], $fieldId, $productId, $shopId, 'images');
            } catch (Exception $e) {
                $this->log('Image upload failed: ' . $e->getMessage(), 2);
            }
        }

        $urlKey = $prefix . '_url';
        $urlModeKey = $prefix . '_url_mode';

        if (!empty($post[$urlKey]) && ($post[$urlModeKey] ?? '') === 'import') {
            try {
                $result = $fileUploadService->downloadFromUrl($post[$urlKey], $fieldId, $productId, $shopId, 'images');
            } catch (Exception $e) {
                $this->log('Image URL import failed: ' . $e->getMessage(), 2);
            }
        }

        $linkKey = $prefix . '_link_url';
        $linkModeKey = $prefix . '_link_mode';

        if (!empty($post[$linkKey]) && ($post[$linkModeKey] ?? '') === 'link') {
            $result = [
                'url' => $post[$linkKey],
                'external' => true,
                'source' => 'external_link',
            ];
        }

        $attachKey = $prefix . '_attachment';

        if (!empty($post[$attachKey])) {
            $attachId = (int) $post[$attachKey];
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

        $titleKey = $prefix . '_title';

        if (isset($post[$titleKey]) && !empty($result)) {
            $result['title'] = $post[$titleKey];
        }

        return !empty($result) ? $result : null;
    }

    private function processVideoField(string $prefix, array $post, array $files, int $fieldId, int $productId, int $shopId, $fileUploadService, mixed $existing): ?array
    {
        $result = is_array($existing) ? $existing : [];

        if (!empty($post[$prefix . '_delete_alt']) && $post[$prefix . '_delete_alt'] === '1') {
            unset($result['sources']);
        }

        if (!empty($post[$prefix . '_delete_poster']) && $post[$prefix . '_delete_poster'] === '1') {
            unset($result['poster_url']);
        }

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

        $altKey = $prefix . '_alt';

        if (isset($files[$altKey]) && $files[$altKey]['error'] === UPLOAD_ERR_OK) {
            try {
                $altUploaded = $fileUploadService->upload($files[$altKey], $fieldId, $productId, $shopId, 'videos');
                $result['sources'] = [['url' => $altUploaded['url'], 'mime' => $altUploaded['mime'] ?? 'video/webm']];
            } catch (Exception $e) {
                $this->log('Alt video upload failed: ' . $e->getMessage(), 2);
            }
        }

        $posterKey = $prefix . '_poster';

        if (isset($files[$posterKey]) && $files[$posterKey]['error'] === UPLOAD_ERR_OK) {
            try {
                $posterUploaded = $fileUploadService->upload($files[$posterKey], $fieldId, $productId, $shopId, 'images');
                $result['poster_url'] = $posterUploaded['url'];
            } catch (Exception $e) {
                $this->log('Poster upload failed: ' . $e->getMessage(), 2);
            }
        }

        $urlKey = $prefix . '_url';

        if (!empty($post[$urlKey]) && empty($result['url'])) {
            $url = $post[$urlKey];

            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $m)) {
                $result = ['source' => 'youtube', 'video_id' => $m[1], 'url' => $url];
            } elseif (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]+\/)?videos\/|video\/|)(\d+)/i', $url, $m)) {
                $result = ['source' => 'vimeo', 'video_id' => $m[1], 'url' => $url];
            } else {
                $result = ['source' => 'external', 'url' => $url, 'mime' => $this->getMimeFromUrl($url)];
            }
        }

        $urlAltKey = $prefix . '_url_alt';

        if (!empty($post[$urlAltKey])) {
            $result['sources'] = [['url' => $post[$urlAltKey], 'mime' => $this->getMimeFromUrl($post[$urlAltKey])]];
        }

        $posterUrlKey = $prefix . '_poster_url';

        if (!empty($post[$posterUrlKey])) {
            $result['poster_url'] = $post[$posterUrlKey];
        }

        if (isset($post[$prefix . '_title'])) {
            $result['title'] = $post[$prefix . '_title'];
        }

        if (isset($post[$prefix . '_description'])) {
            $result['description'] = $post[$prefix . '_description'];
        }

        return !empty($result) ? $result : null;
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

        if (!file_exists($file) || !is_readable($file)) {
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

    public function get($serviceId): ?object
    {
        return $this->getService($serviceId);
    }

    public function log(string $message, int $severity = 1, array $context = []): void
    {
        $msg = '[' . $this->name . '] ' . $message;
        if (!empty($context)) {
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

    private function registerSmartyPlugins(): void
    {
        $smarty = $this->context->smarty;
        $pluginsDir = __DIR__ . '/src/Application/Smarty/';
        $functions = ['acf_field', 'acf_render', 'acf_group'];

        foreach ($functions as $funcName) {
            $pluginFile = $pluginsDir . 'function.' . $funcName . '.php';
            if (file_exists($pluginFile) && !isset($smarty->registered_plugins['function'][$funcName])) {
                require_once $pluginFile;
                $smarty->registerPlugin('function', $funcName, 'smarty_function_' . $funcName);
            }
        }

        $blocks = ['acf_foreach'];
        foreach ($blocks as $blockName) {
            $pluginFile = $pluginsDir . 'block.' . $blockName . '.php';
            if (file_exists($pluginFile) && !isset($smarty->registered_plugins['block'][$blockName])) {
                require_once $pluginFile;
                $smarty->registerPlugin('block', $blockName, 'smarty_block_' . $blockName);
            }
        }
    }

    private function getAllHooks(): array
    {
        return array_merge(EntityHooksConfig::getAllHooks(), ['moduleRoutes']);
    }

    private function getAdminApiBaseUrl(): string
    {
        try {
            $container = $this->getContainer();
            if ($container && $container->has('router')) {
                $router = $container->get('router');
                $url = $router->generate('wepresta_acf_api_relation_search');
                return preg_replace('/\/relation\/search$/', '', $url);
            }
        } catch (Exception $e) {
            // Fallback
        }

        $adminUrl = $this->context->link->getAdminLink('AdminModules', true);
        $baseAdmin = preg_replace('/\?.*$/', '', $adminUrl);
        $baseAdmin = preg_replace('/\/sell\/.*$/', '', $baseAdmin);
        $baseAdmin = preg_replace('/\/configure\/.*$/', '', $baseAdmin);

        return rtrim($baseAdmin, '/') . '/modules/wepresta_acf/api';
    }
}

if (!class_exists('wepresta_acf', false)) {
    class_alias('WeprestaAcf', 'wepresta_acf');
}
