<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Customer Groups.
 */
final class CustomerGroupEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'customer_group';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminGroups'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectGroupUpdateAfter', 'actionObjectGroupAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'customer_group',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Customer Group';
    }
}
