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

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;

final class ValueProvider
{
    public function __construct(
        private readonly AcfFieldValueRepositoryInterface $valueRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductFieldValues(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->getEntityFieldValues('product', $productId, $shopId, $langId);
    }

    /**
     * @return array<int, array{slug: string, title: string, type: string, value: mixed, instructions: string|null}>
     */
    public function getProductFieldValuesWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->getEntityFieldValuesWithMeta('product', $productId, $shopId, $langId);
    }

    /**
     * Gets field values for any entity type.
     *
     * @return array<string, mixed>
     */
    public function getEntityFieldValues(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->valueRepository->findByEntity($entityType, $entityId, $shopId, $langId);
    }

    /**
     * Gets all field values for any entity type, including ALL languages for translatable fields.
     * Returns: [slug => value] for non-translatable, [slug => [langId => value]] for translatable.
     *
     * @return array<string, mixed>
     */
    public function getEntityFieldValuesAllLanguages(string $entityType, int $entityId, ?int $shopId = null): array
    {
        return $this->valueRepository->findByEntityAllLanguages($entityType, $entityId, $shopId);
    }

    /**
     * Gets all field values indexed by Field ID (for Admin Builders).
     *
     * @return array<int, mixed>
     */
    public function getEntityFieldValuesAllLanguagesIndexedById(string $entityType, int $entityId, ?int $shopId = null): array
    {
        return $this->valueRepository->findByEntityAllLanguagesIndexedById($entityType, $entityId, $shopId);
    }
}
