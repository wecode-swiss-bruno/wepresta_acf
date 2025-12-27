<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Service\FieldTypeRegistry;
use WeprestaAcf\Application\Service\FieldTypeLoader;

/**
 * Vue.js Field Builder SPA Controller
 */
final class BuilderController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly FieldTypeLoader $fieldTypeLoader,
        private readonly LocationProviderRegistry $locationProviderRegistry
    ) {}

    #[AdminSecurity("is_granted('read', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
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
            ];
        }

        // Get all available locations (entity types, product categories, etc.)
        $locations = $this->locationProviderRegistry->getLocationsGrouped();

        return $this->render('@Modules/wepresta_acf/views/templates/admin/builder.html.twig', [
            'layoutTitle' => $this->trans('ACF Field Builder', 'Modules.Weprestaacf.Admin'),
            'enableSidebar' => true,
            'fieldTypes' => $fieldTypes,
            'locations' => $locations,
            // Toolbar buttons are now handled dynamically via Vue.js and DOM manipulation
            // The buttons are injected via header_toolbar_btn block and controlled by Vue state
            'layoutHeaderToolbarBtn' => [],
        ]);
    }
}
