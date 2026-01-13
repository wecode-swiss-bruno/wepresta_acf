<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

use WeprestaAcf\Domain\Entity\CptRelation;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CptRelationRepositoryInterface
{
    public function find(int $id): ?CptRelation;
    public function findBySlug(string $slug, int $sourceTypeId, int $targetTypeId): ?CptRelation;
    public function findBySourceType(int $sourceTypeId): array;
    public function findByTargetType(int $targetTypeId): array;
    public function findActive(): array;
    public function findAll(?int $limit = null, ?int $offset = null): array;
    public function save(CptRelation $relation): int;
    public function delete(int $id): bool;
    public function exists(string $slug, int $sourceTypeId, int $targetTypeId, ?int $excludeId = null): bool;
}
