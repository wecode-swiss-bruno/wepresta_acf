<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Validator;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

/**
 * Slug validation service.
 */
final class SlugValidator
{
    public function __construct(
        private readonly SlugGenerator $slugGenerator,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository
    ) {
    }

    /**
     * Resolve and validate group slug.
     * Returns null if slug already exists (error case).
     */
    public function resolveGroupSlug(
        string $slug,
        string $title,
        ?int $excludeId = null
    ): ?string {
        $slug = trim($slug);

        // Generate from title if empty
        if (empty($slug) || $slug === '-') {
            return $this->slugGenerator->generateUnique(
                $title,
                fn ($s, $id) => $this->groupRepository->slugExists($s, $id),
                $excludeId
            );
        }

        // Normalize provided slug
        $slug = $this->slugGenerator->generate($slug);

        if (empty($slug)) {
            return $this->slugGenerator->generateUnique(
                $title,
                fn ($s, $id) => $this->groupRepository->slugExists($s, $id),
                $excludeId
            );
        }

        // Check uniqueness
        if ($this->groupRepository->slugExists($slug, $excludeId)) {
            return null;
        }

        return $slug;
    }

    /**
     * Resolve and validate field slug within a group.
     * Returns null if slug already exists (error case).
     */
    public function resolveFieldSlug(
        string $slug,
        string $title,
        int $groupId,
        ?int $excludeId = null,
        ?string $currentSlug = null
    ): ?string {
        $slug = trim($slug);

        // Generate from title if empty
        if (empty($slug) || $slug === '-') {
            return $this->slugGenerator->generateUnique(
                $title,
                fn ($s, $id) => $this->fieldRepository->slugExistsInGroup($s, $groupId, $id),
                $excludeId
            );
        }

        // Normalize provided slug
        $slug = $this->slugGenerator->generate($slug);

        if (empty($slug)) {
            return $this->slugGenerator->generateUnique(
                $title,
                fn ($s, $id) => $this->fieldRepository->slugExistsInGroup($s, $groupId, $id),
                $excludeId
            );
        }

        // Check uniqueness (skip if unchanged)
        if ($slug !== $currentSlug && $this->fieldRepository->slugExistsInGroup($slug, $groupId, $excludeId)) {
            return null;
        }

        return $slug;
    }
}
