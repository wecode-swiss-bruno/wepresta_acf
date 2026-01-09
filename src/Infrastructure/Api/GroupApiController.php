<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Application\Service\ValueProvider;
use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Context;

class GroupApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly SlugGenerator $slugGenerator,
        private readonly ValueProvider $valueProvider,
        private readonly ValueHandler $valueHandler,
        private readonly AutoSyncService $autoSyncService
    ) {}

    public function list(): JsonResponse
    {
        try {
            $groups = $this->groupRepository->findAll();
            return $this->json(['success' => true, 'data' => array_map(fn($g) => $this->serializeGroup($g), $groups)]);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);
            if (!$group) { return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND); }
            return $this->json(['success' => true, 'data' => $this->serializeGroup($group, true)]);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);
            if (empty($data['title'])) { return $this->jsonError('Title is required', Response::HTTP_BAD_REQUEST); }

            $slug = $data['slug'] ?? '';
            if (empty($slug)) {
                $slug = $this->slugGenerator->generateUnique($data['title'], fn($s, $id) => $this->groupRepository->slugExists($s, $id));
            } elseif ($this->groupRepository->slugExists($slug)) {
                return $this->jsonError('Slug already exists', Response::HTTP_BAD_REQUEST);
            }

            $groupId = $this->groupRepository->create([
                'uuid' => $this->generateUuid(), 'title' => $data['title'], 'slug' => $slug,
                'description' => $data['description'] ?? null, 'locationRules' => $data['locationRules'] ?? [],
                'placementTab' => $data['placementTab'] ?? 'modules', 'placementPosition' => $data['placementPosition'] ?? null,
                'priority' => $data['priority'] ?? 10, 'boOptions' => $data['boOptions'] ?? [],
                'foOptions' => $data['foOptions'] ?? [], 'active' => $data['active'] ?? true,
            ]);

            // Associate with all active shops
            $this->groupRepository->addAllShopAssociations($groupId);

            // Save group translations if provided
            if (isset($data['translations']) && is_array($data['translations'])) {
                $this->groupRepository->saveGroupTranslations($groupId, $data['translations']);
            }

            // Mark for auto-sync export
            $this->autoSyncService->markDirty();

            return $this->json(['success' => true, 'data' => $this->serializeGroup($this->groupRepository->findById($groupId))], Response::HTTP_CREATED);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);
            if (!$group) { return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND); }
            $data = $this->getJsonPayload($request);
            $newSlug = $data['slug'] ?? $group['slug'];
            if ($newSlug !== $group['slug'] && $this->groupRepository->slugExists($newSlug, $id)) {
                return $this->jsonError('Slug already exists', Response::HTTP_BAD_REQUEST);
            }
            $this->groupRepository->update($id, [
                'title' => $data['title'] ?? $group['title'], 'slug' => $newSlug,
                'description' => $data['description'] ?? $group['description'],
                'locationRules' => $data['locationRules'] ?? json_decode($group['location_rules'] ?? '[]', true),
                'placementTab' => $data['placementTab'] ?? $group['placement_tab'],
                'placementPosition' => $data['placementPosition'] ?? $group['placement_position'],
                'priority' => $data['priority'] ?? $group['priority'],
                'boOptions' => $data['boOptions'] ?? json_decode($group['bo_options'] ?? '{}', true),
                'foOptions' => $data['foOptions'] ?? json_decode($group['fo_options'] ?? '{}', true),
                'active' => $data['active'] ?? $group['active'],
            ]);

            // Save group translations if provided
            if (isset($data['translations']) && is_array($data['translations'])) {
                $this->groupRepository->saveGroupTranslations($id, $data['translations']);
            }

            // Mark for auto-sync export
            $this->autoSyncService->markDirty();

            return $this->json(['success' => true, 'data' => $this->serializeGroup($this->groupRepository->findById($id), true)]);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->groupRepository->findById($id)) { return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND); }
            $this->groupRepository->delete($id);
            
            // Mark for auto-sync export
            $this->autoSyncService->markDirty();
            
            return $this->json(['success' => true, 'message' => 'Group deleted successfully']);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function duplicate(int $id): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);
            if (!$group) { return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND); }

            $newSlug = $this->slugGenerator->generateUnique($group['slug'] . '-copy', fn($s, $i) => $this->groupRepository->slugExists($s, $i));
            $newGroupId = $this->groupRepository->create([
                'uuid' => $this->generateUuid(), 'title' => $group['title'] . ' (Copy)', 'slug' => $newSlug,
                'description' => $group['description'], 'locationRules' => json_decode($group['location_rules'] ?? '[]', true),
                'placementTab' => $group['placement_tab'], 'placementPosition' => $group['placement_position'],
                'priority' => $group['priority'], 'boOptions' => json_decode($group['bo_options'] ?? '{}', true),
                'foOptions' => json_decode($group['fo_options'] ?? '{}', true), 'active' => false,
            ]);

            // Associate with all active shops
            $this->groupRepository->addAllShopAssociations($newGroupId);

            foreach ($this->fieldRepository->findAllByGroup((int) $group['id_wepresta_acf_group']) as $field) {
                $this->fieldRepository->create([
                    'uuid' => $this->generateUuid(), 'idAcfGroup' => $newGroupId,
                    'type' => $field['type'], 'title' => $field['title'],
                    'slug' => $this->slugGenerator->generateUnique($field['slug'], fn($s, $i) => $this->fieldRepository->slugExistsInGroup($s, $newGroupId, $i)),
                    'instructions' => $field['instructions'], 'config' => json_decode($field['config'] ?? '[]', true),
                    'validation' => json_decode($field['validation'] ?? '[]', true), 'conditions' => json_decode($field['conditions'] ?? '[]', true),
                    'wrapper' => json_decode($field['wrapper'] ?? '[]', true), 'foOptions' => json_decode($field['fo_options'] ?? '{}', true),
                    'position' => $field['position'], 'value_translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false), 'translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false), 'active' => $field['active'],
                ]);
            }
            
            // Mark for auto-sync export
            $this->autoSyncService->markDirty();
            
            return $this->json(['success' => true, 'data' => $this->serializeGroup($this->groupRepository->findById($newGroupId), true)], Response::HTTP_CREATED);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    /** @param array<string, mixed> $group @return array<string, mixed> */
    private function serializeGroup(array $group, bool $includeFields = false): array
    {
        $groupId = (int) $group['id_wepresta_acf_group'];
        $data = [
            'id' => $groupId, 'uuid' => $group['uuid'], 'title' => $group['title'], 'slug' => $group['slug'],
            'description' => $group['description'] ?: null, 'locationRules' => json_decode($group['location_rules'] ?? '[]', true),
            'placementTab' => $group['placement_tab'], 'placementPosition' => $group['placement_position'] ?: null,
            'priority' => (int) $group['priority'], 'boOptions' => json_decode($group['bo_options'] ?? '{}', true),
            'foOptions' => json_decode($group['fo_options'] ?? '{}', true), 'active' => (bool) $group['active'],
            'dateAdd' => $group['date_add'], 'dateUpd' => $group['date_upd'],
        ];
        if ($includeFields) {
            $fields = $this->fieldRepository->findByGroup($groupId);
            $data['fields'] = array_map(fn($f) => $this->serializeField($f), $fields);
        } else { $data['fieldCount'] = $this->fieldRepository->countByGroup($groupId); }
        return $data;
    }

    /** @param array<string, mixed> $field @return array<string, mixed> */
    private function serializeField(array $field): array
    {
        $fieldId = (int) $field['id_wepresta_acf_field'];
        
        // Get translations for this field
        $translations = $this->fieldRepository->getFieldTranslations($fieldId);
        
        $result = [
            'id' => $fieldId, 'uuid' => $field['uuid'],
            'groupId' => (int) $field['id_wepresta_acf_group'],
            'parentId' => isset($field['id_parent']) && $field['id_parent'] ? (int) $field['id_parent'] : null,
            'type' => $field['type'], 'title' => $field['title'], 'slug' => $field['slug'],
            'instructions' => $field['instructions'] ?: null, 'config' => json_decode($field['config'] ?? '[]', true),
            'validation' => json_decode($field['validation'] ?? '[]', true), 'conditions' => json_decode($field['conditions'] ?? '[]', true),
            'wrapper' => json_decode($field['wrapper'] ?? '[]', true), 'foOptions' => json_decode($field['fo_options'] ?? '[]', true),
            'position' => (int) $field['position'], 'value_translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false), 'translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false), 'active' => (bool) $field['active'],
            'dateAdd' => $field['date_add'], 'dateUpd' => $field['date_upd'],
            'translations' => $translations,
        ];
        if ($field['type'] === 'repeater') {
            $result['children'] = array_map(fn($c) => $this->serializeField($c), $this->fieldRepository->findByParent($fieldId));
        }
        return $result;
    }

    /** @return array<string, mixed> */
    private function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if (empty($content)) { return []; }
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) { throw new \InvalidArgumentException('Invalid JSON payload'); }
        return $data;
    }

    private function jsonError(string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return $this->json(['success' => false, 'error' => $message], $status);
    }

    /**
     * Get global values for a group (entity_id = 0).
     * Returns ALL languages for translatable fields.
     * For global scope, values are shared across all entity_types, so we use the first entity_type.
     */
    public function getGlobalValues(int $id): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);
            if (!$group) {
                return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND);
            }

            // Extract first entity_type from location_rules (values are stored with first entity_type)
            $locationRules = json_decode($group['location_rules'] ?? '[]', true);
            if (empty($locationRules) || !isset($locationRules[0]['=='][1])) {
                return $this->jsonError('No entity type defined for this group', Response::HTTP_BAD_REQUEST);
            }

            // Use first entity_type (values are stored with this type for global scope)
            $primaryEntityType = $locationRules[0]['=='][1];
            $shopId = (int) Context::getContext()->shop->id;

            // Get values with entity_id = 0 (global) - including ALL languages for translatable fields
            // These values are shared across all entity_types in the group
            $values = $this->valueProvider->getEntityFieldValuesAllLanguages($primaryEntityType, 0, $shopId);

            // Ensure values is always an object in JSON, not an array
            // Use stdClass for empty to force {} instead of []
            $jsonValues = empty($values) ? new \stdClass() : $values;

            return $this->json([
                'success' => true,
                'data' => [
                    'entityType' => $primaryEntityType,
                    'values' => $jsonValues,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Save global values for a group (entity_id = 0).
     * For global scope, values are shared across all entity_types in the group.
     */
    public function saveGlobalValues(int $id, Request $request): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);
            if (!$group) {
                return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND);
            }

            // Check if group is configured for global scope
            $foOptions = json_decode($group['fo_options'] ?? '{}', true);
            if (($foOptions['valueScope'] ?? 'entity') !== 'global') {
                return $this->jsonError('This group is not configured for global values', Response::HTTP_BAD_REQUEST);
            }

            // Extract ALL entity types from location_rules
            $locationRules = json_decode($group['location_rules'] ?? '[]', true);
            if (empty($locationRules)) {
                return $this->jsonError('No entity type defined for this group', Response::HTTP_BAD_REQUEST);
            }

            $entityTypes = [];
            foreach ($locationRules as $rule) {
                if (isset($rule['==']) && isset($rule['=='][1])) {
                    $entityType = $rule['=='][1];
                    if (!in_array($entityType, $entityTypes, true)) {
                        $entityTypes[] = $entityType;
                    }
                }
            }

            if (empty($entityTypes)) {
                return $this->jsonError('No entity type defined for this group', Response::HTTP_BAD_REQUEST);
            }

            // Use first entity_type as primary (for storage)
            $primaryEntityType = $entityTypes[0];
            $shopId = (int) Context::getContext()->shop->id;

            // Get values from request
            $data = $this->getJsonPayload($request);
            $values = $data['values'] ?? [];

            // Get all fields in this group to clean up old global values
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findAllByGroup($groupId);

            // For each field being saved, delete all existing global values (entity_id=0) for ALL entity_types
            // This ensures we don't have duplicate values when entity_types change
            foreach ($fields as $field) {
                $fieldId = (int) $field['id_wepresta_acf_field'];
                $fieldSlug = $field['slug'];
                
                // Only process fields that are being saved
                if (!isset($values[$fieldSlug])) {
                    continue;
                }

                // Delete all global values (entity_id=0) for this field across all entity_types
                // This ensures a single shared value regardless of entity_type
                foreach ($entityTypes as $entityType) {
                    $this->valueRepository->deleteByFieldAndEntity($fieldId, $entityType, 0, $shopId);
                }
            }

            // Save with primary entity_type and entity_id = 0 (global)
            // This single value will be shared across all entity_types when reading
            $this->valueHandler->saveEntityFieldValues($primaryEntityType, 0, $values, $shopId);

            return $this->json([
                'success' => true,
                'message' => 'Global values saved successfully',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

