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

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTermRepositoryInterface;
use WeprestaAcf\Domain\Entity\CptType;
use WeprestaAcf\Domain\Entity\CptTaxonomy;
use WeprestaAcf\Domain\Entity\CptTerm;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sync Service for CPT - Export/Import to JSON templates
 */
final class CptSyncService
{
    private CptTypeRepositoryInterface $typeRepository;
    private CptTaxonomyRepositoryInterface $taxonomyRepository;
    private CptTermRepositoryInterface $termRepository;

    public function __construct(
        CptTypeRepositoryInterface $typeRepository,
        CptTaxonomyRepositoryInterface $taxonomyRepository,
        CptTermRepositoryInterface $termRepository
    ) {
        $this->typeRepository = $typeRepository;
        $this->taxonomyRepository = $taxonomyRepository;
        $this->termRepository = $termRepository;
    }

    /**
     * Export CPT type to JSON
     */
    public function exportType(int $typeId): array
    {
        $type = $this->typeRepository->findWithGroups($typeId);

        if (!$type) {
            throw new \Exception("Type not found: $typeId");
        }

        // Get taxonomies
        $taxonomies = $this->taxonomyRepository->findByType($typeId);

        $export = [
            'slug' => $type->getSlug(),
            'name' => $type->getName(),
            'description' => $type->getDescription(),
            'config' => $type->getConfig(),
            'url_prefix' => $type->getUrlPrefix(),
            'has_archive' => $type->hasArchive(),
            'archive_slug' => $type->getArchiveSlug(),
            'seo_config' => $type->getSeoConfig(),
            'icon' => $type->getIcon(),
            'acf_groups' => array_column($type->getAcfGroups(), 'slug'),
            'taxonomies' => array_map(function ($taxonomy) {
                return $this->exportTaxonomy($taxonomy->getId());
            }, $taxonomies),
        ];

        return $export;
    }

    /**
     * Export taxonomy with terms
     */
    public function exportTaxonomy(int $taxonomyId): array
    {
        $taxonomy = $this->taxonomyRepository->findWithTerms($taxonomyId);

        if (!$taxonomy) {
            throw new \Exception("Taxonomy not found: $taxonomyId");
        }

        $terms = $this->termRepository->getTree($taxonomyId);

        return [
            'slug' => $taxonomy->getSlug(),
            'name' => $taxonomy->getName(),
            'description' => $taxonomy->getDescription(),
            'hierarchical' => $taxonomy->isHierarchical(),
            'terms' => $this->exportTermsTree($terms),
        ];
    }

    /**
     * Export terms tree recursively
     */
    private function exportTermsTree(array $terms): array
    {
        return array_map(function ($term) {
            $data = [
                'slug' => $term->getSlug(),
                'name' => $term->getName(),
                'description' => $term->getDescription(),
                'position' => $term->getPosition(),
            ];

            if (!empty($term->getChildren())) {
                $data['children'] = $this->exportTermsTree($term->getChildren());
            }

            return $data;
        }, $terms);
    }

    /**
     * Export all CPT configuration
     */
    public function exportAll(): array
    {
        $types = $this->typeRepository->findAll();

        return [
            'version' => '1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'cpt_types' => array_map(function ($type) {
                return $this->exportType($type->getId());
            }, $types),
        ];
    }

    /**
     * Import CPT type from JSON
     */
    public function importType(array $data, bool $overwrite = false): int
    {
        // Check if type exists
        $existingType = $this->typeRepository->findBySlug($data['slug']);

        if ($existingType && !$overwrite) {
            throw new \Exception("Type with slug '{$data['slug']}' already exists");
        }

        // Import taxonomies first
        $taxonomyMap = [];
        if (!empty($data['taxonomies'])) {
            foreach ($data['taxonomies'] as $taxData) {
                $taxonomyId = $this->importTaxonomy($taxData, $overwrite);
                $taxonomyMap[$taxData['slug']] = $taxonomyId;
            }
        }

        // Create/update type
        if ($existingType && $overwrite) {
            $type = $existingType;
            $type->setName($data['name']);
            $type->setDescription($data['description'] ?? null);
        } else {
            $type = new CptType($data);
        }

        $typeId = $this->typeRepository->save($type);

        // Sync taxonomies
        if (!empty($taxonomyMap)) {
            $this->typeRepository->syncTaxonomies($typeId, array_values($taxonomyMap));
        }

        // Sync ACF groups (by slug, need to resolve IDs)
        // TODO: Implement ACF group slug resolution

        return $typeId;
    }

    /**
     * Import taxonomy from JSON
     */
    public function importTaxonomy(array $data, bool $overwrite = false): int
    {
        // Check if taxonomy exists
        $existingTaxonomy = $this->taxonomyRepository->findBySlug($data['slug']);

        if ($existingTaxonomy && !$overwrite) {
            return $existingTaxonomy->getId();
        }

        // Create/update taxonomy
        if ($existingTaxonomy && $overwrite) {
            $taxonomy = $existingTaxonomy;
            $taxonomy->setName($data['name']);
            $taxonomy->setDescription($data['description'] ?? null);
        } else {
            $taxonomy = new CptTaxonomy($data);
        }

        $taxonomyId = $this->taxonomyRepository->save($taxonomy);

        // Import terms
        if (!empty($data['terms'])) {
            $this->importTermsTree($data['terms'], $taxonomyId);
        }

        return $taxonomyId;
    }

    /**
     * Import terms tree recursively
     */
    private function importTermsTree(array $terms, int $taxonomyId, ?int $parentId = null): void
    {
        foreach ($terms as $termData) {
            $termData['id_wepresta_acf_cpt_taxonomy'] = $taxonomyId;
            $termData['id_parent'] = $parentId;

            $term = new CptTerm($termData);
            $termId = $this->termRepository->save($term);

            // Import children recursively
            if (!empty($termData['children'])) {
                $this->importTermsTree($termData['children'], $taxonomyId, $termId);
            }
        }
    }

    /**
     * Save export to file
     */
    public function saveToFile(array $export, string $filename): string
    {
        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $syncDir = _PS_MODULE_DIR_ . 'wepresta_acf/sync/cpt/';

        if (!is_dir($syncDir)) {
            mkdir($syncDir, 0755, true);
        }

        $filepath = $syncDir . $filename . '.json';
        file_put_contents($filepath, $json);

        return $filepath;
    }

    /**
     * Load from file
     */
    public function loadFromFile(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new \Exception("File not found: $filepath");
        }

        $content = file_get_contents($filepath);

        if (!$content) {
            throw new \Exception("Failed to read file: $filepath");
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }

        return $data;
    }
}
