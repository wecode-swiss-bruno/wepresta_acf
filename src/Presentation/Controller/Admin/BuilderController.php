<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use Configuration;
use Language;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Service\FieldTypeLoader;
use WeprestaAcf\Application\Service\FieldTypeRegistry;

/**
 * Vue.js Field Builder SPA Controller.
 */
final class BuilderController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly FieldTypeLoader $fieldTypeLoader,
        private readonly LocationProviderRegistry $locationProviderRegistry,
        private readonly TranslatorInterface $translator
    ) {
        // Note: parent::__construct() is NOT called for PS8/PS9 compatibility
        // In PS9 (Symfony 6.x), calling parent constructor causes "Cannot call constructor" error
    }

    public function index(): Response
    {
        // Load custom field types from theme/uploads
        $this->fieldTypeLoader->loadAllCustomTypes();

        // Get loaded types info to know which are custom
        $loadedTypesInfo = $this->fieldTypeLoader->getLoadedTypesInfo();

        $fieldTypes = [];

        foreach ($this->fieldTypeRegistry->getAll() as $type => $fieldType) {
            // Check if this is a custom type (from theme or uploaded)
            $source = $loadedTypesInfo[$type]['source'] ?? 'core';
            $category = $fieldType->getCategory();

            // Override category to 'custom' for non-core types
            if ($source !== 'core') {
                $category = 'custom';
            }

            $fieldTypes[] = [
                'type' => $type,
                'label' => $fieldType->getLabel(),
                'icon' => $fieldType->getIcon(),
                'category' => $category,
                'source' => $source,
                'supportsTranslation' => $fieldType->supportsTranslation(),
            ];
        }

        // Get all available locations (entity types, product categories, etc.)
        $locations = $this->locationProviderRegistry->getLocationsGrouped();

        // Get available languages for multilingual fields
        $languages = [];

        foreach (Language::getLanguages(true) as $lang) {
            // Extract language name without duplicate code in parentheses
            // PrestaShop stores names like "English (English)", we want just "English"
            $name = $lang['name'];

            if (preg_match('/^(.+)\s*\([^)]+\)$/', $name, $matches)) {
                $name = trim($matches[1]);
            }

            $languages[] = [
                'id' => (int) $lang['id_lang'],
                'code' => $lang['iso_code'],
                'name' => $name,
                'is_default' => (int) $lang['id_lang'] === (int) Configuration::get('PS_LANG_DEFAULT'),
            ];
        }

        return $this->render('@Modules/wepresta_acf/views/templates/admin/builder.html.twig', [
            'layoutTitle' => $this->trans('ACF Field Builder', 'Modules.Weprestaacf.Admin'),
            'enableSidebar' => true,
            'fieldTypes' => $fieldTypes,
            'locations' => $locations,
            'languages' => $languages,
            // Toolbar buttons are now handled dynamically via Vue.js and DOM manipulation
            // The buttons are injected via header_toolbar_btn block and controlled by Vue state
            'layoutHeaderToolbarBtn' => [],
        ]);
    }

    /**
     * Override trans() method for PS8/PS9 compatibility.
     * In PS8, translator is not available in the service locator.
     */
    protected function trans($key, $domain, array $parameters = [])
    {
        return $this->translator->trans($key, $parameters, $domain);
    }
}
