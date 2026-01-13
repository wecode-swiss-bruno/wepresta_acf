<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

use WeprestaAcf\Domain\Entity\CptTerm;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CptTermRepositoryInterface
{
    public function find(int $id, ?int $langId = null): ?CptTerm;
    public function findBySlug(string $slug, int $taxonomyId, ?int $langId = null): ?CptTerm;
    public function findByTaxonomy(int $taxonomyId, ?int $langId = null): array;
    public function findTopLevel(int $taxonomyId, ?int $langId = null): array;
    public function findChildren(int $parentId, ?int $langId = null): array;
    public function getTree(int $taxonomyId, ?int $langId = null): array;
    public function save(CptTerm $term): int;
    public function delete(int $id): bool;
    public function countPosts(int $termId): int;
    public function slugExists(string $slug, int $taxonomyId, ?int $excludeId = null): bool;
}
