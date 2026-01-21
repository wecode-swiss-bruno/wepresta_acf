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

use Customer;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Customers.
 */
final class CustomerEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'customer';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminCustomers'];
    }

    public function getActionHooks(): array
    {
        return ['actionCustomerAccountUpdate', 'actionObjectCustomerUpdateAfter'];
    }

    public function buildContext(int $entityId): array
    {
        $customer = new Customer($entityId);

        return [
            'entity_type' => 'customer',
            'entity_id' => $entityId,
            'group_id' => (int) $customer->id_default_group,
            'is_guest' => (bool) $customer->is_guest,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Customer';
    }
}
