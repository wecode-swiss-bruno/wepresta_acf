<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Entity\CptTaxonomy;
use WeprestaAcf\Domain\Entity\CptTerm;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTermRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTaxonomyService
{
    private CptTaxonomyRepositoryInterface $taxonomyRepository;
    private CptTermRepositoryInterface $termRepository;
    private ContextAdapter $context;

    public function __construct(CptTaxonomyRepositoryInterface $taxonomyRepository, CptTermRepositoryInterface $termRepository, ContextAdapter $context)
    {
        $this->taxonomyRepository = $taxonomyRepository;
        $this->termRepository = $termRepository;
        $this->context = $context;
    }

    public function getTaxonomyById(int $id): ?CptTaxonomy
    {
        return $this->taxonomyRepository->find($id, $this->context->getLangId());
    }

    public function getTaxonomyBySlug(string $slug): ?CptTaxonomy
    {
        return $this->taxonomyRepository->findBySlug($slug, $this->context->getLangId());
    }

    public function getAllTaxonomies(): array
    {
        return $this->taxonomyRepository->findAll($this->context->getLangId());
    }

    public function getTaxonomiesByType(int $typeId): array
    {
        return $this->taxonomyRepository->findByType($typeId, $this->context->getLangId());
    }

    public function createTaxonomy(array $data): int
    {
        $taxonomy = new CptTaxonomy($data);
        return $this->taxonomyRepository->save($taxonomy);
    }

    public function updateTaxonomy(int $id, array $data): bool
    {
        $taxonomy = $this->taxonomyRepository->find($id);
        if (!$taxonomy) {
            return false;
        }
        if (isset($data['slug']))
            $taxonomy->setSlug($data['slug']);
        if (isset($data['name']))
            $taxonomy->setName($data['name']);
        $this->taxonomyRepository->save($taxonomy);
        return true;
    }

    public function deleteTaxonomy(int $id): bool
    {
        return $this->taxonomyRepository->delete($id);
    }

    public function getTermById(int $id): ?CptTerm
    {
        return $this->termRepository->find($id, $this->context->getLangId());
    }

    public function getTermBySlug(string $slug, int $taxonomyId): ?CptTerm
    {
        return $this->termRepository->findBySlug($slug, $taxonomyId, $this->context->getLangId());
    }

    public function getTermsByTaxonomy(int $taxonomyId): array
    {
        return $this->termRepository->findByTaxonomy($taxonomyId, $this->context->getLangId());
    }

    public function getTermsTree(int $taxonomyId): array
    {
        return $this->termRepository->getTree($taxonomyId, $this->context->getLangId());
    }

    public function createTerm(array $data): int
    {
        $term = new CptTerm($data);
        return $this->termRepository->save($term);
    }

    public function updateTerm(int $id, array $data): bool
    {
        $term = $this->termRepository->find($id);
        if (!$term) {
            return false;
        }
        if (isset($data['slug']))
            $term->setSlug($data['slug']);
        if (isset($data['name']))
            $term->setName($data['name']);
        $this->termRepository->save($term);
        return true;
    }

    public function deleteTerm(int $id): bool
    {
        return $this->termRepository->delete($id);
    }

    public function countPostsByTerm(int $termId): int
    {
        return $this->termRepository->countPosts($termId);
    }
}
