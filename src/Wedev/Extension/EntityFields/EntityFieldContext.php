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
 * Helper class for building context arrays for entity field location rules.
 *
 * Context arrays are used by ACF location rules to determine if field groups
 * should be displayed for a specific entity.
 *
 * @example
 * $context = EntityFieldContext::build('product', 123, [
 *     'category_ids' => [1, 2, 3],
 *     'product_type' => 'simple',
 * ]);
 *
 * // Result:
 * // [
 * //     'entity_type' => 'product',
 * //     'entity_id' => 123,
 * //     'category_ids' => [1, 2, 3],
 * //     'product_type' => 'simple',
 * // ]
 */
final class EntityFieldContext
{
    /**
     * Builds a context array for an entity.
     *
     * The context always includes:
     * - 'entity_type': The entity type identifier
     * - 'entity_id': The entity ID
     *
     * Additional context data can be provided via $additional.
     *
     * @param string $entityType Entity type identifier
     * @param int $entityId Entity ID
     * @param array<string, mixed> $additional Additional context data
     *
     * @return array<string, mixed> Context array
     */
    public static function build(string $entityType, int $entityId, array $additional = []): array
    {
        return array_merge(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            $additional
        );
    }

    /**
     * Builds context from a provider.
     *
     * Convenience method that gets the provider from the registry
     * and builds context using the provider's buildContext method.
     *
     * @param EntityFieldRegistry $registry Registry instance
     * @param string $entityType Entity type identifier
     * @param int $entityId Entity ID
     *
     * @return array<string, mixed> Context array
     */
    public static function buildFromProvider(
        EntityFieldRegistry $registry,
        string $entityType,
        int $entityId
    ): array {
        $provider = $registry->getEntityType($entityType);

        if ($provider === null) {
            // Fallback to basic context
            return self::build($entityType, $entityId);
        }

        return $provider->buildContext($entityId);
    }
}
