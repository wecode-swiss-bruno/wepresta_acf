<?php
/**
 * Module Starter - Template PRO pour PrestaShop 8.x / 9.x
 *
 * Architecture moderne avec:
 * - Clean Architecture (Application/Domain/Infrastructure)
 * - CQRS léger
 * - Symfony Forms & Grid
 * - Event Subscribers
 * - Doctrine Entities
 *
 * @author      Bruno Studer
 * @copyright   2024 Votre Société
 * @license     MIT
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

// Load module autoloader (handles PrestaShop vs CLI context)
require_once __DIR__ . '/autoload.php';

use WeprestaAcf\Application\Installer\ModuleInstaller;
use WeprestaAcf\Application\Installer\ModuleUninstaller;
use WeprestaAcf\Infrastructure\Adapter\ConfigurationAdapter;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class WeprestaAcf extends Module
{
    /**
     * Version du module (utilisée par les contrôleurs)
     */
    public const VERSION = '1.0.0';

    /**
     * Hooks enregistrés par le module
     */
    public const HOOKS = [
        // Front Office - Display
        'displayHeader',
        'displayHome',
        'displayFooter',
        'displayProductAdditionalInfo',
        'displayShoppingCart',
        'displayOrderConfirmation',

        // Front Office - Actions
        'actionFrontControllerSetMedia',
        'actionCartSave',
        'actionValidateOrder',
        'actionCustomerAccountAdd',

        // Back Office
        'actionAdminControllerSetMedia',
        'actionObjectProductAddAfter',
        'actionObjectProductUpdateAfter',
    ];

    /**
     * Configuration par défaut du module
     */
    public const DEFAULT_CONFIG = [
        'WEPRESTA_ACF_ACTIVE' => true,
        'WEPRESTA_ACF_TITLE' => 'Module Starter',
        'WEPRESTA_ACF_DESCRIPTION' => '',
        'WEPRESTA_ACF_DEBUG' => false,
        'WEPRESTA_ACF_CACHE_TTL' => 3600,
        'WEPRESTA_ACF_API_ENABLED' => false,
    ];

    /**
     * Version minimum de PrestaShop requise
     */
    private const MIN_PS_VERSION = '8.0.0';

    /**
     * Version minimum de PHP requise
     */
    private const MIN_PHP_VERSION = '8.1.0';

    /**
     * Extensions PHP requises
     */
    private const REQUIRED_EXTENSIONS = ['json', 'pdo', 'mbstring'];

    private ?ConfigurationAdapter $config = null;

    public function __construct()
    {
        $this->name = 'wepresta_acf';
        $this->tab = 'administration';
        $this->version = self::VERSION;
        $this->author = 'Bruno Studer';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => self::MIN_PS_VERSION,
            'max' => '9.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Module Starter', [], 'Modules.WeprestaAcf.Admin');
        $this->description = $this->trans(
            'Template PRO de démarrage pour module PrestaShop 8.x/9.x avec architecture moderne',
            [],
            'Modules.WeprestaAcf.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Êtes-vous sûr de vouloir désinstaller ce module ? Toutes les données seront perdues.',
            [],
            'Modules.WeprestaAcf.Admin'
        );
    }

    // =========================================================================
    // INSTALLATION / DÉSINSTALLATION
    // =========================================================================

    public function install(): bool
    {
        if (!$this->checkRequirements()) {
            return false;
        }

        try {
            $installer = new ModuleInstaller($this, Db::getInstance());

            return parent::install()
                && $this->registerHook(self::HOOKS)
                && $installer->install();
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
            $this->log('Installation failed: ' . $e->getMessage(), 3);
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

    public function enable($force_all = false): bool
    {
        return parent::enable($force_all) && $this->registerHook(self::HOOKS);
    }

    /**
     * Vérifie les prérequis avant installation
     */
    private function checkRequirements(): bool
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            $this->_errors[] = sprintf(
                $this->trans('PHP %s minimum requis (actuel: %s)', [], 'Modules.WeprestaAcf.Admin'),
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
            return false;
        }

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (!extension_loaded($ext)) {
                $this->_errors[] = sprintf(
                    $this->trans('Extension PHP requise: %s', [], 'Modules.WeprestaAcf.Admin'),
                    $ext
                );
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // CONFIGURATION (BACK-OFFICE)
    // =========================================================================

    /**
     * Page de configuration - Redirige vers le contrôleur Symfony moderne
     */
    public function getContent(): string
    {
        // Pour PS 8+, on utilise le contrôleur Symfony
        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            $router = $this->getContainer()->get('router');
            $url = $router->generate('wepresta_acf_configuration');
            Tools::redirectAdmin($url);
        }

        // Fallback pour versions plus anciennes
        return $this->renderLegacyConfigurationForm();
    }

    /**
     * Formulaire de configuration legacy (fallback)
     */
    private function renderLegacyConfigurationForm(): string
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->processLegacyConfiguration();
        }

        return $output . $this->buildLegacyForm();
    }

    private function processLegacyConfiguration(): string
    {
        $errors = [];

        $title = Tools::getValue('WEPRESTA_ACF_TITLE');
        if (empty($title)) {
            $errors[] = $this->trans('Le titre est obligatoire.', [], 'Modules.WeprestaAcf.Admin');
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        Configuration::updateValue('WEPRESTA_ACF_ACTIVE', (bool) Tools::getValue('WEPRESTA_ACF_ACTIVE'));
        Configuration::updateValue('WEPRESTA_ACF_TITLE', pSQL($title));
        Configuration::updateValue('WEPRESTA_ACF_DESCRIPTION', pSQL(Tools::getValue('WEPRESTA_ACF_DESCRIPTION')));
        Configuration::updateValue('WEPRESTA_ACF_DEBUG', (bool) Tools::getValue('WEPRESTA_ACF_DEBUG'));
        Configuration::updateValue('WEPRESTA_ACF_CACHE_TTL', (int) Tools::getValue('WEPRESTA_ACF_CACHE_TTL'));
        Configuration::updateValue('WEPRESTA_ACF_API_ENABLED', (bool) Tools::getValue('WEPRESTA_ACF_API_ENABLED'));

        $this->clearModuleCache();

        return $this->displayConfirmation($this->trans('Configuration sauvegardée.', [], 'Modules.WeprestaAcf.Admin'));
    }

    private function buildLegacyForm(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action = 'submit' . $this->name;
        $helper->title = $this->displayName;

        $helper->fields_value = $this->getConfigurationValues();

        return $helper->generateForm([$this->getConfigFormFields()]);
    }

    private function getConfigFormFields(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Configuration', [], 'Modules.WeprestaAcf.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Activer', [], 'Modules.WeprestaAcf.Admin'),
                        'name' => 'WEPRESTA_ACF_ACTIVE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Oui', [], 'Admin.Global')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('Non', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Titre', [], 'Modules.WeprestaAcf.Admin'),
                        'name' => 'WEPRESTA_ACF_TITLE',
                        'class' => 'fixed-width-xxl',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Description', [], 'Modules.WeprestaAcf.Admin'),
                        'name' => 'WEPRESTA_ACF_DESCRIPTION',
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Mode debug', [], 'Modules.WeprestaAcf.Admin'),
                        'name' => 'WEPRESTA_ACF_DEBUG',
                        'is_bool' => true,
                        'hint' => $this->trans('Active les logs détaillés', [], 'Modules.WeprestaAcf.Admin'),
                        'values' => [
                            ['id' => 'debug_on', 'value' => 1, 'label' => $this->trans('Oui', [], 'Admin.Global')],
                            ['id' => 'debug_off', 'value' => 0, 'label' => $this->trans('Non', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('TTL Cache (secondes)', [], 'Modules.WeprestaAcf.Admin'),
                        'name' => 'WEPRESTA_ACF_CACHE_TTL',
                        'class' => 'fixed-width-sm',
                        'suffix' => 's',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('API REST', [], 'Modules.WeprestaAcf.Admin'),
                        'name' => 'WEPRESTA_ACF_API_ENABLED',
                        'is_bool' => true,
                        'hint' => $this->trans('Active les endpoints API', [], 'Modules.WeprestaAcf.Admin'),
                        'values' => [
                            ['id' => 'api_on', 'value' => 1, 'label' => $this->trans('Oui', [], 'Admin.Global')],
                            ['id' => 'api_off', 'value' => 0, 'label' => $this->trans('Non', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getConfigurationValues(): array
    {
        $values = [];
        foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
            $values[$key] = Configuration::get($key);
        }
        return $values;
    }

    // =========================================================================
    // HOOKS FRONT-OFFICE - DISPLAY
    // =========================================================================

    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        $this->context->controller->registerStylesheet(
            'wepresta_acf-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );

        return '';
    }

    public function hookDisplayHome(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        $cacheId = $this->getCacheId('home');

        if (!$this->isCached('module:wepresta_acf/views/templates/hook/home.tpl', $cacheId)) {
            $this->context->smarty->assign([
                'wepresta_acf' => [
                    'title' => $this->getConfig()->get('WEPRESTA_ACF_TITLE'),
                    'description' => $this->getConfig()->get('WEPRESTA_ACF_DESCRIPTION'),
                    'link' => $this->context->link->getModuleLink($this->name, 'display'),
                ],
            ]);
        }

        return $this->fetch('module:wepresta_acf/views/templates/hook/home.tpl', $cacheId);
    }

    public function hookDisplayFooter(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        return $this->fetch('module:wepresta_acf/views/templates/hook/footer.tpl');
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        if (!$this->isActive()) {
            return '';
        }

        /** @var Product $product */
        $product = $params['product'] ?? null;

        if (!$product) {
            return '';
        }

        $this->context->smarty->assign([
            'product_id' => $product->id ?? ($product['id_product'] ?? 0),
        ]);

        return $this->fetch('module:wepresta_acf/views/templates/hook/product-info.tpl');
    }

    public function hookDisplayShoppingCart(array $params): string
    {
        return ''; // Implémentez si nécessaire
    }

    public function hookDisplayOrderConfirmation(array $params): string
    {
        return ''; // Implémentez si nécessaire
    }

    // =========================================================================
    // HOOKS FRONT-OFFICE - ACTIONS
    // =========================================================================

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $controller = Tools::getValue('controller');

        // JS conditionnel
        $jsPages = ['product', 'category', 'index', 'cart'];
        if (in_array($controller, $jsPages, true)) {
            $this->context->controller->registerJavascript(
                'wepresta_acf-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['position' => 'bottom', 'priority' => 150, 'attributes' => 'defer']
            );
        }
    }

    public function hookActionCartSave(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->debug('Cart saved', ['cart_id' => $this->context->cart->id]);
    }

    public function hookActionValidateOrder(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        /** @var Order $order */
        $order = $params['order'] ?? null;

        if ($order) {
            $this->debug('Order validated', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'total' => $order->total_paid,
            ]);
        }
    }

    public function hookActionCustomerAccountAdd(array $params): void
    {
        if (!$this->isActive()) {
            return;
        }

        /** @var Customer $customer */
        $customer = $params['newCustomer'] ?? null;

        if ($customer) {
            $this->debug('Customer registered', ['customer_id' => $customer->id]);
        }
    }

    // =========================================================================
    // HOOKS BACK-OFFICE
    // =========================================================================

    public function hookActionAdminControllerSetMedia(array $params): void
    {
        if ($this->isConfigurationPage()) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    public function hookActionObjectProductAddAfter(array $params): void
    {
        /** @var Product $product */
        $product = $params['object'] ?? null;

        if ($product) {
            $this->debug('Product added', ['product_id' => $product->id]);
        }
    }

    public function hookActionObjectProductUpdateAfter(array $params): void
    {
        /** @var Product $product */
        $product = $params['object'] ?? null;

        if ($product) {
            $this->debug('Product updated', ['product_id' => $product->id]);
            $this->clearModuleCache();
        }
    }

    // =========================================================================
    // MÉTHODES UTILITAIRES
    // =========================================================================

    /**
     * Vérifie si le module est activé
     */
    public function isActive(): bool
    {
        return (bool) Configuration::get('WEPRESTA_ACF_ACTIVE');
    }

    /**
     * Vérifie si on est sur la page de configuration du module
     */
    private function isConfigurationPage(): bool
    {
        return $this->context->controller instanceof AdminModulesController
            && Tools::getValue('configure') === $this->name;
    }

    /**
     * Accès à l'adaptateur de configuration (avec cache)
     */
    public function getConfig(): ConfigurationAdapter
    {
        if ($this->config === null) {
            $this->config = new ConfigurationAdapter();
        }
        return $this->config;
    }

    /**
     * Récupère un service du conteneur Symfony
     */
    public function getService(string $serviceId): ?object
    {
        try {
            $container = $this->getContainer();
            if ($container && $container->has($serviceId)) {
                return $container->get($serviceId);
            }
        } catch (\Exception $e) {
            $this->log('Service not found: ' . $serviceId, 2);
        }
        return null;
    }

    /**
     * Vide le cache du module
     */
    public function clearModuleCache(): void
    {
        $this->_clearCache('*');

        // Clear Symfony cache if available
        $cacheDir = _PS_CACHE_DIR_ . 'smarty/compile/';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob($cacheDir . $this->name . '_*') ?: []);
        }
    }

    /**
     * Log avec contexte
     */
    public function log(string $message, int $severity = 1, array $context = []): void
    {
        $formattedMessage = '[' . $this->name . '] ' . $message;

        if (!empty($context)) {
            $formattedMessage .= ' | ' . json_encode($context);
        }

        PrestaShopLogger::addLog(
            $formattedMessage,
            $severity,
            null,
            'Module',
            $this->id
        );
    }

    /**
     * Log de debug (seulement si mode debug actif)
     */
    private function debug(string $message, array $context = []): void
    {
        if ((bool) Configuration::get('WEPRESTA_ACF_DEBUG')) {
            $this->log('[DEBUG] ' . $message, 1, $context);
        }
    }

    /**
     * Retourne le chemin du module
     */
    public function getModulePath(): string
    {
        return $this->getLocalPath();
    }

    /**
     * Check si le module est correctement configuré
     */
    public function isConfigured(): bool
    {
        return !empty(Configuration::get('WEPRESTA_ACF_TITLE'));
    }

    /**
     * Génère une clé de cache unique
     */
    protected function getCacheId($name = null)
    {
        return $this->name . '_' . (string) $name . '_' . $this->context->shop->id;
    }
}

// Alias pour PrestaShop: Module::getInstanceByName() cherche la classe avec le nom exact du module (snake_case)
// Cette ligne crée un alias 'wepresta_acf' -> 'WeprestaAcf' pour que class_exists() fonctionne
if (!class_exists('wepresta_acf', false)) {
    class_alias('WeprestaAcf', 'wepresta_acf');
}
