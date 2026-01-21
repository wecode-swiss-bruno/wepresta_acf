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

use Cart;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Carts.
 */
final class CartEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'cart';
    }

    public function getDisplayHooks(): array
    {
        // Cart doesn't have a dedicated admin page, but we can use generic hooks
        return [];
    }

    public function getActionHooks(): array
    {
        return [
            'actionObjectCartUpdateAfter',
            'actionCartSave',
        ];
    }

    public function buildContext(int $entityId): array
    {
        $cart = new Cart($entityId);

        return [
            'entity_type' => 'cart',
            'entity_id' => $entityId,
            'customer_id' => (int) $cart->id_customer,
            'currency_id' => (int) $cart->id_currency,
            'shop_id' => (int) $cart->id_shop,
            'is_guest' => (bool) $cart->isGuestCart(),
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Cart';
    }
}
