<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
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
