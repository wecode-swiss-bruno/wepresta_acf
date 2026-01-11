<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use CMSCategory;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop CMS Categories.
 */
final class CmsCategoryEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'cms_category';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminCmsCategories'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectCmsCategoryUpdateAfter', 'actionObjectCmsCategoryAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        $category = new CMSCategory($entityId);

        return [
            'entity_type' => 'cms_category',
            'entity_id' => $entityId,
            'parent_id' => (int) $category->id_parent,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'CMS Category';
    }
}
