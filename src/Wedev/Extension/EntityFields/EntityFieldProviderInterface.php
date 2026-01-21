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

namespace WeprestaAcf\Wedev\Extension\EntityFields;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interface for modules to register entity types that support custom fields.
 *
 * Modules implementing this interface can register their entities
 * (e.g., Product, Category, Custom Post Types) with the EntityFieldRegistry.
 *
 * @example
 * // In wepresta_acf module:
 * final class ProductEntityFieldProvider implements EntityFieldProviderInterface
 * {
 *     public function getEntityType(): string
 *     {
 *         return 'product';
 *     }
 *
 *     public function getDisplayHooks(): array
 *     {
 *         return ['displayAdminProductsExtra'];
 *     }
 *
 *     public function getActionHooks(): array
 *     {
 *         return ['actionProductUpdate', 'actionProductAdd'];
 *     }
 *
 *     public function buildContext(int $entityId): array
 *     {
 *         $product = new Product($entityId);
 *         return [
 *             'entity_type' => 'product',
 *             'entity_id' => $entityId,
 *             'category_ids' => $product->getCategories(),
 *             'product_type' => $product->getType(),
 *         ];
 *     }
 *
 *     public function getEntityLabel(int $langId): string
 *     {
 *         return 'Product';
 *     }
 * }
 */
interface EntityFieldProviderInterface
{
    /**
     * Returns the unique entity type identifier.
     *
     * Examples: 'product', 'category', 'customer', 'cpt_event'
     */
    public function getEntityType(): string;

    /**
     * Returns the display hooks for this entity type.
     *
     * These hooks are used to render custom fields in admin edit pages.
     * Example: ['displayAdminProductsExtra', 'displayAdminCategoriesExtra']
     *
     * @return array<string> Hook names
     */
    public function getDisplayHooks(): array;

    /**
     * Returns the action hooks for this entity type.
     *
     * These hooks are used to save custom field values when entities are created/updated.
     * Example: ['actionProductUpdate', 'actionProductAdd']
     *
     * @return array<string> Hook names
     */
    public function getActionHooks(): array;

    /**
     * Builds context array for location rule matching.
     *
     * The context is used by ACF location rules to determine if field groups
     * should be displayed for this entity.
     *
     * Must include at minimum:
     * - 'entity_type': The entity type identifier
     * - 'entity_id': The entity ID
     *
     * Can include additional entity-specific data:
     * - 'category_ids': For products
     * - 'post_type': For CPT posts
     * - etc.
     *
     * @param int $entityId The entity ID
     *
     * @return array<string, mixed> Context array
     */
    public function buildContext(int $entityId): array;

    /**
     * Returns a human-readable label for this entity type.
     *
     * Used in UI (e.g., location rules editor, field group builder).
     *
     * @param int $langId Language ID
     *
     * @return string Entity label
     */
    public function getEntityLabel(int $langId): string;
}
