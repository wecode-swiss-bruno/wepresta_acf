<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

use WeprestaAcf\Domain\Entity\CptType;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CptTypeRepositoryInterface
{
    public function find(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findBySlug(string $slug, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findActive(?int $langId = null, ?int $shopId = null): array;
    public function findAll(?int $limit = null, ?int $offset = null): array;
    public function findWithGroups(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findWithTaxonomies(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findFull(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function save(CptType $type, ?int $shopId = null): int;
    public function delete(int $id): bool;
    public function attachGroup(int $typeId, int $groupId, int $position = 0): bool;
    public function detachGroup(int $typeId, int $groupId): bool;
    public function syncGroups(int $typeId, array $groupIds): void;
    public function attachTaxonomy(int $typeId, int $taxonomyId): bool;
    public function detachTaxonomy(int $typeId, int $taxonomyId): bool;
    public function syncTaxonomies(int $typeId, array $taxonomyIds): void;
    public function slugExists(string $slug, ?int $excludeId = null): bool;
}
