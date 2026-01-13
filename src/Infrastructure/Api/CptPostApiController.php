<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\CptPostRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptPostApiController extends AbstractApiController
{
    private CptPostRepositoryInterface $repository;
    private CptTypeRepositoryInterface $typeRepository;

    public function __construct(CptPostRepositoryInterface $repository, CptTypeRepositoryInterface $typeRepository, ConfigurationAdapter $config, ContextAdapter $context)
    {
        parent::__construct($config, $context);
        $this->repository = $repository;
        $this->typeRepository = $typeRepository;
    }

    public function listByType(string $typeSlug, Request $request): JsonResponse
    {
        try {
            $type = $this->typeRepository->findBySlug($typeSlug, $this->context->getLangId(), $this->context->getShopId());
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $limit = (int) $request->query->get('limit', 50);
            $offset = (int) $request->query->get('offset', 0);
            $status = $request->query->get('status');

            $posts = $status === 'published'
                ? $this->repository->findPublishedByType($type->getId(), $this->context->getLangId(), $this->context->getShopId(), $limit, $offset)
                : $this->repository->findByType($type->getId(), $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);

            $total = $this->repository->countByType($type->getId(), $this->context->getShopId(), $status);

            $data = array_map(function ($post) {
                return [
                    'id' => $post->getId(),
                    'slug' => $post->getSlug(),
                    'title' => $post->getTitle(),
                    'status' => $post->getStatus(),
                ];
            }, $posts);

            return $this->jsonSuccess(['posts' => $data, 'total' => $total]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $post = $this->repository->find($id, $this->context->getLangId(), $this->context->getShopId());
            if (!$post) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $data = [
                'id' => $post->getId(),
                'type_id' => $post->getTypeId(),
                'slug' => $post->getSlug(),
                'title' => $post->getTitle(),
                'status' => $post->getStatus(),
                'terms' => $post->getTerms(),
            ];
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(string $typeSlug, Request $request): JsonResponse
    {
        try {
            $type = $this->typeRepository->findBySlug($typeSlug, $this->context->getLangId(), $this->context->getShopId());
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data['slug']) || empty($data['title'])) {
                return $this->jsonError('Invalid data', Response::HTTP_BAD_REQUEST);
            }
            if ($this->repository->slugExists($data['slug'], $type->getId())) {
                return $this->jsonError('Slug exists', Response::HTTP_CONFLICT);
            }
            $data['id_wepresta_acf_cpt_type'] = $type->getId();
            $data['id_employee'] = $this->context->getEmployeeId() ?: null;
            $post = new \WeprestaAcf\Domain\Entity\CptPost($data);
            $id = $this->repository->save($post, $this->context->getShopId());
            if (!empty($data['terms'])) {
                $this->repository->syncTerms($id, $data['terms']);
            }
            return $this->jsonSuccess(['id' => $id], null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $post = $this->repository->find($id);
            if (!$post) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->jsonError('Invalid JSON', Response::HTTP_BAD_REQUEST);
            }
            if (isset($data['title']))
                $post->setTitle($data['title']);
            if (isset($data['slug']))
                $post->setSlug($data['slug']);
            if (isset($data['status']))
                $post->setStatus($data['status']);
            $this->repository->save($post);
            if (isset($data['terms'])) {
                $this->repository->syncTerms($id, $data['terms']);
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
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeStatus(int $id, Request $request): JsonResponse
    {
        try {
            $post = $this->repository->find($id);
            if (!$post) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (empty($data['status'])) {
                return $this->jsonError('Status required', Response::HTTP_BAD_REQUEST);
            }
            $post->setStatus($data['status']);
            $this->repository->save($post);
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
