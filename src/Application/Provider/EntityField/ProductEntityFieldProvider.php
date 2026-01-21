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

use Product;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

/**
 * Entity field provider for PrestaShop Products.
 */
final class ProductEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'product';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminProductsExtra'];
    }

    public function getActionHooks(): array
    {
        return ['actionProductUpdate', 'actionProductAdd'];
    }

    public function buildContext(int $entityId): array
    {
        $product = new Product($entityId);
        $categoryIds = $product->getCategories();

        return [
            'entity_type' => 'product',
            'entity_id' => $entityId,
            'category_ids' => array_map('intval', $categoryIds),
            'category_id' => ! empty($categoryIds) ? (int) $categoryIds[0] : null,
            'product_type' => $product->getType(),
            'manufacturer_id' => (int) $product->id_manufacturer,
            'supplier_id' => (int) $product->id_supplier,
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Product';
    }
}
