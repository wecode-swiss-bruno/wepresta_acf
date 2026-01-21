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

interface AcfGroupRepositoryInterface
{
    public function findById(int $id): ?array;

    public function findBySlug(string $slug): ?array;

    public function findActiveGroups(?int $shopId = null): array;

    public function findAll(?int $limit = null, ?int $offset = null): array;

    public function create(array $data): int;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function slugExists(string $slug, ?int $excludeId = null): bool;

    /**
     * Get shop IDs associated with a group.
     *
     * @param int $groupId Group ID
     *
     * @return array<int> Array of shop IDs
     */
    public function getShopIds(int $groupId): array;

    /**
     * Associate a group with a shop.
     *
     * @param int $groupId Group ID
     * @param int $shopId Shop ID
     *
     * @return bool Success
     */
    public function addShopAssociation(int $groupId, int $shopId): bool;

    /**
     * Remove all shop associations for a group.
     *
     * @param int $groupId Group ID
     *
     * @return bool Success
     */
    public function removeAllShopAssociations(int $groupId): bool;

    /**
     * Associate a group with all active shops.
     *
     * @param int $groupId Group ID
     */
    public function addAllShopAssociations(int $groupId): void;
}
