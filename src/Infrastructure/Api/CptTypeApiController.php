<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTypeApiController extends AbstractApiController
{
    private CptTypeRepositoryInterface $repository;

    public function __construct(CptTypeRepositoryInterface $repository, ConfigurationAdapter $config, ContextAdapter $context)
    {
        parent::__construct($config, $context);
        $this->repository = $repository;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $types = $this->repository->findAll($this->context->getLangId(), $this->context->getShopId());
            $data = array_map(function ($type) {
                return [
                    'id' => $type->getId(),
                    'slug' => $type->getSlug(),
                    'name' => $type->getName(),
                    'url_prefix' => $type->getUrlPrefix(),
                    'has_archive' => $type->hasArchive(),
                    'active' => $type->isActive(),
                ];
            }, $types);
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $type = $this->repository->findWithGroups($id, $this->context->getLangId(), $this->context->getShopId());
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $data = [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName(),
                'description' => $type->getDescription(),
                'config' => $type->getConfig(),
                'url_prefix' => $type->getUrlPrefix(),
                'has_archive' => $type->hasArchive(),
                'seo_config' => $type->getSeoConfig(),
                'active' => $type->isActive(),
                'acf_groups' => $type->getAcfGroups(),
            ];
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
            if ($this->repository->slugExists($data['slug'])) {
                return $this->jsonError('Slug exists', Response::HTTP_CONFLICT);
            }
            $type = new \WeprestaAcf\Domain\Entity\CptType($data);
            $id = $this->repository->save($type, $this->context->getShopId());
            if (!empty($data['acf_groups'])) {
                $this->repository->syncGroups($id, $data['acf_groups']);
            }
            if (!empty($data['taxonomies'])) {
                $this->repository->syncTaxonomies($id, $data['taxonomies']);
            }
            return $this->jsonSuccess(['id' => $id], null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $type = $this->repository->find($id);
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->jsonError('Invalid JSON', Response::HTTP_BAD_REQUEST);
            }
            if (isset($data['name']))
                $type->setName($data['name']);
            if (isset($data['slug']))
                $type->setSlug($data['slug']);
            if (isset($data['url_prefix']))
                $type->setUrlPrefix($data['url_prefix']);
            if (isset($data['has_archive']))
                $type->setHasArchive((bool) $data['has_archive']);
            if (isset($data['active']))
                $type->setActive((bool) $data['active']);
            $this->repository->save($type);
            if (isset($data['acf_groups'])) {
                $this->repository->syncGroups($id, $data['acf_groups']);
            }
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->repository->find($id)) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
