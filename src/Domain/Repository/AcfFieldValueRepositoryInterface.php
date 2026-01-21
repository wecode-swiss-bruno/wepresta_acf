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

namespace WeprestaAcf\Domain\Repository;


if (!defined('_PS_VERSION_')) {
    exit;
}

interface AcfFieldValueRepositoryInterface
{
    // Legacy product-specific methods (backward compatibility)
    /**
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}>
     */
    public function findByProductWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array;

    public function save(
        int $fieldId,
        int $productId,
        ?string $value,
        ?int $shopId = null,
        ?int $langId = null,
        ?bool $isTranslatable = null,
        ?string $indexValue = null
    ): bool;

    public function deleteByProduct(int $productId, ?int $shopId = null): bool;

    public function deleteByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): bool;

    // New generic entity methods
    /**
     * @return array<string, mixed>
     */
    public function findByEntity(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array;

    /**
     * Find all field values for an entity, including ALL languages for translatable fields.
     * Returns: [slug => value] for non-translatable, [slug => [langId => value]] for translatable.
     *
     * @return array<string, mixed>
     */
    public function findByEntityAllLanguages(string $entityType, int $entityId, ?int $shopId = null): array;

    /**
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}>
     */
    public function findByEntityWithMeta(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array;

    /**
     * Find field values with metadata for an entity, filtered by display hook.
     * Only returns fields from groups configured to display in the specified hook.
     *
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null, config: array<string, mixed>, fo_options: array<string, mixed>}>
     */
    public function findByFieldAndEntity(int $fieldId, string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): ?string;

    public function saveEntity(
        int $fieldId,
        string $entityType,
        int $entityId,
        ?string $value,
        ?int $shopId = null,
        ?int $langId = null,
        ?bool $isTranslatable = null,
        ?string $indexValue = null
    ): bool;

    public function deleteByEntity(string $entityType, int $entityId, ?int $shopId = null): bool;

    public function deleteByFieldAndEntity(int $fieldId, string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): bool;

    public function deleteByField(int $fieldId): bool;

    /**
     * Deletes all translatable values (id_lang IS NOT NULL) for a given field.
     * Used when a field is changed from translatable to non-translatable.
     */
    public function deleteTranslatableValuesByField(int $fieldId): bool;

    /**
     * @return array<int>
     */
    public function findEntitiesByFieldValue(int $fieldId, string $value, string $entityType, ?int $shopId = null): array;

    /**
     * Find all field values for all fields in a group.
     * Used for export functionality.
     *
     * @param int $groupId Group ID
     *
     * @return array<array<string, mixed>> Array of field values with all columns
     */
    public function findAllByGroup(int $groupId): array;
}
