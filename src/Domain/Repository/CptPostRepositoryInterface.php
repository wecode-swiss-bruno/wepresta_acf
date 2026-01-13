<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

use WeprestaAcf\Domain\Entity\CptPost;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CptPostRepositoryInterface
{
    public function find(int $id, ?int $langId = null, ?int $shopId = null): ?CptPost;
    public function findBySlug(string $slug, int $typeId, ?int $langId = null, ?int $shopId = null): ?CptPost;
    public function findByType(int $typeId, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array;
    public function findPublishedByType(int $typeId, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array;
    public function findByTerm(int $termId, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array;
    public function findByTerms(array $termIds, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array;
    public function countByType(int $typeId, ?int $shopId = null, ?string $status = null): int;
    public function countByTerm(int $termId, ?int $shopId = null): int;
    public function save(CptPost $post, ?int $shopId = null): int;
    public function delete(int $id): bool;
    public function attachTerm(int $postId, int $termId): bool;
    public function detachTerm(int $postId, int $termId): bool;
    public function syncTerms(int $postId, array $termIds): void;
    public function findRelated(int $relationId, int $sourcePostId): array;
    public function attachRelated(int $relationId, int $sourcePostId, int $targetPostId, int $position = 0): bool;
    public function syncRelated(int $relationId, int $sourcePostId, array $targetPostIds): void;
    public function slugExists(string $slug, int $typeId, ?int $excludeId = null): bool;
}
