<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Domain\Repository\CptTermRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTermApiController extends AbstractApiController
{
    private CptTermRepositoryInterface $repository;
    private CptTaxonomyRepositoryInterface $taxonomyRepository;
    private AutoSyncService $autoSyncService;

    public function __construct(
        CptTermRepositoryInterface $repository,
        CptTaxonomyRepositoryInterface $taxonomyRepository,
        ConfigurationAdapter $config,
        ContextAdapter $context,
        AutoSyncService $autoSyncService
    ) {
        parent::__construct($config, $context);
        $this->repository = $repository;
        $this->taxonomyRepository = $taxonomyRepository;
        $this->autoSyncService = $autoSyncService;
    }

    public function listByTaxonomy(int $taxonomyId, Request $request): JsonResponse
    {
        try {
            $taxonomy = $this->taxonomyRepository->find($taxonomyId, $this->context->getLangId());
            if (!$taxonomy) {
                return $this->jsonError('Taxonomy not found', Response::HTTP_NOT_FOUND);
            }
            $tree = $request->query->get('tree', false);
            $terms = $tree ? $this->repository->getTree($taxonomyId, $this->context->getLangId()) : $this->repository->findByTaxonomy($taxonomyId, $this->context->getLangId());
            $data = array_map(fn($term) => $this->serializeTerm($term), $terms);
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // Pass null for langId to fetch all translations
            $term = $this->repository->find($id, null);
            if (!$term) {
                return $this->jsonError('Term not found', Response::HTTP_NOT_FOUND);
            }
            $data = $this->serializeTerm($term);
            $data['post_count'] = $this->repository->countPosts($id);
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(int $taxonomyId, Request $request): JsonResponse
    {
        try {
            $taxonomy = $this->taxonomyRepository->find($taxonomyId);
            if (!$taxonomy) {
                return $this->jsonError('Taxonomy not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data['slug']) || empty($data['name'])) {
                return $this->jsonError('Invalid data', Response::HTTP_BAD_REQUEST);
            }
            if ($this->repository->slugExists($data['slug'], $taxonomyId)) {
                return $this->jsonError('Slug exists', Response::HTTP_CONFLICT);
            }
            $data['id_wepresta_acf_cpt_taxonomy'] = $taxonomyId;
            $term = new \WeprestaAcf\Domain\Entity\CptTerm($data);
            $id = $this->repository->save($term);
            
            // Trigger auto-sync
            $this->autoSyncService->markDirty();
            
            return $this->jsonSuccess(['id' => $id], null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            // Load with all translations (null = all langs)
            $term = $this->repository->find($id, null);
            if (!$term) {
                return $this->jsonError('Term not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (isset($data['name']))
                $term->setName($data['name']);
            if (isset($data['translations']) && is_array($data['translations'])) {
                $term->setTranslations($data['translations']);
            }
            $this->repository->save($term);
            
            // Trigger auto-sync
            $this->autoSyncService->markDirty();
            
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->repository->find($id)) {
                return $this->jsonError('Term not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);
            
            // Trigger auto-sync
            $this->autoSyncService->markDirty();
            
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function serializeTerm($term): array
    {
        $data = [
            'id' => $term->getId(),
            'slug' => $term->getSlug(),
            'name' => $term->getName(),
            'description' => $term->getDescription(),
            'translations' => $term->getTranslations(),
            'parent_id' => $term->getParentId(),
        ];
        if (!empty($term->getChildren())) {
            $data['children'] = array_map(fn($child) => $this->serializeTerm($child), $term->getChildren());
        }
        return $data;
    }
}
