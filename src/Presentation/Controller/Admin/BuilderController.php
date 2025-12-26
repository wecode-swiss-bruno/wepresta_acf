<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\FieldTypeRegistry;

/**
 * Vue.js Field Builder SPA Controller
 */
final class BuilderController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry
    ) {}

    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute: 'admin_dashboard')]
    public function index(): Response
    {
        $fieldTypes = [];
        foreach ($this->fieldTypeRegistry->getAll() as $type => $fieldType) {
            $fieldTypes[] = [
                'type' => $type,
                'label' => $fieldType->getLabel(),
                'icon' => $fieldType->getIcon(),
                'category' => $fieldType->getCategory(),
            ];
        }

        return $this->render('@Modules/wepresta_acf/views/templates/admin/builder.html.twig', [
            'layoutTitle' => $this->trans('ACF Field Builder', 'Modules.Weprestaacf.Admin'),
            'enableSidebar' => true,
            'fieldTypes' => $fieldTypes,
        ]);
    }
}
