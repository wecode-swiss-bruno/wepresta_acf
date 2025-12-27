<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use Group;
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

