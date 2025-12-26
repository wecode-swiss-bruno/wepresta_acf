<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

interface AcfGroupRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findBySlug(string $slug): ?array;
    public function findActiveGroups(?int $shopId = null): array;
    public function findAll(): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function slugExists(string $slug, ?int $excludeId = null): bool;
}

