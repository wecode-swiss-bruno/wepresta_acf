<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Repository;

interface AcfFieldRepositoryInterface
{
    public function findById(int $id): ?array;

    public function findBySlug(string $slug): ?array;

    public function findBySlugAndGroup(string $slug, int $groupId): ?array;

    public function findByGroup(int $groupId): array;

    public function findAllByGroup(int $groupId): array;

    public function findByParent(int $parentId): array;

    public function create(array $data): int;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function deleteByGroup(int $groupId): bool;

    public function slugExistsInGroup(string $slug, int $groupId, ?int $excludeId = null): bool;

    public function getNextPosition(int $groupId): int;

    public function countByGroup(int $groupId): int;

    public function getFieldTranslations(int $fieldId): array;

    public function saveFieldTranslations(int $fieldId, array $translations): bool;
}
