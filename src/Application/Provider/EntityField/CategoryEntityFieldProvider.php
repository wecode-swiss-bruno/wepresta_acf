<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Category;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Categories.
 */
final class CategoryEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'category';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminCategoriesExtra'];
    }

    public function getActionHooks(): array
    {
        return ['actionCategoryUpdate', 'actionCategoryAdd'];
    }

    public function buildContext(int $entityId): array
    {
        $category = new Category($entityId);

        return [
            'entity_type' => 'category',
            'entity_id' => $entityId,
            'parent_id' => (int) $category->id_parent,
            'level_depth' => (int) $category->level_depth,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Category';
    }
}
