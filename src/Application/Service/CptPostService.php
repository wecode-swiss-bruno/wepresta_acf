<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Entity\CptPost;
use WeprestaAcf\Domain\Repository\CptPostRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptPostService
{
    private CptPostRepositoryInterface $repository;
    private ContextAdapter $context;

    public function __construct(CptPostRepositoryInterface $repository, ContextAdapter $context)
    {
        $this->repository = $repository;
        $this->context = $context;
    }

    public function getPostById(int $id): ?CptPost
    {
        return $this->repository->find($id, $this->context->getLangId(), $this->context->getShopId());
    }

    public function getPostBySlug(string $slug, int $typeId): ?CptPost
    {
        return $this->repository->findBySlug($slug, $typeId, $this->context->getLangId(), $this->context->getShopId());
    }

    public function getPostsByType(int $typeId, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findByType($typeId, $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);
    }

    public function getPublishedPostsByType(int $typeId, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findPublishedByType($typeId, $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);
    }

    public function getPostsByTerm(int $termId, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findByTerm($termId, $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);
    }

    public function countPostsByType(int $typeId, ?string $status = null): int
    {
        return $this->repository->countByType($typeId, $this->context->getShopId(), $status);
    }

    public function createPost(array $data): int
    {
        if (!isset($data['id_employee'])) {
            $data['id_employee'] = $this->context->getEmployeeId() ?: null;
        }
        $post = new CptPost($data);
        return $this->repository->save($post, $this->context->getShopId());
    }

    public function updatePost(int $id, array $data): bool
    {
        $post = $this->repository->find($id);
        if (!$post) {
            return false;
        }
        if (isset($data['slug']))
            $post->setSlug($data['slug']);
        if (isset($data['title']))
            $post->setTitle($data['title']);
        if (isset($data['status']))
            $post->setStatus($data['status']);
        $this->repository->save($post);
        return true;
    }

    public function deletePost(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function publishPost(int $id): bool
    {
        $post = $this->repository->find($id);
        if (!$post) {
            return false;
        }
        $post->publish();
        $this->repository->save($post);
        return true;
    }

    public function unpublishPost(int $id): bool
    {
        $post = $this->repository->find($id);
        if (!$post) {
            return false;
        }
        $post->unpublish();
        $this->repository->save($post);
        return true;
    }

    public function slugExists(string $slug, int $typeId, ?int $excludeId = null): bool
    {
        return $this->repository->slugExists($slug, $typeId, $excludeId);
    }

    public function generateUniqueSlug(string $title, int $typeId, ?int $excludeId = null): string
    {
        $slug = \Tools::str2url($title);
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug, $typeId, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            ++$counter;
        }
        return $slug;
    }

    public function syncTerms(int $postId, array $termIds): void
    {
        $this->repository->syncTerms($postId, $termIds);
    }
}
