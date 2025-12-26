<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Domain\Repository;

use WeprestaAcf\Example\Domain\Entity\WeprestaAcfEntity;

/**
 * Interface du repository - Domain Layer
 */
interface WeprestaAcfRepositoryInterface
{
    /**
     * Trouve une entité par son ID
     */
    public function find(int $id): ?WeprestaAcfEntity;

    /**
     * Trouve toutes les entités
     *
     * @return WeprestaAcfEntity[]
     */
    public function findAll(): array;

    /**
     * Trouve les entités actives, triées par position
     *
     * @return WeprestaAcfEntity[]
     */
    public function findActive(): array;

    /**
     * Trouve par critères
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return WeprestaAcfEntity[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null): array;

    /**
     * Sauvegarde une entité
     */
    public function save(WeprestaAcfEntity $entity): void;

    /**
     * Supprime une entité
     */
    public function delete(WeprestaAcfEntity $entity): void;

    /**
     * Compte le nombre total d'entités
     */
    public function count(): int;
}

