<?php
/**
 * WePresta ACF - Advanced Custom Fields for PrestaShop 8.x / 9.x
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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/autoload.php';

use WeprestaAcf\Application\Installer\ModuleInstaller;
use WeprestaAcf\Application\Installer\ModuleUninstaller;
use WeprestaAcf\Application\Service\AcfServiceContainer;
use WeprestaAcf\Infrastructure\Adapter\ConfigurationAdapter;

class WeprestaAcf extends Module
{
    public const VERSION = '1.0.0';

    public const HOOKS = [
        // Product Admin
        'displayAdminProductsExtra',
        'actionProductUpdate',
        'actionProductAdd',
        'actionAdminControllerSetMedia',
        // Front-Office
        'displayProductAdditionalInfo',
        'actionFrontControllerSetMedia',
        'displayHeader',
    ];

    public const DEFAULT_CONFIG = [
        'WEPRESTA_ACF_ACTIVE' => true,
        'WEPRESTA_ACF_MAX_FILE_SIZE' => 10485760, // 10MB
        'WEPRESTA_ACF_DEBUG' => false,
    ];

    private ?ConfigurationAdapter $config = null;

    public function __construct()
    {
        $this->name = 'wepresta_acf';
        $this->tab = 'administration';
        $this->version = self::VERSION;
        $this->author = 'Bruno Studer';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('WePresta ACF', [], 'Modules.WeprestaAcf.Admin');
        $this->description = $this->trans('Advanced Custom Fields for PrestaShop - ACF-style field builder', [], 'Modules.WeprestaAcf.Admin');
        $this->confirmUninstall = $this->trans('Delete all custom fields and values?', [], 'Modules.WeprestaAcf.Admin');

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
            return parent::install() && $this->registerHook(self::HOOKS) && $installer->install();
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }
    }

    public function uninstall(): bool
    {
        try {
            $uninstaller = new ModuleUninstaller($this, Db::getInstance());
            return $uninstaller->uninstall() && parent::uninstall();
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
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
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Configuration error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        return '';
    }

    // =========================================================================
    // ADMIN PRODUCT HOOKS
    // =========================================================================

    public function hookDisplayAdminProductsExtra(array $params): string
    {
        if (!$this->isActive()) { return ''; }

        $productId = (int) ($params['id_product'] ?? 0);
        if ($productId <= 0) { return ''; }

        try {
            $groupRepository = AcfServiceContainer::getGroupRepository();
            $fieldRepository = AcfServiceContainer::getFieldRepository();
            $valueProvider = AcfServiceContainer::getValueProvider();
            $fieldTypeRegistry = AcfServiceContainer::getFieldTypeRegistry();

            $groups = $groupRepository->findActiveGroups((int) $this->context->shop->id);
            if (empty($groups)) { return ''; }

            $languages = Language::getLanguages(true);
            $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
            $currentLangId = (int) $this->context->language->id;

            // Get values for current language (non-translatable fields)
            $values = $valueProvider->getProductFieldValues($productId, null, $currentLangId);

            // Get values for all languages (translatable fields)
            $valuesPerLang = [];
            foreach ($languages as $lang) {
                $valuesPerLang[(int) $lang['id_lang']] = $valueProvider->getProductFieldValues($productId, null, (int) $lang['id_lang']);
            }

            $groupsData = [];
            foreach ($groups as $group) {
                $groupId = (int) $group['id_wepresta_acf_group'];
                $fields = $fieldRepository->findByGroup($groupId);

                $fieldsHtml = [];
                foreach ($fields as $field) {
                    $slug = $field['slug'];
                    $type = $field['type'];
                    $isTranslatable = (bool) $field['translatable'];
                    $fieldType = $fieldTypeRegistry->getOrNull($type);

                    if (!$fieldType) {
                        continue;
                    }

                    $fieldData = [
                        'slug' => $slug,
                        'title' => $field['title'],
                        'instructions' => $field['instructions'],
                        'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                        'translatable' => $isTranslatable,
                    ];

                    if ($isTranslatable) {
                        // Render field for each language
                        $langInputs = [];
                        foreach ($languages as $lang) {
                            $langId = (int) $lang['id_lang'];
                            $langValue = $valuesPerLang[$langId][$slug] ?? null;
                            $langInputs[] = [
                                'id_lang' => $langId,
                                'iso_code' => $lang['iso_code'],
                                'name' => $lang['name'],
                                'is_default' => $langId === $defaultLangId,
                                'html' => $fieldType->renderAdminInput($field, $langValue, [
                                    'prefix' => 'acf_',
                                    'suffix' => '_' . $langId,
                                ]),
                            ];
                        }
                        $fieldData['lang_inputs'] = $langInputs;
                        $fieldData['html'] = ''; // Will be built in template
                    } else {
                        $value = $values[$slug] ?? null;
                        $fieldData['html'] = $fieldType->renderAdminInput($field, $value, ['prefix' => 'acf_']);
                        $fieldData['lang_inputs'] = [];
                    }

                    $fieldsHtml[] = $fieldData;
                }

                $groupsData[] = [
                    'id' => $groupId,
                    'title' => $group['title'],
                    'description' => $group['description'],
                    'fields' => $fieldsHtml,
                ];
            }

            $this->context->smarty->assign([
                'acf_groups' => $groupsData,
                'acf_product_id' => $productId,
                'acf_languages' => $languages,
                'acf_default_lang' => $defaultLangId,
            ]);

            return $this->fetch('module:wepresta_acf/views/templates/admin/product-fields.tpl');
        } catch (\Exception $e) {
            $this->log('Error in hookDisplayAdminProductsExtra: ' . $e->getMessage(), 3);
            return '<div class="alert alert-danger">ACF Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public function hookActionProductUpdate(array $params): void
    {
        $this->saveProductFields($params);
    }

    public function hookActionProductAdd(array $params): void
    {
        $this->saveProductFields($params);
    }

    private function saveProductFields(array $params): void
    {
        if (!$this->isActive()) { return; }

        $productId = (int) ($params['id_product'] ?? $params['object']?->id ?? 0);
        if ($productId <= 0) { return; }

        try {
            $valueHandler = AcfServiceContainer::getValueHandler();
            $fieldRepository = AcfServiceContainer::getFieldRepository();
            $fileUploadService = AcfServiceContainer::getFileUploadService();
            $languages = Language::getLanguages(true);
            $shopId = (int) $this->context->shop->id;

            // Collect values from $_POST
            // Format: acf_slug (non-translatable) or acf_slug_langId (translatable)
            $values = [];
            $translatableValues = []; // [slug => [langId => value]]

            foreach ($_POST as $key => $value) {
                if (!str_starts_with($key, 'acf_')) {
                    continue;
                }

                $keyWithoutPrefix = substr($key, 4); // Remove 'acf_'

                // Check if this is a language-specific key (ends with _N where N is a number)
                if (preg_match('/^(.+)_(\d+)$/', $keyWithoutPrefix, $matches)) {
                    $slug = $matches[1];
                    $langId = (int) $matches[2];

                    // Verify this is actually a language ID
                    $isLangId = false;
                    foreach ($languages as $lang) {
                        if ((int) $lang['id_lang'] === $langId) {
                            $isLangId = true;
                            break;
                        }
                    }

                    if ($isLangId) {
                        // This is a translatable field value
                        $field = $fieldRepository->findBySlug($slug);
                        if ($field && (bool) $field['translatable']) {
                            $translatableValues[$slug][$langId] = $value;
                            continue;
                        }
                    }
                }

                // Non-translatable field or field slug that happens to end with a number
                $values[$keyWithoutPrefix] = $value;
            }

            // Handle file uploads
            foreach ($_FILES as $key => $file) {
                if (!str_starts_with($key, 'acf_') || $file['error'] !== UPLOAD_ERR_OK) { continue; }
                $slug = substr($key, 4);
                $field = $fieldRepository->findBySlug($slug);
                if (!$field) { continue; }

                $fieldId = (int) $field['id_wepresta_acf_field'];
                $type = in_array($field['type'], ['image', 'gallery']) ? 'images' : 'files';
                $allowedMimes = $field['type'] === 'image' ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] : [];

                try {
                    $uploadResult = $fileUploadService->upload($file, $fieldId, $productId, $shopId, $type, $allowedMimes);
                    $values[$slug] = json_encode($uploadResult);
                } catch (\Exception $e) {
                    $this->log('File upload failed for ' . $slug . ': ' . $e->getMessage(), 2);
                }
            }

            // Save non-translatable values
            $valueHandler->saveProductFieldValues($productId, $values, $shopId);

            // Save translatable values per language
            foreach ($translatableValues as $slug => $langValues) {
                foreach ($langValues as $langId => $value) {
                    $valueHandler->saveFieldValue($productId, $slug, $value, $shopId, $langId);
                }
            }
        } catch (\Exception $e) {
            $this->log('Error saving product fields: ' . $e->getMessage(), 3);
        }
    }

    public function hookActionAdminControllerSetMedia(array $params): void
    {
        $controller = Tools::getValue('controller');
        if (in_array($controller, ['AdminProducts', 'AdminWeprestaAcfBuilder', 'AdminWeprestaAcfConfiguration'], true)) {
            $this->context->controller->addCSS($this->_path . 'views/dist/admin.css');
            $this->context->controller->addJS($this->_path . 'views/dist/admin.js');
        }
    }

    // =========================================================================
    // FRONT-OFFICE HOOKS
    // =========================================================================

    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isActive()) { return ''; }
        $this->context->controller->registerStylesheet('wepresta_acf-front', 'modules/' . $this->name . '/views/dist/front.css', ['media' => 'all', 'priority' => 150]);
        return '';
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        if (!$this->isActive()) { return ''; }

        $product = $params['product'] ?? null;
        $productId = (int) ($product['id_product'] ?? ($product->id ?? 0));
        if ($productId <= 0) { return ''; }

        try {
            $valueProvider = AcfServiceContainer::getValueProvider();
            $fieldTypeRegistry = AcfServiceContainer::getFieldTypeRegistry();

            $fields = $valueProvider->getProductFieldValuesWithMeta($productId);
            if (empty($fields)) { return ''; }

            // Filter fields with show_on_front enabled
            $displayFields = [];
            foreach ($fields as $field) {
                $foOptions = $field['fo_options'] ?? [];
                if (!($foOptions['show_on_front'] ?? true)) { continue; }
                if ($field['value'] === null || $field['value'] === '') { continue; }

                $fieldType = $fieldTypeRegistry->getOrNull($field['type']);
                $renderedValue = $fieldType ? $fieldType->renderValue($field['value'], $field['config'], $foOptions) : htmlspecialchars((string) $field['value']);

                $displayFields[] = [
                    'slug' => $field['slug'],
                    'title' => $field['title'],
                    'type' => $field['type'],
                    'value' => $field['value'],
                    'rendered' => $renderedValue,
                    'fo_options' => $foOptions,
                ];
            }

            if (empty($displayFields)) { return ''; }

            $this->context->smarty->assign([
                'acf_fields' => $displayFields,
                'acf_product_id' => $productId,
            ]);

            return $this->fetch('module:wepresta_acf/views/templates/hook/product-info.tpl');
        } catch (\Exception $e) {
            $this->log('Error in hookDisplayProductAdditionalInfo: ' . $e->getMessage(), 3);
            return '';
        }
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (!$this->isActive()) { return; }

        $controller = Tools::getValue('controller');
        if ($controller === 'product') {
            $this->context->controller->registerJavascript('wepresta_acf-front', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 150]);
        }

        // Handle file downloads
        if (Tools::getValue('acf_download')) {
            $this->handleFileDownload();
        }
    }

    private function handleFileDownload(): void
    {
        $path = Tools::getValue('acf_download');
        $file = $this->getLocalPath() . 'uploads/' . pSQL($path);

        if (!file_exists($file) || !is_readable($file)) {
            header('HTTP/1.0 404 Not Found');
            exit('File not found');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
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

    public function isActive(): bool { return (bool) Configuration::get('WEPRESTA_ACF_ACTIVE'); }
    public function getConfig(): ConfigurationAdapter { return $this->config ??= new ConfigurationAdapter(); }

    public function getService(string $serviceId): ?object
    {
        try { return $this->getContainer()?->get($serviceId); }
        catch (\Exception $e) { return null; }
    }

    public function log(string $message, int $severity = 1, array $context = []): void
    {
        $msg = '[' . $this->name . '] ' . $message;
        if (!empty($context)) { $msg .= ' | ' . json_encode($context); }
        PrestaShopLogger::addLog($msg, $severity, null, 'Module', $this->id);
    }

    public function clearModuleCache(): void { $this->_clearCache('*'); }
    public function getModulePath(): string { return $this->getLocalPath(); }
}

if (!class_exists('wepresta_acf', false)) { class_alias('WeprestaAcf', 'wepresta_acf'); }
