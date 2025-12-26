<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Application\Service;

use WeprestaAcf\Example\Domain\Entity\WeprestaAcfEntity;
use WeprestaAcf\Example\Domain\Repository\WeprestaAcfRepositoryInterface;
use WeprestaAcf\Example\Infrastructure\Adapter\ConfigurationAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service principal du module
 */
final class WeprestaAcfService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly WeprestaAcfRepositoryInterface $repository,
        private readonly ConfigurationAdapter $config,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Récupère les éléments actifs pour le front-office
     *
     * @return WeprestaAcfEntity[]
     */
    public function getActiveItems(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        return $this->repository->findActive();
    }

    /**
     * Récupère un élément par son ID
     */
    public function getItem(int $id): ?WeprestaAcfEntity
    {
        return $this->repository->find($id);
    }

    /**
     * Crée un nouvel élément
     */
    public function createItem(string $name, string $description = ''): WeprestaAcfEntity
    {
        $entity = new WeprestaAcfEntity($name);
        $entity->setDescription($description);

        // Calculer la position suivante
        $maxPosition = $this->repository->count();
        $entity->setPosition($maxPosition);

        $this->repository->save($entity);

        $this->logger->info('WeprestaAcf: Item created', [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
        ]);

        return $entity;
    }

    /**
     * Met à jour un élément
     */
    public function updateItem(int $id, array $data): ?WeprestaAcfEntity
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            return null;
        }

        if (isset($data['name'])) {
            $entity->setName($data['name']);
        }

        if (isset($data['description'])) {
            $entity->setDescription($data['description']);
        }

        if (isset($data['active'])) {
            $entity->setActive((bool) $data['active']);
        }

        if (isset($data['position'])) {
            $entity->setPosition((int) $data['position']);
        }

        $this->repository->save($entity);

        $this->logger->info('WeprestaAcf: Item updated', ['id' => $id]);

        return $entity;
    }

    /**
     * Supprime un élément
     */
    public function deleteItem(int $id): bool
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            return false;
        }

        $this->repository->delete($entity);

        $this->logger->info('WeprestaAcf: Item deleted', ['id' => $id]);

        return true;
    }

    /**
     * Active/Désactive un élément
     */
    public function toggleItem(int $id): ?WeprestaAcfEntity
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            return null;
        }

        if ($entity->isActive()) {
            $entity->deactivate();
        } else {
            $entity->activate();
        }

        $this->repository->save($entity);

        return $entity;
    }

    /**
     * Vérifie si le module est activé
     */
    public function isEnabled(): bool
    {
        return $this->config->getBool('WEPRESTA_ACF_ACTIVE');
    }

    /**
     * Récupère le titre configuré
     */
    public function getTitle(): string
    {
        return $this->config->get('WEPRESTA_ACF_TITLE', 'Module Starter');
    }

    /**
     * Récupère la description configurée
     */
    public function getDescription(): string
    {
        return $this->config->get('WEPRESTA_ACF_DESCRIPTION', '');
    }

    /**
     * Vérifie si le mode debug est actif
     */
    public function isDebugEnabled(): bool
    {
        return $this->config->getBool('WEPRESTA_ACF_DEBUG');
    }

    /**
     * Récupère le TTL du cache
     */
    public function getCacheTtl(): int
    {
        return $this->config->getInt('WEPRESTA_ACF_CACHE_TTL', 3600);
    }
}

