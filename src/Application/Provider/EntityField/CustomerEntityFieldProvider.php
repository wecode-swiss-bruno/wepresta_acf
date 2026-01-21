<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
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
