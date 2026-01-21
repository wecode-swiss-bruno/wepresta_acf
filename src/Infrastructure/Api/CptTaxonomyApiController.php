<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTaxonomyApiController extends AbstractApiController
{
    private CptTaxonomyRepositoryInterface $repository;
    private AutoSyncService $autoSyncService;

    public function __construct(
        CptTaxonomyRepositoryInterface $repository,
        ConfigurationAdapter $config,
        ContextAdapter $context,
        AutoSyncService $autoSyncService
    ) {
        parent::__construct($config, $context);
        $this->repository = $repository;
        $this->autoSyncService = $autoSyncService;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $taxonomies = $this->repository->findAll(null);
            $data = array_map(function ($taxonomy) {
                // Fetch terms for each taxonomy
                $fullTaxonomy = $this->repository->findWithTerms($taxonomy->getId(), $this->context->getLangId());

                $termsData = array_map(function ($term) {
                    $name = $term->getName();
                    $translations = $term->getTranslations();
                    $langId = $this->context->getLangId();

                    if (isset($translations[$langId]['name']) && !empty($translations[$langId]['name'])) {
                        $name = $translations[$langId]['name'];
                    } elseif (!empty($translations)) {
                        // Fallback to first available translation if current lang not found
                        foreach ($translations as $trans) {
                            if (!empty($trans['name'])) {
                                $name = $trans['name'];
                                break;
                            }
                        }
                    }

                    return [
                        'id' => $term->getId(),
                        'name' => $name,
                        'slug' => $term->getSlug(),
                        'active' => $term->isActive(),
                    ];
                }, $fullTaxonomy->getTerms());

                return [
                    'id' => $fullTaxonomy->getId(),
                    'slug' => $fullTaxonomy->getSlug(),
                    'name' => $fullTaxonomy->getName(),
                    'description' => $fullTaxonomy->getDescription(),
                    'active' => $fullTaxonomy->isActive(),
                    'terms' => $termsData
                ];
            }, $taxonomies);
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // Pass null for langId to fetch all translations for editor
            $taxonomy = $this->repository->findWithTerms($id, null);
            if (!$taxonomy) {
                return $this->jsonError('Taxonomy not found', Response::HTTP_NOT_FOUND);
            }

            $termsData = array_map(function ($term) {
                $name = $term->getName();
                $translations = $term->getTranslations();
                $langId = $this->context->getLangId();

                if (isset($translations[$langId]['name']) && !empty($translations[$langId]['name'])) {
                    $name = $translations[$langId]['name'];
                } elseif (!empty($translations)) {
                    // Fallback to first available translation if current lang not found
                    foreach ($translations as $trans) {
                        if (!empty($trans['name'])) {
                            $name = $trans['name'];
                            break;
                        }
                    }
                }

                return [
                    'id' => $term->getId(),
                    'name' => $name,
                    'slug' => $term->getSlug(),
                    'active' => $term->isActive(),
                ];
            }, $taxonomy->getTerms());

            return $this->jsonSuccess([
                'id' => $taxonomy->getId(),
                'slug' => $taxonomy->getSlug(),
                'name' => $taxonomy->getName(),
                'description' => $taxonomy->getDescription(),
                'hierarchical' => $taxonomy->isHierarchical(),
                'active' => $taxonomy->isActive(),
                'config' => $taxonomy->getConfig(),
                'translations' => $taxonomy->getTranslations(),
                'terms' => $termsData,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data['slug']) || empty($data['name'])) {
                return $this->jsonError('Invalid data', Response::HTTP_BAD_REQUEST);
            }
            if ($this->repository->slugExists($data['slug'])) {
                return $this->jsonError('Slug exists', Response::HTTP_CONFLICT);
            }
            $taxonomy = new \WeprestaAcf\Domain\Entity\CptTaxonomy($data);
            $id = $this->repository->save($taxonomy);

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
            $taxonomy = $this->repository->find($id);
            if (!$taxonomy) {
                return $this->jsonError('Taxonomy not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (isset($data['name'])) {
                $taxonomy->setName($data['name']);
            }
            if (isset($data['slug'])) {
                $taxonomy->setSlug($data['slug']);
            }
            if (isset($data['description'])) {
                $taxonomy->setDescription($data['description']);
            }
            if (isset($data['hierarchical'])) {
                $taxonomy->setHierarchical((bool) $data['hierarchical']);
            }
            if (isset($data['active'])) {
                $taxonomy->setActive((bool) $data['active']);
            }
            if (isset($data['config'])) {
                $taxonomy->setConfig(is_string($data['config']) ? json_decode($data['config'], true) : $data['config']);
            }
            if (isset($data['translations']) && is_array($data['translations'])) {
                $taxonomy->setTranslations($data['translations']);
            }
            $this->repository->save($taxonomy);

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
                return $this->jsonError('Taxonomy not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);

            // Trigger auto-sync
            $this->autoSyncService->markDirty();

            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
