<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Exception;
use JsonException;
use Module;
use WeprestaAcf\Application\Template\ImportResult;
use WeprestaAcf\Domain\Entity\CptPost;
use WeprestaAcf\Domain\Entity\CptTaxonomy;
use WeprestaAcf\Domain\Entity\CptTerm;
use WeprestaAcf\Domain\Entity\CptType;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptPostRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTermRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Trait\LoggerTrait;

/**
 * Simplified Export/Import service for ACF field groups.
 * KISS approach: export to JSON, import from JSON, no sync complexity.
 */
final class ExportImportService
{
    use LoggerTrait;

    private const JSON_VERSION = '1.0';

    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $fieldValueRepository,
        private readonly CptTypeRepositoryInterface $cptTypeRepository,
        private readonly CptTaxonomyRepositoryInterface $cptTaxonomyRepository,
        private readonly CptTermRepositoryInterface $cptTermRepository,
        private readonly CptPostRepositoryInterface $cptPostRepository
    ) {
    }

    /**
     * Export all groups AND CPT to JSON format.
     *
     * @return array<string, mixed> Export data structure
     */
    public function exportAll(): array
    {
        // Export ACF groups
        $groups = $this->groupRepository->findAll();
        $exportedGroups = [];

        foreach ($groups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findAllByGroup($groupId);
            $fieldValues = $this->fieldValueRepository->findAllByGroup($groupId);
            $exportedGroups[] = $this->formatGroupForExport($group, $fields, $fieldValues);
        }

        // Export CPT types
        $cptTypes = $this->cptTypeRepository->findAll();
        $exportedCptTypes = [];

        foreach ($cptTypes as $type) {
            $exportedCptTypes[] = $this->formatCptTypeForExport($type);
        }

        // Export CPT taxonomies
        $cptTaxonomies = $this->cptTaxonomyRepository->findAll();
        $exportedTaxonomies = [];

        foreach ($cptTaxonomies as $taxonomy) {
            $exportedTaxonomies[] = $this->formatCptTaxonomyForExport($taxonomy);
        }

        // Export CPT posts (all posts from all types)
        $exportedPosts = [];

        foreach ($cptTypes as $type) {
            // Pass null for langId to get all posts regardless of language
            // Pass null for shopId to get all posts regardless of shop
            // Use high limit to get all posts
            $posts = $this->cptPostRepository->findByType($type->getId(), null, null, 10000, 0);

            $this->logInfo('Exporting CPT posts', [
                'type' => $type->getSlug(),
                'type_id' => $type->getId(),
                'posts_count' => count($posts),
            ]);

            foreach ($posts as $post) {
                $exportedPosts[] = $this->formatCptPostForExport($post, $type->getSlug());
            }
        }

        $this->logInfo('Export complete', [
            'groups' => count($exportedGroups),
            'cpt_types' => count($exportedCptTypes),
            'cpt_taxonomies' => count($exportedTaxonomies),
            'cpt_posts' => count($exportedPosts),
        ]);

        $module = Module::getInstanceByName('wepresta_acf');
        $moduleVersion = $module ? $module::VERSION : '1.0.0';

        return [
            'version' => self::JSON_VERSION,
            'exported_at' => date('c'),
            'module_version' => $moduleVersion,
            'prestashop_version' => _PS_VERSION_,
            'groups' => $exportedGroups,
            'cpt_types' => $exportedCptTypes,
            'cpt_taxonomies' => $exportedTaxonomies,
            'cpt_posts' => $exportedPosts,
        ];
    }

    /**
     * Export a single group to JSON format.
     *
     * @param int $groupId Group ID
     *
     * @return array<string, mixed>|null Export data structure or null if group not found
     */
    public function exportGroup(int $groupId): ?array
    {
        $group = $this->groupRepository->findById($groupId);

        if ($group === null) {
            return null;
        }

        $fields = $this->fieldRepository->findAllByGroup($groupId);
        $fieldValues = $this->fieldValueRepository->findAllByGroup($groupId);
        $exportedGroup = $this->formatGroupForExport($group, $fields, $fieldValues);

        $module = Module::getInstanceByName('wepresta_acf');
        $moduleVersion = $module ? $module::VERSION : '1.0.0';

        return [
            'version' => self::JSON_VERSION,
            'exported_at' => date('c'),
            'module_version' => $moduleVersion,
            'prestashop_version' => _PS_VERSION_,
            'group' => $exportedGroup,
        ];
    }

    /**
     * Import groups with replace mode (delete all existing, then import).
     *
     * @param array<string, mixed> $data Import data
     *
     * @return ImportResult Import result with details
     */
    public function importReplace(array $data): ImportResult
    {
        $result = new ImportResult(true);
        $result->setVersion($data['version'] ?? 'unknown');

        // Validate data first
        $validation = $this->validateImportData($data);

        if (! $validation['valid']) {
            $result = new ImportResult(false, $validation['error']);

            foreach ($validation['errors'] as $error) {
                $result->addError('validation', $error);
            }

            return $result;
        }

        // Get groups to import
        $groupsToImport = $data['groups'] ?? [];

        if (empty($groupsToImport) && isset($data['group'])) {
            $groupsToImport = [$data['group']];
        }

        if (empty($groupsToImport)) {
            return new ImportResult(false, 'No groups to import');
        }

        // Delete all existing ACF groups
        $allGroups = $this->groupRepository->findAll();
        $deletedCount = 0;

        foreach ($allGroups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $this->fieldRepository->deleteByGroup($groupId);

            if ($this->groupRepository->delete($groupId)) {
                ++$deletedCount;
            }
        }

        $this->logInfo('Deleted all ACF groups for replace import', ['count' => $deletedCount]);

        // Delete all existing CPT posts FIRST (before deleting types/taxonomies)
        // This prevents cascade delete issues and allows proper re-import
        $allCptTypes = $this->cptTypeRepository->findAll();
        $deletedPostsCount = 0;

        foreach ($allCptTypes as $type) {
            $posts = $this->cptPostRepository->findByType($type->getId());

            foreach ($posts as $post) {
                // Delete ACF values for this post first
                $this->fieldValueRepository->deleteByEntity('cpt_post', $post->getId());

                // Then delete the post
                if ($this->cptPostRepository->delete($post->getId())) {
                    ++$deletedPostsCount;
                }
            }
        }

        $this->logInfo('Deleted all CPT posts for replace import', ['count' => $deletedPostsCount]);

        // Now delete CPT types (no cascade issues)
        $deletedCptCount = 0;

        foreach ($allCptTypes as $type) {
            if ($this->cptTypeRepository->delete($type->getId())) {
                ++$deletedCptCount;
            }
        }

        $this->logInfo('Deleted all CPT types for replace import', ['count' => $deletedCptCount]);

        // Delete all existing CPT taxonomies (cascades to terms via DB constraints)
        $allTaxonomies = $this->cptTaxonomyRepository->findAll();
        $deletedTaxCount = 0;

        foreach ($allTaxonomies as $taxonomy) {
            if ($this->cptTaxonomyRepository->delete($taxonomy->getId())) {
                ++$deletedTaxCount;
            }
        }

        $this->logInfo('Deleted all CPT taxonomies for replace import', ['count' => $deletedTaxCount]);

        // Import ACF groups
        foreach ($groupsToImport as $groupData) {
            try {
                $this->importGroup($groupData, $result);
            } catch (Exception $e) {
                $result->addError($groupData['slug'] ?? 'unknown', $e->getMessage());
                $this->logError('Import group failed', [
                    'slug' => $groupData['slug'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Import CPT taxonomies first (needed for types)
        $taxonomiesMap = [];

        foreach ($data['cpt_taxonomies'] ?? [] as $taxData) {
            try {
                $taxonomyId = $this->importCptTaxonomy($taxData, $result);
                $taxonomiesMap[$taxData['slug']] = $taxonomyId;
            } catch (Exception $e) {
                $result->addError('taxonomy_' . ($taxData['slug'] ?? 'unknown'), $e->getMessage());
                $this->logError('Import CPT taxonomy failed', [
                    'slug' => $taxData['slug'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Import CPT types
        $typesMap = [];

        foreach ($data['cpt_types'] ?? [] as $typeData) {
            try {
                $typeId = $this->importCptType($typeData, $taxonomiesMap, $result);
                $typesMap[$typeData['slug']] = $typeId;
            } catch (Exception $e) {
                $result->addError('cpt_' . ($typeData['slug'] ?? 'unknown'), $e->getMessage());
                $this->logError('Import CPT type failed', [
                    'slug' => $typeData['slug'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check if at least one group was successfully imported
        // Import CPT posts (with ACF values)
        $this->logInfo('Importing CPT posts', [
            'posts_count' => count($data['cpt_posts'] ?? []),
            'typesMap' => $typesMap,
        ]);

        foreach ($data['cpt_posts'] ?? [] as $postData) {
            try {
                $postId = $this->importCptPost($postData, $typesMap, $result);
                $this->logInfo('CPT Post imported successfully', [
                    'slug' => $postData['slug'],
                    'id' => $postId,
                ]);
            } catch (Exception $e) {
                $result->addError('post_' . ($postData['slug'] ?? 'unknown'), $e->getMessage());
                $this->logError('Import CPT post failed', [
                    'slug' => $postData['slug'] ?? 'unknown',
                    'type_slug' => $postData['type_slug'] ?? 'unknown',
                    'typesMap' => $typesMap,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $importedCount = \count($result->getCreated());

        if ($importedCount === 0 && ! empty($groupsToImport)) {
            // No groups were imported but we had groups to import - this is a failure
            $result->addError('import', 'No groups were successfully imported');
            $result->setMessage('Import failed: No groups were imported');
        } else {
            $result->setMessage(\sprintf(
                '%d groups imported',
                $importedCount
            ));
        }

        return $result;
    }

    /**
     * Import groups with merge mode (add/update without deleting existing).
     *
     * @param array<string, mixed> $data Import data
     *
     * @return ImportResult Import result with details
     */
    public function importMerge(array $data): ImportResult
    {
        $result = new ImportResult(true);
        $result->setVersion($data['version'] ?? 'unknown');

        // Validate data first
        $validation = $this->validateImportData($data);

        if (! $validation['valid']) {
            $result = new ImportResult(false, $validation['error']);

            foreach ($validation['errors'] as $error) {
                $result->addError('validation', $error);
            }

            return $result;
        }

        // Get groups to import
        $groupsToImport = $data['groups'] ?? [];

        if (empty($groupsToImport) && isset($data['group'])) {
            $groupsToImport = [$data['group']];
        }

        // Check if we have anything to import (groups OR CPT)
        $hasCptData = !empty($data['cpt_types']) || !empty($data['cpt_taxonomies']) || !empty($data['cpt_posts']);

        if (empty($groupsToImport) && !$hasCptData) {
            return new ImportResult(false, 'No data to import');
        }

        // Import each group (create or update)
        foreach ($groupsToImport as $groupData) {
            try {
                $slug = $groupData['slug'] ?? '';

                if (empty($slug)) {
                    $result->addError('unknown', 'Group slug is required');

                    continue;
                }

                $existing = $this->groupRepository->findBySlug($slug);

                if ($existing !== null) {
                    // Update existing group
                    $this->updateGroup((int) $existing['id_wepresta_acf_group'], $groupData, $result);
                } else {
                    // Create new group
                    $this->createGroup($groupData, $result);
                }
            } catch (Exception $e) {
                $result->addError($groupData['slug'] ?? 'unknown', $e->getMessage());
                $this->logError('Import group failed', [
                    'slug' => $groupData['slug'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Import CPT taxonomies (create or update)
        $taxonomiesMap = [];

        foreach ($data['cpt_taxonomies'] ?? [] as $taxData) {
            try {
                $slug = $taxData['slug'] ?? '';

                if (empty($slug)) {
                    continue;
                }

                $existing = $this->cptTaxonomyRepository->findBySlug($slug);

                if ($existing !== null) {
                    // Update existing taxonomy
                    $this->updateCptTaxonomy($existing->getId(), $taxData, $result);
                    $taxonomiesMap[$slug] = $existing->getId();
                } else {
                    // Create new taxonomy
                    $taxonomyId = $this->importCptTaxonomy($taxData, $result);
                    $taxonomiesMap[$slug] = $taxonomyId;
                }
            } catch (Exception $e) {
                $result->addError('taxonomy_' . ($taxData['slug'] ?? 'unknown'), $e->getMessage());
            }
        }

        // Import CPT types (create or update)
        $typesMap = [];

        foreach ($data['cpt_types'] ?? [] as $typeData) {
            try {
                $slug = $typeData['slug'] ?? '';

                if (empty($slug)) {
                    continue;
                }

                $existing = $this->cptTypeRepository->findBySlug($slug);

                if ($existing !== null) {
                    // Update existing type
                    $this->updateCptType($existing->getId(), $typeData, $taxonomiesMap, $result);
                    $typesMap[$slug] = $existing->getId();
                } else {
                    // Create new type
                    $typeId = $this->importCptType($typeData, $taxonomiesMap, $result);
                    $typesMap[$slug] = $typeId;
                }
            } catch (Exception $e) {
                $result->addError('cpt_' . ($typeData['slug'] ?? 'unknown'), $e->getMessage());
            }
        }

        // Import CPT posts (create or update)
        foreach ($data['cpt_posts'] ?? [] as $postData) {
            try {
                $typeSlug = $postData['type_slug'] ?? '';
                $postSlug = $postData['slug'] ?? '';

                if (empty($typeSlug) || empty($postSlug) || !isset($typesMap[$typeSlug])) {
                    continue;
                }

                $typeId = $typesMap[$typeSlug];
                $existing = $this->cptPostRepository->findBySlug($postSlug, $typeId);

                if ($existing !== null) {
                    // Update existing post
                    $this->updateCptPost($existing->getId(), $postData, $typesMap, $result);
                } else {
                    // Create new post
                    $this->importCptPost($postData, $typesMap, $result);
                }
            } catch (Exception $e) {
                $result->addError('post_' . ($postData['slug'] ?? 'unknown'), $e->getMessage());
            }
        }

        $createdCount = \count($result->getCreated());
        $updatedCount = \count($result->getUpdated());

        $result->setMessage(\sprintf(
            '%d groups created, %d groups updated',
            $createdCount,
            $updatedCount
        ));

        return $result;
    }

    /**
     * Validate import data structure.
     *
     * @param array<string, mixed> $data Import data
     *
     * @return array{valid: bool, error?: string, errors: array<string>} Validation result
     */
    public function validateImportData(array $data): array
    {
        $errors = [];

        // Check version
        if (! isset($data['version'])) {
            $errors[] = 'Missing required field: version';
        }

        // Check that we have either groups OR cpt_types
        $hasGroups = !empty($data['groups']) || !empty($data['group']);
        $hasCptTypes = !empty($data['cpt_types']);
        $hasTaxonomies = !empty($data['cpt_taxonomies']);

        if (!$hasGroups && !$hasCptTypes && !$hasTaxonomies) {
            $errors[] = 'Import data must contain either ACF groups or CPT types/taxonomies';
        }

        $groupsToValidate = $data['groups'] ?? [];

        if (empty($groupsToValidate) && isset($data['group'])) {
            $groupsToValidate = [$data['group']];
        }

        // Validate each group
        foreach ($groupsToValidate as $index => $group) {
            if (! \is_array($group)) {
                $errors[] = \sprintf('Group at index %d is not an array', $index);

                continue;
            }

            // Required fields
            if (empty($group['title'])) {
                $errors[] = \sprintf('Group at index %d: missing required field "title"', $index);
            }

            if (empty($group['slug'])) {
                $errors[] = \sprintf('Group at index %d: missing required field "slug"', $index);
            }

            if (! isset($group['fields']) || ! \is_array($group['fields'])) {
                $errors[] = \sprintf('Group at index %d: missing required field "fields" (array)', $index);

                continue;
            }

            // Validate fields
            foreach ($group['fields'] as $fieldIndex => $field) {
                if (! \is_array($field)) {
                    $errors[] = \sprintf('Group "%s", field at index %d: not an array', $group['slug'] ?? 'unknown', $fieldIndex);

                    continue;
                }

                if (empty($field['type'])) {
                    $errors[] = \sprintf('Group "%s", field at index %d: missing required field "type"', $group['slug'] ?? 'unknown', $fieldIndex);
                }

                if (empty($field['title'])) {
                    $errors[] = \sprintf('Group "%s", field at index %d: missing required field "title"', $group['slug'] ?? 'unknown', $fieldIndex);
                }

                if (empty($field['slug'])) {
                    $errors[] = \sprintf('Group "%s", field at index %d: missing required field "slug"', $group['slug'] ?? 'unknown', $fieldIndex);
                }
            }
        }

        // Validate CPT types
        if (isset($data['cpt_types']) && \is_array($data['cpt_types'])) {
            foreach ($data['cpt_types'] as $index => $type) {
                if (! \is_array($type)) {
                    $errors[] = \sprintf('CPT Type at index %d is not an array', $index);

                    continue;
                }

                if (empty($type['slug'])) {
                    $errors[] = \sprintf('CPT Type at index %d: missing required field "slug"', $index);
                }

                if (empty($type['url_prefix'])) {
                    $errors[] = \sprintf('CPT Type at index %d: missing required field "url_prefix"', $index);
                }

                if (empty($type['name'])) {
                    $errors[] = \sprintf('CPT Type "%s": missing required field "name"', $type['slug'] ?? 'unknown');
                }
            }
        }

        // Validate CPT taxonomies
        if (isset($data['cpt_taxonomies']) && \is_array($data['cpt_taxonomies'])) {
            foreach ($data['cpt_taxonomies'] as $index => $taxonomy) {
                if (! \is_array($taxonomy)) {
                    $errors[] = \sprintf('CPT Taxonomy at index %d is not an array', $index);

                    continue;
                }

                if (empty($taxonomy['slug'])) {
                    $errors[] = \sprintf('CPT Taxonomy at index %d: missing required field "slug"', $index);
                }

                if (empty($taxonomy['name'])) {
                    $errors[] = \sprintf('CPT Taxonomy "%s": missing required field "name"', $taxonomy['slug'] ?? 'unknown');
                }
            }
        }

        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? '' : 'Validation failed',
            'errors' => $errors,
        ];
    }

    /**
     * Get field count for a group.
     *
     * @param int $groupId Group ID
     *
     * @return int Field count
     */
    public function getFieldCount(int $groupId): int
    {
        return $this->fieldRepository->countByGroup($groupId);
    }

    /**
     * Format a group and its fields for export.
     *
     * @param array<string, mixed> $group Group data from repository
     * @param array<array<string, mixed>> $fields Fields data from repository
     * @param array<array<string, mixed>> $fieldValues Field values data from repository
     *
     * @return array<string, mixed> Formatted group data
     */
    private function formatGroupForExport(array $group, array $fields, array $fieldValues = []): array
    {
        $groupId = (int) $group['id_wepresta_acf_group'];

        // Get shop associations for this group
        $shopIds = $this->groupRepository->getShopIds($groupId);

        $exportedFields = [];

        foreach ($fields as $field) {
            $exportedFields[] = [
                'id' => (int) $field['id_wepresta_acf_field'], // Old ID for mapping during import
                'uuid' => $field['uuid'] ?? null,
                'id_parent' => isset($field['id_parent']) ? (int) $field['id_parent'] : null,
                'type' => $field['type'],
                'title' => $field['title'],
                'slug' => $field['slug'],
                'instructions' => $field['instructions'] ?? null,
                'position' => (int) ($field['position'] ?? 0),
                'required' => $this->isFieldRequired($field),
                'translatable' => (bool) ($field['translatable'] ?? $field['value_translatable'] ?? false),
                'active' => (bool) ($field['active'] ?? true),
                'config' => $this->decodeJson($field['config'] ?? '{}'),
                'validation' => $this->decodeJson($field['validation'] ?? '{}'),
                'conditions' => $this->decodeJson($field['conditions'] ?? '{}'),
                'wrapper' => $this->decodeJson($field['wrapper'] ?? '{}'),
                'fo_options' => $this->decodeJson($field['fo_options'] ?? '{}'),
            ];
        }

        // Sort fields: parents first (id_parent = null), then children by position
        // This ensures proper hierarchy in export
        usort($exportedFields, function ($a, $b) {
            $aParent = $a['id_parent'] ?? 0;
            $bParent = $b['id_parent'] ?? 0;

            // Null parents come first
            if ($aParent === 0 && $bParent !== 0) {
                return -1;
            }

            if ($aParent !== 0 && $bParent === 0) {
                return 1;
            }

            // Then sort by parent ID
            if ($aParent !== $bParent) {
                return $aParent <=> $bParent;
            }

            // Same parent, sort by position
            return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
        });

        // Format field values for export
        // Create a map of field IDs to slugs for quick lookup
        $fieldIdToSlug = [];

        foreach ($fields as $field) {
            $fieldIdToSlug[(int) $field['id_wepresta_acf_field']] = $field['slug'];
        }

        $exportedValues = [];

        foreach ($fieldValues as $value) {
            $fieldId = (int) $value['id_wepresta_acf_field'];
            $fieldSlug = $fieldIdToSlug[$fieldId] ?? null;

            if ($fieldSlug === null) {
                continue; // Skip if field not found
            }

            $exportedValues[] = [
                'field_slug' => $fieldSlug,
                'entity_type' => $value['entity_type'] ?? null,
                'entity_id' => (int) ($value['entity_id'] ?? 0),
                'id_product' => isset($value['id_product']) ? (int) $value['id_product'] : null, // Legacy
                'id_shop' => (int) ($value['id_shop'] ?? 1),
                'id_lang' => isset($value['id_lang']) ? (int) $value['id_lang'] : null,
                'value' => $this->decodeValueForExport($value['value'] ?? null),
                'value_index' => $value['value_index'] ?? null,
            ];
        }

        return [
            'uuid' => $group['uuid'] ?? null,
            'title' => $group['title'],
            'slug' => $group['slug'],
            'description' => $group['description'] ?? null,
            'location_rules' => $this->decodeJson($group['location_rules'] ?? '{}'),
            'placement_tab' => $group['placement_tab'] ?? 'description',
            'placement_position' => $group['placement_position'] ?? null,
            'position' => (int) ($group['priority'] ?? 10), // Export as 'position' for compatibility
            'priority' => (int) ($group['priority'] ?? 10), // Keep for backward compatibility
            'active' => (bool) ($group['active'] ?? true),
            'bo_options' => $this->decodeJson($group['bo_options'] ?? '{}'),
            'fo_options' => $this->decodeJson($group['fo_options'] ?? '{}'),
            'shop_ids' => $shopIds, // Export shop associations
            'fields' => $exportedFields,
            'field_values' => $exportedValues,
        ];
    }

    /**
     * Encode array to JSON string for database storage.
     *
     * @param mixed $data Data to encode
     *
     * @return string JSON string
     */
    private function encodeJson(mixed $data): string
    {
        if (\is_string($data)) {
            // If already a JSON string, try to validate it
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Valid JSON string, re-encode it properly
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Not valid JSON, return as is (shouldn't happen)
            return $data;
        }

        if (\is_array($data) || \is_object($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // For null, empty string, etc., return empty JSON array
        return '[]';
    }

    /**
     * Decode value for export (handle JSON strings).
     */
    private function decodeValueForExport(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Try to decode as JSON
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Return as string if not JSON
        return $value;
    }

    /**
     * Import a single group (create).
     *
     * @param array<string, mixed> $groupData Group data from JSON
     * @param ImportResult $result Result object to update
     */
    private function createGroup(array $groupData, ImportResult $result): void
    {
        $groupToSave = [
            'uuid' => $groupData['uuid'] ?? null, // Will be generated if null
            'slug' => $groupData['slug'],
            'title' => $groupData['title'],
            'description' => $groupData['description'] ?? null,
            'locationRules' => $groupData['location_rules'] ?? [],
            'placementTab' => $groupData['placement_tab'] ?? 'description',
            'placementPosition' => $groupData['placement_position'] ?? null,
            'priority' => $groupData['position'] ?? $groupData['priority'] ?? 10, // Support both 'position' and 'priority'
            'active' => $groupData['active'] ?? true,
            'boOptions' => $groupData['bo_options'] ?? [],
            'foOptions' => $groupData['fo_options'] ?? [],
        ];

        $groupId = $this->groupRepository->create($groupToSave);

        // Import shop associations
        $this->importShopAssociations($groupId, $groupData['shop_ids'] ?? []);

        $fieldIdMap = $this->importFields($groupId, $groupData['fields'] ?? [], $result);
        $this->importFieldValues($groupId, $fieldIdMap, $groupData['field_values'] ?? [], $result);

        $result->addCreated($groupData['slug']);
        $this->logInfo('Group created via import', ['slug' => $groupData['slug'], 'id' => $groupId]);
    }

    /**
     * Update an existing group.
     *
     * @param int $groupId Existing group ID
     * @param array<string, mixed> $groupData Group data from JSON
     * @param ImportResult $result Result object to update
     */
    private function updateGroup(int $groupId, array $groupData, ImportResult $result): void
    {
        $groupToSave = [
            'title' => $groupData['title'],
            'description' => $groupData['description'] ?? null,
            'locationRules' => $groupData['location_rules'] ?? [],
            'placementTab' => $groupData['placement_tab'] ?? 'description',
            'placementPosition' => $groupData['placement_position'] ?? null,
            'priority' => $groupData['position'] ?? $groupData['priority'] ?? 10, // Support both 'position' and 'priority'
            'active' => $groupData['active'] ?? true,
            'boOptions' => $groupData['bo_options'] ?? [],
            'foOptions' => $groupData['fo_options'] ?? [],
        ];

        $this->groupRepository->update($groupId, $groupToSave);

        // Update shop associations (remove all, then add new ones)
        $this->groupRepository->removeAllShopAssociations($groupId);
        $this->importShopAssociations($groupId, $groupData['shop_ids'] ?? []);

        // Delete existing fields and import new ones
        $this->fieldRepository->deleteByGroup($groupId);
        $fieldIdMap = $this->importFields($groupId, $groupData['fields'] ?? [], $result);
        $this->importFieldValues($groupId, $fieldIdMap, $groupData['field_values'] ?? [], $result);

        $result->addUpdated($groupData['slug']);
        $this->logInfo('Group updated via import', ['slug' => $groupData['slug'], 'id' => $groupId]);
    }

    /**
     * Import fields for a group.
     *
     * @param int $groupId Group ID
     * @param array<array<string, mixed>> $fields Fields data from JSON
     * @param ImportResult $result Result object to update
     *
     * @return array<string, int> Map of field slug => field ID
     */
    private function importFields(int $groupId, array $fields, ImportResult $result): array
    {
        $fieldIdMap = [];
        $oldIdToNewIdMap = []; // Map old field IDs to new field IDs

        // Sort fields: parents first (id_parent = null), then children
        // This ensures parent fields are created before their children
        usort($fields, function ($a, $b) {
            $aParent = isset($a['id_parent']) ? (int) $a['id_parent'] : 0;
            $bParent = isset($b['id_parent']) ? (int) $b['id_parent'] : 0;

            // Null parents come first
            if ($aParent === 0 && $bParent !== 0) {
                return -1;
            }

            if ($aParent !== 0 && $bParent === 0) {
                return 1;
            }

            // Then sort by parent ID
            if ($aParent !== $bParent) {
                return $aParent <=> $bParent;
            }

            // Same parent, sort by position
            return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
        });

        foreach ($fields as $fieldData) {
            $oldFieldId = isset($fieldData['id']) ? (int) $fieldData['id'] : null;
            $oldParentId = isset($fieldData['id_parent']) ? (int) $fieldData['id_parent'] : null;

            // Map old parent ID to new parent ID
            $newParentId = null;

            if ($oldParentId !== null && isset($oldIdToNewIdMap[$oldParentId])) {
                $newParentId = $oldIdToNewIdMap[$oldParentId];
            }

            $fieldToSave = [
                'uuid' => $fieldData['uuid'] ?? null, // Will be generated if null
                'idAcfGroup' => $groupId,
                'idParent' => $newParentId, // Use mapped parent ID
                'slug' => $fieldData['slug'],
                'type' => $fieldData['type'],
                'title' => $fieldData['title'],
                'instructions' => $fieldData['instructions'] ?? null,
                'position' => $fieldData['position'] ?? 0,
                'valueTranslatable' => $fieldData['translatable'] ?? false,
                'active' => $fieldData['active'] ?? true,
                'config' => $fieldData['config'] ?? [],
                'validation' => $fieldData['validation'] ?? [],
                'conditions' => $fieldData['conditions'] ?? [],
                'wrapper' => $fieldData['wrapper'] ?? [],
                'foOptions' => $fieldData['fo_options'] ?? [],
            ];

            $newFieldId = $this->fieldRepository->create($fieldToSave);
            $fieldIdMap[$fieldData['slug']] = $newFieldId;

            // Store mapping of old ID to new ID for children reference
            if ($oldFieldId !== null) {
                $oldIdToNewIdMap[$oldFieldId] = $newFieldId;
            }
        }

        $result->addFieldsImported(\count($fields));

        return $fieldIdMap;
    }

    /**
     * Import field values for a group.
     *
     * @param int $groupId Group ID
     * @param array<string, int> $fieldIdMap Map of field slug => field ID
     * @param array<array<string, mixed>> $fieldValues Field values data from JSON
     * @param ImportResult $result Result object to update
     */
    private function importFieldValues(int $groupId, array $fieldIdMap, array $fieldValues, ImportResult $result): void
    {
        $importedCount = 0;

        foreach ($fieldValues as $valueData) {
            $fieldSlug = $valueData['field_slug'] ?? null;

            if ($fieldSlug === null || ! isset($fieldIdMap[$fieldSlug])) {
                continue; // Skip if field not found
            }

            $fieldId = $fieldIdMap[$fieldSlug];
            $entityType = $valueData['entity_type'] ?? 'product';
            $entityId = (int) ($valueData['entity_id'] ?? 0);

            // Encode value if it's an array/object
            $value = $valueData['value'] ?? null;

            if (\is_array($value) || \is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($value !== null) {
                $value = (string) $value;
            }

            $this->fieldValueRepository->saveEntity(
                $fieldId,
                $entityType,
                $entityId,
                $value,
                (int) ($valueData['id_shop'] ?? 1),
                isset($valueData['id_lang']) ? (int) $valueData['id_lang'] : null,
                null, // isTranslatable - will be determined by field
                $valueData['value_index'] ?? null
            );

            ++$importedCount;
        }

        $this->logInfo('Field values imported', ['group_id' => $groupId, 'count' => $importedCount]);
    }

    /**
     * Import a single group (used by importReplace).
     *
     * @param array<string, mixed> $groupData Group data from JSON
     * @param ImportResult $result Result object to update
     */
    private function importGroup(array $groupData, ImportResult $result): void
    {
        $this->createGroup($groupData, $result);
    }

    /**
     * Check if a field is required based on validation rules.
     *
     * @param array<string, mixed> $field Field data
     *
     * @return bool True if field is required
     */
    private function isFieldRequired(array $field): bool
    {
        $validation = $this->decodeJson($field['validation'] ?? '{}');

        return (bool) ($validation['required'] ?? false);
    }

    /**
     * Import shop associations for a group.
     *
     * @param int $groupId Group ID
     * @param array<int> $shopIds Array of shop IDs to associate
     */
    private function importShopAssociations(int $groupId, array $shopIds): void
    {
        if (empty($shopIds)) {
            // If no shop IDs provided, associate with all active shops (default behavior)
            $this->groupRepository->addAllShopAssociations($groupId);

            return;
        }

        foreach ($shopIds as $shopId) {
            $this->groupRepository->addShopAssociation($groupId, (int) $shopId);
        }

        $this->logInfo('Shop associations imported', ['group_id' => $groupId, 'shop_ids' => $shopIds]);
    }

    /**
     * Decode JSON string to array.
     * Handles both JSON strings and already-decoded arrays.
     *
     * @param string|array|null $json JSON string, array, or null
     *
     * @return array<string, mixed> Decoded array
     */
    private function decodeJson(string|array|null $json): array
    {
        // If already an array, return it
        if (\is_array($json)) {
            return $json;
        }

        // If null or empty string, return empty array
        if ($json === null || $json === '') {
            return [];
        }

        // Handle string representations of empty arrays/objects
        $trimmed = trim($json);

        if ($trimmed === '' || $trimmed === '{}' || $trimmed === '[]' || $trimmed === 'null') {
            return [];
        }

        // Handle quoted JSON strings like "[]" or "{}"
        if ($trimmed[0] === '"' && $trimmed[\strlen($trimmed) - 1] === '"') {
            $unquoted = substr($trimmed, 1, -1);

            if ($unquoted === '[]' || $unquoted === '{}' || $unquoted === '') {
                return [];
            }

            // Try to decode the unquoted string
            try {
                $data = json_decode($unquoted, true, 512, JSON_THROW_ON_ERROR);

                return \is_array($data) ? $data : [];
            } catch (JsonException $e) {
                // Fall through to try decoding the original
            }
        }

        // Try to decode as JSON
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return \is_array($data) ? $data : [];
        } catch (JsonException $e) {
            return [];
        }
    }

    /**
     * Format CPT Type for export.
     *
     * @param CptType $type CPT Type entity
     *
     * @return array<string, mixed> Formatted type data
     */
    private function formatCptTypeForExport(CptType $type): array
    {
        return [
            'uuid' => $type->getUuid(),
            'slug' => $type->getSlug(),
            'name' => $type->getName(),
            'description' => $type->getDescription(),
            'config' => $type->getConfig(),
            'url_prefix' => $type->getUrlPrefix(),
            'has_archive' => $type->hasArchive(),
            'archive_slug' => $type->getArchiveSlug(),
            'seo_config' => $type->getSeoConfig(),
            'icon' => $type->getIcon(),
            'position' => $type->getPosition(),
            'active' => $type->isActive(),
            'translations' => $type->getTranslations(),
            'acf_groups' => array_map(
                fn($g) => $g['slug'] ?? $g['id_wepresta_acf_group'] ?? null,
                $type->getAcfGroups()
            ),
            'taxonomies' => array_map(
                fn($t) => $t['slug'] ?? $t['id_wepresta_acf_cpt_taxonomy'] ?? null,
                $type->getTaxonomies()
            ),
        ];
    }

    /**
    /**
     * Format CPT Post for export.
     *
     * @param CptPost $post CPT Post entity
     * @param string $typeSlug Type slug for reference resolution
     *
     * @return array<string, mixed> Formatted post data
     */
    private function formatCptPostForExport(CptPost $post, string $typeSlug): array
    {
        // Get term slugs for this post
        // $post->getTerms() returns an array of term IDs
        $termSlugs = [];
        $termIds = $post->getTerms();

        if (!empty($termIds)) {
            foreach ($termIds as $termId) {
                // Resolve term ID to slug
                $term = $this->cptTermRepository->find((int) $termId);

                if ($term) {
                    $termSlugs[] = $term->getSlug();
                }
            }
        }

        // Get ACF field values for this post (all languages)
        $acfValues = $this->fieldValueRepository->findByEntityAllLanguages('cpt_post', $post->getId());

        return [
            'uuid' => $post->getUuid(),
            'type_slug' => $typeSlug,
            'slug' => $post->getSlug(),
            'title' => $post->getTitle(),
            'status' => $post->getStatus(),
            'seo_title' => $post->getSeoTitle(),
            'seo_description' => $post->getSeoDescription(),
            'seo_meta' => $post->getSeoMeta(),
            'translations' => $post->getTranslations(),
            'terms' => $termSlugs,
            'acf_values' => $acfValues,
        ];
    }

    /**
     * Format CPT Taxonomy for export.
     *
     * @param CptTaxonomy $taxonomy CPT Taxonomy entity
     *
     * @return array<string, mixed> Formatted taxonomy data
     */
    private function formatCptTaxonomyForExport(CptTaxonomy $taxonomy): array
    {
        $terms = $this->cptTermRepository->getTree($taxonomy->getId());

        return [
            'uuid' => $taxonomy->getUuid(),
            'slug' => $taxonomy->getSlug(),
            'name' => $taxonomy->getName(),
            'description' => $taxonomy->getDescription(),
            'hierarchical' => $taxonomy->isHierarchical(),
            'config' => $taxonomy->getConfig(),
            'active' => $taxonomy->isActive(),
            'translations' => $taxonomy->getTranslations(),
            'terms' => $this->formatCptTermsTree($terms),
        ];
    }

    /**
     * Format CPT Terms tree recursively.
     *
     * @param array<CptTerm> $terms Array of CptTerm entities
     *
     * @return array<array<string, mixed>> Formatted terms tree
     */
    private function formatCptTermsTree(array $terms): array
    {
        return array_map(function (CptTerm $term) {
            $data = [
                'slug' => $term->getSlug(),
                'name' => $term->getName(),
                'description' => $term->getDescription(),
                'position' => $term->getPosition(),
                'translations' => $term->getTranslations(),
            ];

            $children = $term->getChildren();

            if (!empty($children)) {
                $data['children'] = $this->formatCptTermsTree($children);
            }

            return $data;
        }, $terms);
    }

    /**
     * Import CPT Type.
     *
     * @param array<string, mixed> $data Type data from JSON
     * @param array<string, int> $taxonomiesMap Map of taxonomy slug => taxonomy ID
     * @param ImportResult $result Result object to update
     *
     * @return int Created type ID
     */
    private function importCptType(array $data, array $taxonomiesMap, ImportResult $result): int
    {
        // Create new CPT Type
        $type = new CptType([
            'uuid' => $data['uuid'] ?? null,
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'config' => $data['config'] ?? [],
            'url_prefix' => $data['url_prefix'],
            'has_archive' => $data['has_archive'] ?? true,
            'archive_slug' => $data['archive_slug'] ?? null,
            'seo_config' => $data['seo_config'] ?? [],
            'icon' => $data['icon'] ?? 'article',
            'position' => $data['position'] ?? 0,
            'active' => $data['active'] ?? true,
        ]);

        // Set translations
        if (!empty($data['translations'])) {
            $type->setTranslations($data['translations']);
        }

        // Save type
        $typeId = $this->cptTypeRepository->save($type);

        // Sync ACF groups (resolve slugs to IDs)
        if (!empty($data['acf_groups'])) {
            $groupIds = [];

            foreach ($data['acf_groups'] as $groupSlug) {
                if ($groupSlug === null) {
                    continue;
                }
                $group = $this->groupRepository->findBySlug($groupSlug);

                if ($group) {
                    $groupIds[] = (int) $group['id_wepresta_acf_group'];
                } else {
                    $this->logInfo('ACF group not found for CPT type', ['slug' => $groupSlug, 'type' => $data['slug']]);
                }
            }

            if (!empty($groupIds)) {
                $this->cptTypeRepository->syncGroups($typeId, $groupIds);
            }
        }

        // Sync taxonomies (resolve slugs to IDs)
        if (!empty($data['taxonomies'])) {
            $taxonomyIds = [];

            foreach ($data['taxonomies'] as $taxSlug) {
                if ($taxSlug === null) {
                    continue;
                }

                if (isset($taxonomiesMap[$taxSlug])) {
                    $taxonomyIds[] = $taxonomiesMap[$taxSlug];
                } else {
                    $this->logInfo('Taxonomy not found for CPT type', ['slug' => $taxSlug, 'type' => $data['slug']]);
                }
            }

            if (!empty($taxonomyIds)) {
                $this->cptTypeRepository->syncTaxonomies($typeId, $taxonomyIds);
            }
        }

        $result->addCreated('cpt_' . $data['slug']);
        $this->logInfo('CPT Type imported', ['slug' => $data['slug'], 'id' => $typeId]);

        return $typeId;
    }

    /**
     * Import CPT Taxonomy.
     *
     * @param array<string, mixed> $data Taxonomy data from JSON
     * @param ImportResult $result Result object to update
     *
     * @return int Created taxonomy ID
     */
    private function importCptTaxonomy(array $data, ImportResult $result): int
    {
        $taxonomy = new CptTaxonomy([
            'uuid' => $data['uuid'] ?? null,
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'hierarchical' => $data['hierarchical'] ?? true,
            'config' => $data['config'] ?? [],
            'active' => $data['active'] ?? true,
        ]);

        if (!empty($data['translations'])) {
            $taxonomy->setTranslations($data['translations']);
        }

        $taxonomyId = $this->cptTaxonomyRepository->save($taxonomy);

        // Import terms tree
        if (!empty($data['terms'])) {
            $this->importCptTermsTree($data['terms'], $taxonomyId);
        }

        $result->addCreated('taxonomy_' . $data['slug']);
        $this->logInfo('CPT Taxonomy imported', ['slug' => $data['slug'], 'id' => $taxonomyId]);

        return $taxonomyId;
    }

    /**
     * Import CPT Terms tree recursively.
     *
     * @param array<array<string, mixed>> $terms Array of term data
     * @param int $taxonomyId Taxonomy ID
     * @param int|null $parentId Parent term ID
     */
    private function importCptTermsTree(array $terms, int $taxonomyId, ?int $parentId = null): void
    {
        foreach ($terms as $termData) {
            $term = new CptTerm([
                'id_wepresta_acf_cpt_taxonomy' => $taxonomyId,
                'id_parent' => $parentId,
                'slug' => $termData['slug'],
                'name' => $termData['name'],
                'description' => $termData['description'] ?? null,
                'position' => $termData['position'] ?? 0,
            ]);

            if (!empty($termData['translations'])) {
                $term->setTranslations($termData['translations']);
            }

            $termId = $this->cptTermRepository->save($term);

            // Import children recursively
            if (!empty($termData['children'])) {
                $this->importCptTermsTree($termData['children'], $taxonomyId, $termId);
            }
        }
    }

    /**
     * Import CPT Post with ACF values.
     *
     * @param array<string, mixed> $data Post data from JSON
     * @param array<string, int> $typesMap Map of type slug => type ID
     * @param ImportResult $result Result object to update
     *
     * @return int Created post ID
     */
    private function importCptPost(array $data, array $typesMap, ImportResult $result): int
    {
        // Resolve type slug to ID
        $typeSlug = $data['type_slug'] ?? null;

        if (!$typeSlug || !isset($typesMap[$typeSlug])) {
            $this->logError('Type not found for post import', [
                'post_slug' => $data['slug'] ?? 'unknown',
                'type_slug' => $typeSlug,
                'available_types' => array_keys($typesMap),
            ]);

            throw new Exception('Type not found for post: ' . ($data['slug'] ?? 'unknown') . ' (type_slug: ' . $typeSlug . ')');
        }

        $typeId = $typesMap[$typeSlug];

        $this->logInfo('Importing CPT post', [
            'post_slug' => $data['slug'],
            'type_slug' => $typeSlug,
            'type_id' => $typeId,
        ]);

        // Create new CPT Post
        $post = new CptPost([
            'uuid' => $data['uuid'] ?? null,
            'id_wepresta_acf_cpt_type' => $typeId,
            'slug' => $data['slug'],
            'title' => $data['title'],
            'status' => $data['status'] ?? CptPost::STATUS_PUBLISHED,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'seo_meta' => $data['seo_meta'] ?? [],
        ]);

        // Set translations
        if (!empty($data['translations'])) {
            $post->setTranslations($data['translations']);
        }

        // Save post (with shopId to ensure proper association)
        $postId = $this->cptPostRepository->save($post, 1);

        // Associate terms (resolve slugs to IDs)
        if (!empty($data['terms'])) {
            $termIds = [];

            foreach ($data['terms'] as $termSlug) {
                // Find term by slug across all taxonomies
                $allTaxonomies = $this->cptTaxonomyRepository->findAll();

                foreach ($allTaxonomies as $taxonomy) {
                    $term = $this->cptTermRepository->findBySlug($termSlug, $taxonomy->getId());

                    if ($term) {
                        $termIds[] = $term->getId();

                        break;
                    }
                }
            }

            if (!empty($termIds)) {
                $this->cptPostRepository->syncTerms($postId, $termIds);
            }
        }

        // Import ACF values for this post
        if (!empty($data['acf_values'])) {
            // Build values in the format expected by saveEntity
            // Format: [slug => value] or [slug_langId => value] for translatable
            $valuesToSave = [];

            foreach ($data['acf_values'] as $fieldSlug => $value) {
                $field = $this->fieldRepository->findBySlug($fieldSlug);

                if (!$field) {
                    $this->logInfo('Field not found for post value', ['slug' => $fieldSlug, 'post' => $data['slug']]);

                    continue;
                }

                $isTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);

                if ($isTranslatable && \is_array($value)) {
                    // Translatable value - save for each language
                    foreach ($value as $langId => $langValue) {
                        $this->fieldValueRepository->saveEntity(
                            (int) $field['id_wepresta_acf_field'],
                            'cpt_post',
                            $postId,
                            $this->encodeValueForImport($langValue),
                            1, // shop_id (default shop)
                            (int) $langId,
                            true,
                            null
                        );
                    }
                } else {
                    // Non-translatable value
                    $this->fieldValueRepository->saveEntity(
                        (int) $field['id_wepresta_acf_field'],
                        'cpt_post',
                        $postId,
                        $this->encodeValueForImport($value),
                        1, // shop_id (default shop)
                        null,
                        false,
                        null
                    );
                }
            }
        }

        $result->addCreated('post_' . $data['slug']);
        $this->logInfo('CPT Post imported', ['slug' => $data['slug'], 'id' => $postId]);

        return $postId;
    }

    /**
     * Encode value for import (convert array/object to JSON string).
     *
     * @param mixed $value Value to encode
     *
     * @return string|null Encoded value
     */
    private function encodeValueForImport(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Encode array/object to JSON
        if (\is_array($value) || \is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    /**
     * Update existing CPT Type.
     *
     * @param int $typeId Existing type ID
     * @param array<string, mixed> $data Type data from JSON
     * @param array<string, int> $taxonomiesMap Map of taxonomy slug => taxonomy ID
     * @param ImportResult $result Result object to update
     */
    private function updateCptType(int $typeId, array $data, array $taxonomiesMap, ImportResult $result): void
    {
        $type = $this->cptTypeRepository->find($typeId);

        if (!$type) {
            return;
        }

        // Update type properties
        $type->setName($data['name']);
        $type->setDescription($data['description'] ?? null);
        $type->setConfig($data['config'] ?? []);
        $type->setUrlPrefix($data['url_prefix']);
        $type->setHasArchive($data['has_archive'] ?? true);
        $type->setArchiveSlug($data['archive_slug'] ?? null);
        $type->setSeoConfig($data['seo_config'] ?? []);
        $type->setIcon($data['icon'] ?? 'article');
        $type->setPosition($data['position'] ?? 0);
        $type->setActive($data['active'] ?? true);

        if (!empty($data['translations'])) {
            $type->setTranslations($data['translations']);
        }

        $this->cptTypeRepository->save($type);

        // Update ACF groups associations
        if (isset($data['acf_groups'])) {
            $groupIds = [];

            foreach ($data['acf_groups'] as $groupSlug) {
                if ($groupSlug === null) {
                    continue;
                }
                $group = $this->groupRepository->findBySlug($groupSlug);

                if ($group) {
                    $groupIds[] = (int) $group['id_wepresta_acf_group'];
                }
            }

            $this->cptTypeRepository->syncGroups($typeId, $groupIds);
        }

        // Update taxonomies associations
        if (isset($data['taxonomies'])) {
            $taxonomyIds = [];

            foreach ($data['taxonomies'] as $taxSlug) {
                if ($taxSlug === null) {
                    continue;
                }

                if (isset($taxonomiesMap[$taxSlug])) {
                    $taxonomyIds[] = $taxonomiesMap[$taxSlug];
                }
            }

            $this->cptTypeRepository->syncTaxonomies($typeId, $taxonomyIds);
        }

        $result->addUpdated('cpt_' . $data['slug']);
        $this->logInfo('CPT Type updated via import', ['slug' => $data['slug'], 'id' => $typeId]);
    }

    /**
     * Update existing CPT Taxonomy.
     *
     * @param int $taxonomyId Existing taxonomy ID
     * @param array<string, mixed> $data Taxonomy data from JSON
     * @param ImportResult $result Result object to update
     */
    private function updateCptTaxonomy(int $taxonomyId, array $data, ImportResult $result): void
    {
        $taxonomy = $this->cptTaxonomyRepository->find($taxonomyId);

        if (!$taxonomy) {
            return;
        }

        // Update taxonomy properties
        $taxonomy->setName($data['name']);
        $taxonomy->setDescription($data['description'] ?? null);
        $taxonomy->setHierarchical($data['hierarchical'] ?? true);
        $taxonomy->setConfig($data['config'] ?? []);
        $taxonomy->setActive($data['active'] ?? true);

        if (!empty($data['translations'])) {
            $taxonomy->setTranslations($data['translations']);
        }

        $this->cptTaxonomyRepository->save($taxonomy);

        // Delete and re-import terms (simpler than complex update logic)
        // This is acceptable as terms are configuration, not user content
        $allTerms = $this->cptTermRepository->findByTaxonomy($taxonomyId);

        foreach ($allTerms as $term) {
            $this->cptTermRepository->delete($term->getId());
        }

        if (!empty($data['terms'])) {
            $this->importCptTermsTree($data['terms'], $taxonomyId);
        }

        $result->addUpdated('taxonomy_' . $data['slug']);
        $this->logInfo('CPT Taxonomy updated via import', ['slug' => $data['slug'], 'id' => $taxonomyId]);
    }

    /**
     * Update existing CPT Post.
     *
     * @param int $postId Existing post ID
     * @param array<string, mixed> $data Post data from JSON
     * @param array<string, int> $typesMap Map of type slug => type ID
     * @param ImportResult $result Result object to update
     */
    private function updateCptPost(int $postId, array $data, array $typesMap, ImportResult $result): void
    {
        $post = $this->cptPostRepository->find($postId);

        if (!$post) {
            return;
        }

        // Update post properties
        $post->setTitle($data['title']);
        $post->setStatus($data['status'] ?? CptPost::STATUS_PUBLISHED);
        $post->setSeoTitle($data['seo_title'] ?? null);
        $post->setSeoDescription($data['seo_description'] ?? null);
        $post->setSeoMeta($data['seo_meta'] ?? []);

        if (!empty($data['translations'])) {
            $post->setTranslations($data['translations']);
        }

        $this->cptPostRepository->save($post, 1);

        // Update terms associations
        if (isset($data['terms'])) {
            $termIds = [];

            foreach ($data['terms'] as $termSlug) {
                $allTaxonomies = $this->cptTaxonomyRepository->findAll();

                foreach ($allTaxonomies as $taxonomy) {
                    $term = $this->cptTermRepository->findBySlug($termSlug, $taxonomy->getId());

                    if ($term) {
                        $termIds[] = $term->getId();

                        break;
                    }
                }
            }

            if (!empty($termIds)) {
                $this->cptPostRepository->syncTerms($postId, $termIds);
            }
        }

        // Delete existing ACF values and re-import
        $this->fieldValueRepository->deleteByEntity('cpt_post', $postId);

        if (!empty($data['acf_values'])) {
            foreach ($data['acf_values'] as $fieldSlug => $value) {
                $field = $this->fieldRepository->findBySlug($fieldSlug);

                if (!$field) {
                    continue;
                }

                $fieldId = (int) $field['id_wepresta_acf_field'];
                $isTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);

                if ($isTranslatable && \is_array($value)) {
                    foreach ($value as $langId => $langValue) {
                        $this->fieldValueRepository->saveEntity(
                            $fieldId,
                            'cpt_post',
                            $postId,
                            $this->encodeValueForImport($langValue),
                            1,
                            (int) $langId,
                            true,
                            null
                        );
                    }
                } else {
                    $this->fieldValueRepository->saveEntity(
                        $fieldId,
                        'cpt_post',
                        $postId,
                        $this->encodeValueForImport($value),
                        1,
                        null,
                        false,
                        null
                    );
                }
            }
        }

        $result->addUpdated('post_' . $data['slug']);
        $this->logInfo('CPT Post updated via import', ['slug' => $data['slug'], 'id' => $postId]);
    }
}
