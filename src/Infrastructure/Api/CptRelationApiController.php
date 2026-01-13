<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\CptRelationRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptRelationApiController extends AbstractApiController
{
    private CptRelationRepositoryInterface $repository;

    public function __construct(CptRelationRepositoryInterface $repository, ConfigurationAdapter $config, ContextAdapter $context)
    {
        parent::__construct($config, $context);
        $this->repository = $repository;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $relations = $this->repository->findAll();
            $data = array_map(function ($relation) {
                return ['id' => $relation->getId(), 'slug' => $relation->getSlug(), 'name' => $relation->getName()];
            }, $relations);
            return $this->jsonSuccess($data);
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
            $relation = new \WeprestaAcf\Domain\Entity\CptRelation($data);
            $id = $this->repository->save($relation);
            return $this->jsonSuccess(['id' => $id], null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->repository->find($id)) {
                return $this->jsonError('Relation not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
