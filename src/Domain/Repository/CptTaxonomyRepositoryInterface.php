<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

use WeprestaAcf\Domain\Entity\CptTaxonomy;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CptTaxonomyRepositoryInterface
{
    public function find(int $id, ?int $langId = null): ?CptTaxonomy;
    public function findBySlug(string $slug, ?int $langId = null): ?CptTaxonomy;
    public function findActive(?int $langId = null): array;
    public function findAll(?int $limit = null, ?int $offset = null): array;
    public function findByType(int $typeId, ?int $langId = null): array;
    public function findWithTerms(int $id, ?int $langId = null): ?CptTaxonomy;
    public function save(CptTaxonomy $taxonomy): int;
    public function delete(int $id): bool;
    public function slugExists(string $slug, ?int $excludeId = null): bool;
}
