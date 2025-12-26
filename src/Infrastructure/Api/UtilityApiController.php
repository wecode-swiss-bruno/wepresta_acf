<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\FieldTypeRegistry;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;

class UtilityApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    public function fieldTypes(): JsonResponse
    {
        try {
            return $this->json(['success' => true, 'data' => $this->fieldTypeRegistry->toArray()]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function fieldTypesGrouped(): JsonResponse
    {
        try {
            $grouped = $this->fieldTypeRegistry->getAllGroupedByCategory();
            $result = [];
            foreach ($grouped as $category => $types) {
                $result[$category] = [];
                foreach ($types as $type => $fieldType) {
                    $result[$category][$type] = [
                        'type' => $type, 'label' => $fieldType->getLabel(),
                        'icon' => $fieldType->getIcon(), 'supportsTranslation' => $fieldType->supportsTranslation(),
                    ];
                }
            }
            return $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

