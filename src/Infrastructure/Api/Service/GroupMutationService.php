<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Service;

use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Infrastructure\Api\Request\CreateGroupRequest;
use WeprestaAcf\Infrastructure\Api\Request\UpdateGroupRequest;
use WeprestaAcf\Infrastructure\Api\Validator\SlugValidator;

/**
 * Business logic for group mutations (create, update, delete, duplicate).
 */
final class GroupMutationService
{
    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly SlugValidator $slugValidator,
        private readonly SlugGenerator $slugGenerator,
        private readonly AutoSyncService $autoSyncService
    ) {
    }

    /**
     * Create a new group.
     *
     * @return array{success: bool, groupId?: int, error?: string}
     */
    public function create(CreateGroupRequest $request, string $uuid): array
    {
        // Resolve slug
        $slug = $this->slugValidator->resolveGroupSlug($request->slug, $request->title);

        if ($slug === null) {
            return ['success' => false, 'error' => 'Slug already exists'];
        }

        // Create group
        $groupId = $this->groupRepository->create([
            'uuid' => $uuid,
            'title' => $request->title,
            'slug' => $slug,
            'description' => $request->description,
            'locationRules' => $request->locationRules,
            'placementTab' => $request->placementTab,
            'placementPosition' => $request->placementPosition,
            'priority' => $request->priority,
            'boOptions' => $request->boOptions,
            'foOptions' => $request->foOptions,
            'active' => $request->active,
        ]);

        // Associate with all active shops
        $this->groupRepository->addAllShopAssociations($groupId);

        // Save translations if provided
        if ($request->translations !== null) {
            $this->groupRepository->saveGroupTranslations($groupId, $request->translations);
        }

        // Mark for auto-sync
        $this->autoSyncService->markDirty();

        return ['success' => true, 'groupId' => $groupId];
    }

    /**
     * Update an existing group.
     *
     * @param array<string, mixed> $group Current group data
     *
     * @return array{success: bool, error?: string}
     */
    public function update(int $groupId, array $group, UpdateGroupRequest $request): array
    {
        // Resolve slug if changed
        $newSlug = null;

        if ($request->slug !== null && $request->slug !== $group['slug']) {
            $newSlug = $this->slugValidator->resolveGroupSlug($request->slug, $group['title'], $groupId);

            if ($newSlug === null) {
                return ['success' => false, 'error' => 'Slug already exists'];
            }
        }

        // Prepare update data
        $updateData = array_filter([
            'title' => $request->title,
            'slug' => $newSlug,
            'description' => $request->description,
            'locationRules' => $request->locationRules,
            'placementTab' => $request->placementTab,
            'placementPosition' => $request->placementPosition,
            'priority' => $request->priority,
            'boOptions' => $request->boOptions,
            'foOptions' => $request->foOptions,
            'active' => $request->active,
        ], fn ($value) => $value !== null);

        // Update group
        $this->groupRepository->update($groupId, $updateData);

        // Save translations if provided
        if ($request->translations !== null) {
            $this->groupRepository->saveGroupTranslations($groupId, $request->translations);
        }

        // Mark for auto-sync
        $this->autoSyncService->markDirty();

        return ['success' => true];
    }

    /**
     * Delete a group.
     */
    public function delete(int $groupId): void
    {
        $this->groupRepository->delete($groupId);
        $this->autoSyncService->markDirty();
    }

    /**
     * Duplicate a group with all its fields.
     *
     * @param array<string, mixed> $group Source group
     *
     * @return array{success: bool, groupId?: int, error?: string}
     */
    public function duplicate(array $group, string $uuid): array
    {
        // Generate unique slug
        $newSlug = $this->slugGenerator->generateUnique(
            $group['slug'] . '-copy',
            fn ($s, $i) => $this->groupRepository->slugExists($s, $i)
        );

        // Create duplicated group
        $newGroupId = $this->groupRepository->create([
            'uuid' => $uuid,
            'title' => $group['title'] . ' (Copy)',
            'slug' => $newSlug,
            'description' => $group['description'],
            'locationRules' => json_decode($group['location_rules'] ?? '[]', true),
            'placementTab' => $group['placement_tab'],
            'placementPosition' => $group['placement_position'],
            'priority' => $group['priority'],
            'boOptions' => json_decode($group['bo_options'] ?? '{}', true),
            'foOptions' => json_decode($group['fo_options'] ?? '{}', true),
            'active' => false,
        ]);

        // Associate with all active shops
        $this->groupRepository->addAllShopAssociations($newGroupId);

        // Duplicate all fields
        $sourceGroupId = (int) $group['id_wepresta_acf_group'];
        $fields = $this->fieldRepository->findAllByGroup($sourceGroupId);

        foreach ($fields as $field) {
            $fieldSlug = $this->slugGenerator->generateUnique(
                $field['slug'],
                fn ($s, $i) => $this->fieldRepository->slugExistsInGroup($s, $newGroupId, $i)
            );

            $this->fieldRepository->create([
                'uuid' => $this->generateUuid(),
                'idAcfGroup' => $newGroupId,
                'type' => $field['type'],
                'title' => $field['title'],
                'slug' => $fieldSlug,
                'instructions' => $field['instructions'],
                'config' => json_decode($field['config'] ?? '[]', true),
                'validation' => json_decode($field['validation'] ?? '[]', true),
                'conditions' => json_decode($field['conditions'] ?? '[]', true),
                'wrapper' => json_decode($field['wrapper'] ?? '[]', true),
                'foOptions' => json_decode($field['fo_options'] ?? '{}', true),
                'position' => $field['position'],
                'value_translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false),
                'translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false),
                'active' => $field['active'],
            ]);
        }

        // Mark for auto-sync
        $this->autoSyncService->markDirty();

        return ['success' => true, 'groupId' => $newGroupId];
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
