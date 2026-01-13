<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTaxonomyApiController extends AbstractApiController
{
    private CptTaxonomyRepositoryInterface $repository;

    public function __construct(CptTaxonomyRepositoryInterface $repository, ConfigurationAdapter $config, ContextAdapter $context)
    {
        parent::__construct($config, $context);
        $this->repository = $repository;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $taxonomies = $this->repository->findAll($this->context->getLangId());
            $data = array_map(function ($taxonomy) {
                return ['id' => $taxonomy->getId(), 'slug' => $taxonomy->getSlug(), 'name' => $taxonomy->getName()];
            }, $taxonomies);
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $taxonomy = $this->repository->findWithTerms($id, $this->context->getLangId());
            if (!$taxonomy) {
                return $this->jsonError('Taxonomy not found', Response::HTTP_NOT_FOUND);
            }
            return $this->jsonSuccess([
                'id' => $taxonomy->getId(),
                'slug' => $taxonomy->getSlug(),
                'name' => $taxonomy->getName(),
                'terms' => $taxonomy->getTerms(),
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
            if (isset($data['name']))
                $taxonomy->setName($data['name']);
            $this->repository->save($taxonomy);
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
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
