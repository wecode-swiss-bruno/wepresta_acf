<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider\EntityField;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Order;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Orders.
 */
final class OrderEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'order';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminOrderMain'];
    }

    public function getActionHooks(): array
    {
        return [
            'actionObjectOrderUpdateAfter',
            'actionOrderStatusUpdate',
            'actionOrderStatusPostUpdate',
        ];
    }

    public function buildContext(int $entityId): array
    {
        $order = new Order($entityId);

        return [
            'entity_type' => 'order',
            'entity_id' => $entityId,
            'order_status' => (int) $order->current_state,
            'customer_id' => (int) $order->id_customer,
            'currency_id' => (int) $order->id_currency,
            'shop_id' => (int) $order->id_shop,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Order';
    }
}
