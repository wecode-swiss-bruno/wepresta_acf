<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;

use Address;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Customer Addresses.
 */
final class CustomerAddressEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'customer_address';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminAddresses'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectAddressUpdateAfter', 'actionObjectAddressAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        $address = new Address($entityId);

        return [
            'entity_type' => 'customer_address',
            'entity_id' => $entityId,
            'customer_id' => (int) $address->id_customer,
            'country_id' => (int) $address->id_country,
            'state_id' => (int) $address->id_state,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Customer Address';
    }
}

