<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Exception;
use JsonException;
use Module;
use WeprestaAcf\Application\Template\ImportResult;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
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
        private readonly AcfFieldValueRepositoryInterface $fieldValueRepository
    ) {
    }

    /**
     * Export all groups to JSON format.
     *
     * @return array<string, mixed> Export data structure
     */
    public function exportAll(): array
    {
        $groups = $this->groupRepository->findAll();
        $exportedGroups = [];

        foreach ($groups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findAllByGroup($groupId);
            $fieldValues = $this->fieldValueRepository->findAllByGroup($groupId);
            $exportedGroups[] = $this->formatGroupForExport($group, $fields, $fieldValues);
        }

        $module = Module::getInstanceByName('wepresta_acf');
        $moduleVersion = $module ? $module::VERSION : '1.0.0';

        return [
            'version' => self::JSON_VERSION,
            'exported_at' => date('c'),
            'module_version' => $moduleVersion,
            'prestashop_version' => _PS_VERSION_,
            'groups' => $exportedGroups,
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

        // Delete all existing groups
        $allGroups = $this->groupRepository->findAll();
        $deletedCount = 0;

        foreach ($allGroups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $this->fieldRepository->deleteByGroup($groupId);

            if ($this->groupRepository->delete($groupId)) {
                ++$deletedCount;
            }
        }

        $this->logInfo('Deleted all groups for replace import', ['count' => $deletedCount]);

        // Import all groups
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

        // Check if at least one group was successfully imported
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

        if (empty($groupsToImport)) {
            return new ImportResult(false, 'No groups to import');
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

        // Check groups or group
        if (! isset($data['groups']) && ! isset($data['group'])) {
            $errors[] = 'Missing required field: groups or group';
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

        // Sort fields by position
        usort($exportedFields, fn ($a, $b) => $a['position'] <=> $b['position']);

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

        foreach ($fields as $fieldData) {
            $fieldToSave = [
                'uuid' => $fieldData['uuid'] ?? null, // Will be generated if null
                'idAcfGroup' => $groupId,
                'idParent' => isset($fieldData['id_parent']) ? (int) $fieldData['id_parent'] : null,
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

            $fieldId = $this->fieldRepository->create($fieldToSave);
            $fieldIdMap[$fieldData['slug']] = $fieldId;
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
}
