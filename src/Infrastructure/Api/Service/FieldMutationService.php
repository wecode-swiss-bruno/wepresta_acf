<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Infrastructure\Api\Request\CreateFieldRequest;
use WeprestaAcf\Infrastructure\Api\Request\UpdateFieldRequest;
use WeprestaAcf\Infrastructure\Api\Validator\SlugValidator;

/**
 * Business logic for field mutations (create, update, delete).
 */
final class FieldMutationService
{
    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly SlugValidator $slugValidator,
        private readonly AutoSyncService $autoSyncService
    ) {
    }

    /**
     * Create a new field.
     *
     * @return array{success: bool, fieldId?: int, error?: string}
     */
    public function create(CreateFieldRequest $request, string $uuid): array
    {
        // Resolve slug
        $slug = $this->slugValidator->resolveFieldSlug(
            $request->slug,
            $request->title,
            $request->groupId
        );

        if ($slug === null) {
            return ['success' => false, 'error' => 'Slug already exists in group'];
        }

        // Get next position if not specified
        $position = $request->position > 0
            ? $request->position
            : $this->fieldRepository->getNextPosition($request->groupId);

        // Create field
        $fieldId = $this->fieldRepository->create([
            'uuid' => $uuid,
            'idAcfGroup' => $request->groupId,
            'idParent' => $request->parentId,
            'type' => $request->type,
            'title' => $request->title,
            'slug' => $slug,
            'instructions' => $request->instructions,
            'config' => $request->config,
            'validation' => $request->validation,
            'conditions' => $request->conditions,
            'wrapper' => $request->wrapper,
            'foOptions' => $request->foOptions,
            'position' => $position,
            'translatable' => $request->translatable,
            'active' => $request->active,
            'translations' => $request->translations,
        ]);

        // Save translations if provided
        if ($request->translations !== null) {
            $this->fieldRepository->saveFieldTranslations($fieldId, $request->translations);
        }

        // Mark for auto-sync
        $this->autoSyncService->markDirty();

        return ['success' => true, 'fieldId' => $fieldId];
    }

    /**
     * Update an existing field.
     *
     * @param array<string, mixed> $field Current field data
     *
     * @return array{success: bool, error?: string}
     */
    public function update(int $fieldId, array $field, UpdateFieldRequest $request): array
    {
        $groupId = (int) $field['id_wepresta_acf_group'];

        // Resolve slug if provided
        $newSlug = null;

        if ($request->slug !== null || $request->title !== null) {
            $newSlug = $this->slugValidator->resolveFieldSlug(
                $request->slug ?? $field['slug'],
                $request->title ?? $field['title'],
                $groupId,
                $fieldId,
                $field['slug']
            );

            if ($newSlug === null) {
                return ['success' => false, 'error' => 'Slug already exists in group'];
            }
        }

        // Handle translatable change (cleanup if switching to non-translatable)
        if ($request->valueTranslatable !== null) {
            $wasTranslatable = (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);

            if ($wasTranslatable && ! $request->valueTranslatable) {
                $this->valueRepository->deleteTranslatableValuesByField($fieldId);
            }
        }

        // Prepare update data
        $updateData = array_filter([
            'title' => $request->title,
            'slug' => $newSlug,
            'instructions' => $request->instructions,
            'config' => $request->config !== null ? $request->config : $this->mergeJson($field['config'] ?? '[]', null),
            'validation' => $request->validation !== null ? $request->validation : $this->mergeJson($field['validation'] ?? '[]', null),
            'conditions' => $request->conditions !== null ? $request->conditions : $this->mergeJson($field['conditions'] ?? '[]', null),
            'wrapper' => $request->wrapper !== null ? $request->wrapper : $this->mergeJson($field['wrapper'] ?? '[]', null),
            'foOptions' => $request->foOptions !== null ? $request->foOptions : $this->mergeJson($field['fo_options'] ?? '[]', null),
            'position' => $request->position,
            'valueTranslatable' => $request->valueTranslatable,
            'active' => $request->active,
        ], fn ($value) => $value !== null);

        // Update field
        $this->fieldRepository->update($fieldId, $updateData);

        // Save translations if provided
        if ($request->translations !== null) {
            $this->fieldRepository->saveFieldTranslations($fieldId, $request->translations);
        }

        // Mark for auto-sync
        $this->autoSyncService->markDirty();

        return ['success' => true];
    }

    /**
     * Delete a field.
     */
    public function delete(int $fieldId): void
    {
        $this->fieldRepository->delete($fieldId);
        $this->autoSyncService->markDirty();
    }

    /**
     * Reorder fields in a group.
     *
     * @param array<int, int> $order Map of position => fieldId
     */
    public function reorder(array $order): void
    {
        foreach ($order as $position => $fieldId) {
            $this->fieldRepository->update((int) $fieldId, ['position' => $position]);
        }

        $this->autoSyncService->markDirty();
    }

    /**
     * Merge existing JSON with new data (fallback to existing if new is null).
     *
     * @return array<string, mixed>
     */
    private function mergeJson(string $existing, ?array $new): array
    {
        $decoded = json_decode($existing, true) ?: [];

        return $new ?? $decoded;
    }
}
