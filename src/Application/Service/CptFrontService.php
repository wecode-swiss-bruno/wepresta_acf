<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\CptPostRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptFrontService
{
    private CptPostRepositoryInterface $postRepository;
    private CptTypeRepositoryInterface $typeRepository;
    private \WeprestaAcf\Domain\Repository\CptTermRepositoryInterface $termRepository;
    private ContextAdapter $context;
    private ?int $currentPostId = null;
    private ?string $currentTypeSlug = null;

    public function __construct(
        CptPostRepositoryInterface $postRepository,
        CptTypeRepositoryInterface $typeRepository,
        \WeprestaAcf\Domain\Repository\CptTermRepositoryInterface $termRepository,
        ContextAdapter $context
    ) {
        $this->postRepository = $postRepository;
        $this->typeRepository = $typeRepository;
        $this->termRepository = $termRepository;
        $this->context = $context;
    }

    public function forPost(int $postId): self
    {
        $this->currentPostId = $postId;
        return $this;
    }

    public function forType(string $typeSlug): self
    {
        $this->currentTypeSlug = $typeSlug;
        return $this;
    }

    public function getCurrentPost()
    {
        if (!$this->currentPostId) {
            return null;
        }
        return $this->postRepository->find($this->currentPostId, $this->context->getLangId(), $this->context->getShopId());
    }

    public function getPostTerms(int $postId): array
    {
        return $this->termRepository->findByPostId($postId, $this->context->getLangId());
    }

    public function getArchivePosts(int $limit = 10, int $offset = 0): array
    {
        if (!$this->currentTypeSlug) {
            return [];
        }
        $type = $this->typeRepository->findBySlug($this->currentTypeSlug, $this->context->getLangId(), $this->context->getShopId());
        if (!$type) {
            return [];
        }
        return $this->postRepository->findPublishedByType($type->getId(), $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);
    }

    public function getTotalPosts(): int
    {
        if (!$this->currentTypeSlug) {
            return 0;
        }
        $type = $this->typeRepository->findBySlug($this->currentTypeSlug, $this->context->getLangId(), $this->context->getShopId());
        if (!$type) {
            return 0;
        }
        return $this->postRepository->countByType($type->getId(), $this->context->getShopId(), 'published');
    }

    public function getPostsByTerm(int $termId, int $limit = 10, int $offset = 0): array
    {
        return $this->postRepository->findByTerm($termId, $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);
    }

    public function field(string $fieldName)
    {
        $post = $this->getCurrentPost();
        if (!$post) {
            return null;
        }
        $method = 'get' . str_replace('_', '', ucwords($fieldName, '_'));
        if (method_exists($post, $method)) {
            return $post->{$method}();
        }
        return null;
    }

    public function has(string $fieldName): bool
    {
        return !empty($this->field($fieldName));
    }
}
