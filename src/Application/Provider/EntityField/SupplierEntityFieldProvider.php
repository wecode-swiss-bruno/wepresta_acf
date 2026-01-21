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

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Suppliers.
 */
final class SupplierEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'supplier';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminSuppliers'];
    }

    public function getActionHooks(): array
    {
        return ['actionObjectSupplierUpdateAfter', 'actionObjectSupplierAddAfter'];
    }

    public function buildContext(int $entityId): array
    {
        return [
            'entity_type' => 'supplier',
            'entity_id' => $entityId,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Supplier';
    }
}
