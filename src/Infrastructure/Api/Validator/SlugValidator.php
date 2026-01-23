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

namespace WeprestaAcf\Infrastructure\Api\Validator;

if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

/**
 * Validates and resolves slugs for fields and groups.
 */
final class SlugValidator
{
    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly SlugGenerator $slugGenerator
    ) {
    }

    /**
     * Resolve a field slug, generating one from title if not provided.
     * Returns null if the slug already exists in the group.
     *
     * @param string|null $slug Provided slug (optional)
     * @param string $title Field title (used to generate slug if not provided)
     * @param int $groupId Group ID to check uniqueness within
     * @param int|null $excludeFieldId Field ID to exclude from uniqueness check (for updates)
     *
     * @return string|null Resolved slug or null if invalid/duplicate
     */
    public function resolveFieldSlug(
        ?string $slug,
        string $title,
        int $groupId,
        ?int $excludeFieldId = null
    ): ?string {
        // Generate slug from title if not provided
        if (empty($slug)) {
            $slug = $this->slugGenerator->generateUnique(
                $title,
                fn(string $testSlug, ?int $excludeId) => $this->fieldSlugExists($testSlug, $groupId, $excludeId),
                $excludeFieldId
            );

            return $slug;
        }

        // Sanitize provided slug
        $slug = $this->slugGenerator->generate($slug);

        // Check if slug already exists in group
        if ($this->fieldSlugExists($slug, $groupId, $excludeFieldId)) {
            return null;
        }

        return $slug;
    }

    /**
     * Resolve a group slug, generating one from title if not provided.
     * Returns null if the slug already exists.
     *
     * @param string|null $slug Provided slug (optional)
     * @param string $title Group title (used to generate slug if not provided)
     * @param int|null $excludeGroupId Group ID to exclude from uniqueness check (for updates)
     *
     * @return string|null Resolved slug or null if invalid/duplicate
     */
    public function resolveGroupSlug(
        ?string $slug,
        string $title,
        ?int $excludeGroupId = null
    ): ?string {
        // Generate slug from title if not provided
        if (empty($slug)) {
            $slug = $this->slugGenerator->generateUnique(
                $title,
                fn(string $testSlug, ?int $excludeId) => $this->groupSlugExists($testSlug, $excludeId),
                $excludeGroupId
            );

            return $slug;
        }

        // Sanitize provided slug
        $slug = $this->slugGenerator->generate($slug);

        // Check if slug already exists
        if ($this->groupSlugExists($slug, $excludeGroupId)) {
            return null;
        }

        return $slug;
    }

    /**
     * Check if a field slug exists within a group.
     */
    private function fieldSlugExists(string $slug, int $groupId, ?int $excludeFieldId = null): bool
    {
        $existing = $this->fieldRepository->findOneBy([
            'slug' => $slug,
            'id_wepresta_acf_group' => $groupId,
        ]);

        if ($existing === null) {
            return false;
        }

        // If we're updating and found the same field, it's ok
        if ($excludeFieldId !== null && (int) $existing['id_wepresta_acf_field'] === $excludeFieldId) {
            return false;
        }

        return true;
    }

    /**
     * Check if a group slug exists.
     */
    private function groupSlugExists(string $slug, ?int $excludeGroupId = null): bool
    {
        $existing = $this->groupRepository->findOneBy(['slug' => $slug]);

        if ($existing === null) {
            return false;
        }

        // If we're updating and found the same group, it's ok
        if ($excludeGroupId !== null && (int) $existing['id_wepresta_acf_group'] === $excludeGroupId) {
            return false;
        }

        return true;
    }
}
