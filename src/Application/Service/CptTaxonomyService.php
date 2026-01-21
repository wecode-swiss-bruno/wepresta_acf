<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
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
