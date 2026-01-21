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

namespace WeprestaAcf\Application\Helper;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use WeprestaAcf\Infrastructure\Repository\AcfFieldRepository;
use WeprestaAcf\Infrastructure\Repository\AcfFieldValueRepository;

/**
 * Helper class for accessing ACF field values in templates.
 *
 * Provides a simple static API for use in Smarty templates and PHP code.
 *
 * @example
 * // In Smarty template
 * {$acf_color = AcfHelper::getField('product_color', $product->id, 'product')}
 * {if $acf_color}<div style="color: {$acf_color}">{/if}
 *
 * // In PHP
 * use WeprestaAcf\Application\Helper\AcfHelper;
 * $color = AcfHelper::getField('product_color', $productId, 'product');
 *
 * // Get all fields for an object
 * $allFields = AcfHelper::getFields($productId, 'product');
 */
final class AcfHelper
{
    private static ?AcfFieldValueRepository $valueRepository = null;

    private static ?AcfFieldRepository $fieldRepository = null;

    /**
     * Gets a single field value.
     *
     * @param string $slug The field slug
     * @param int $objectId The object ID (product ID, category ID, etc.)
     * @param string $objectType The object type (product, category, cms, etc.)
     * @param int|null $langId Language ID (defaults to current context language)
     *
     * @return mixed The field value or null if not found
     */
    public static function getField(
        string $slug,
        int $objectId,
        string $objectType = 'product',
        ?int $langId = null
    ): mixed {
        $langId ??= (int) Context::getContext()->language->id;

        return self::getValueRepository()->getValue(
            $slug,
            $objectId,
            $objectType,
            $langId
        );
    }

    /**
     * Gets all field values for an object.
     *
     * @param int $objectId The object ID
     * @param string $objectType The object type
     * @param int|null $langId Language ID
     *
     * @return array<string, mixed> Map of slug => value
     */
    public static function getFields(
        int $objectId,
        string $objectType = 'product',
        ?int $langId = null
    ): array {
        $langId ??= (int) Context::getContext()->language->id;

        return self::getValueRepository()->getValuesForObject(
            $objectId,
            $objectType,
            $langId
        );
    }

    /**
     * Checks if an object has a specific field value.
     *
     * @param string $slug The field slug
     * @param int $objectId The object ID
     * @param string $objectType The object type
     *
     * @return bool True if the field has a non-null value
     */
    public static function hasField(
        string $slug,
        int $objectId,
        string $objectType = 'product'
    ): bool {
        return self::getField($slug, $objectId, $objectType) !== null;
    }

    /**
     * Gets a field value with a default fallback.
     *
     * @param string $slug The field slug
     * @param int $objectId The object ID
     * @param mixed $default Default value if field is empty
     * @param string $objectType The object type
     * @param int|null $langId Language ID
     *
     * @return mixed The field value or default
     */
    public static function getFieldOrDefault(
        string $slug,
        int $objectId,
        mixed $default,
        string $objectType = 'product',
        ?int $langId = null
    ): mixed {
        $value = self::getField($slug, $objectId, $objectType, $langId);

        return $value !== null && $value !== '' ? $value : $default;
    }

    /**
     * Gets multiple field values at once.
     *
     * @param array<string> $slugs List of field slugs
     * @param int $objectId The object ID
     * @param string $objectType The object type
     * @param int|null $langId Language ID
     *
     * @return array<string, mixed> Map of slug => value
     */
    public static function getFieldsBySlug(
        array $slugs,
        int $objectId,
        string $objectType = 'product',
        ?int $langId = null
    ): array {
        $result = [];

        foreach ($slugs as $slug) {
            $result[$slug] = self::getField($slug, $objectId, $objectType, $langId);
        }

        return $result;
    }

    /**
     * Gets field configuration by slug.
     *
     * @param string $slug The field slug
     *
     * @return array<string, mixed>|null Field configuration or null
     */
    public static function getFieldConfig(string $slug): ?array
    {
        return self::getFieldRepository()->findBySlug($slug);
    }

    /**
     * Clears internal caches (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$valueRepository = null;
        self::$fieldRepository = null;
    }

    private static function getValueRepository(): AcfFieldValueRepository
    {
        if (self::$valueRepository === null) {
            self::$valueRepository = new AcfFieldValueRepository();
        }

        return self::$valueRepository;
    }

    private static function getFieldRepository(): AcfFieldRepository
    {
        if (self::$fieldRepository === null) {
            self::$fieldRepository = new AcfFieldRepository();
        }

        return self::$fieldRepository;
    }
}
