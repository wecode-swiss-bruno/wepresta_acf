<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldApiController extends FrameworkBundleAdminController
{
    private const JSON_FIELDS = ['config', 'validation', 'conditions', 'wrapper'];
    private const JSON_FIELD_MAP = ['fo_options' => 'foOptions'];

    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly SlugGenerator $slugGenerator,
    ) {}

    public function create(int $groupId, Request $request): JsonResponse
    {
        try {
            if (!$this->groupRepository->findById($groupId)) {
                return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND);
            }

            $data = $this->getJsonPayload($request);

            if (empty($data['type']) || empty($data['title'])) {
                return $this->jsonError('Type and title are required', Response::HTTP_BAD_REQUEST);
            }

            $slug = $this->resolveSlug($data['slug'] ?? '', $data['title'], $groupId);
            if ($slug === null) {
                return $this->jsonError('Slug already exists in group', Response::HTTP_BAD_REQUEST);
            }

            $fieldId = $this->fieldRepository->create([
                'uuid' => $this->generateUuid(),
                'idAcfGroup' => $groupId,
                'idParent' => $data['parentId'] ?? null,
                'type' => $data['type'],
                'title' => $data['title'],
                'slug' => $slug,
                'instructions' => $data['instructions'] ?? null,
                'config' => $data['config'] ?? [],
                'validation' => $data['validation'] ?? [],
                'conditions' => $data['conditions'] ?? [],
                'wrapper' => $data['wrapper'] ?? [],
                'foOptions' => $data['foOptions'] ?? [],
                'position' => $data['position'] ?? $this->fieldRepository->getNextPosition($groupId),
                'translatable' => $data['translatable'] ?? false,
                'active' => $data['active'] ?? true,
            ]);

            $field = $this->fieldRepository->findById($fieldId);

            return $this->json(
                ['success' => true, 'data' => $this->serializeField($field)],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $field = $this->fieldRepository->findById($id);
            if (!$field) {
                return $this->jsonError('Field not found', Response::HTTP_NOT_FOUND);
            }

            $data = $this->getJsonPayload($request);
            $groupId = (int) $field['id_wepresta_acf_group'];

            // Resolve slug
            $newSlug = $this->resolveSlug(
                $data['slug'] ?? $field['slug'],
                $data['title'] ?? $field['title'],
                $groupId,
                $id,
                $field['slug']
            );
            if ($newSlug === null) {
                return $this->jsonError('Slug already exists in group', Response::HTTP_BAD_REQUEST);
            }

            // Parse and merge JSON fields
            $jsonData = $this->mergeJsonFields($field, $data);

            // Handle translatable change
            $this->handleTranslatableChange($field, $data);

            $this->fieldRepository->update($id, [
                'title' => $data['title'] ?? $field['title'],
                'slug' => $newSlug,
                'instructions' => $data['instructions'] ?? $field['instructions'],
                'config' => $jsonData['config'],
                'validation' => $jsonData['validation'],
                'conditions' => $jsonData['conditions'],
                'wrapper' => $jsonData['wrapper'],
                'foOptions' => $jsonData['foOptions'],
                'position' => $data['position'] ?? $field['position'],
                'translatable' => (bool) ($data['translatable'] ?? $field['translatable']),
                'active' => $data['active'] ?? $field['active'],
            ]);

            return $this->json([
                'success' => true,
                'data' => $this->serializeField($this->fieldRepository->findById($id))
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->fieldRepository->findById($id)) {
                return $this->jsonError('Field not found', Response::HTTP_NOT_FOUND);
            }

            $this->fieldRepository->delete($id);

            return $this->json(['success' => true, 'message' => 'Field deleted successfully']);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    public function reorder(int $groupId, Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);

            if (empty($data['order']) || !is_array($data['order'])) {
                return $this->jsonError('Order array is required', Response::HTTP_BAD_REQUEST);
            }

            foreach ($data['order'] as $position => $fieldId) {
                $this->fieldRepository->update((int) $fieldId, ['position' => $position]);
            }

            return $this->json(['success' => true, 'message' => 'Fields reordered successfully']);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Resolve and validate slug for create/update operations.
     * Returns null if slug already exists (error case).
     */
    private function resolveSlug(
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
                fn($s, $id) => $this->fieldRepository->slugExistsInGroup($s, $groupId, $id),
                $excludeId
            );
        }

        // Normalize provided slug
        $slug = $this->slugGenerator->generate($slug);

        if (empty($slug)) {
            return $this->slugGenerator->generateUnique(
                $title,
                fn($s, $id) => $this->fieldRepository->slugExistsInGroup($s, $groupId, $id),
                $excludeId
            );
        }

        // Check uniqueness (skip if unchanged)
        if ($slug !== $currentSlug && $this->fieldRepository->slugExistsInGroup($slug, $groupId, $excludeId)) {
            return null;
        }

        return $slug;
    }

    /**
     * Merge existing JSON fields with new data.
     * @return array<string, array<string, mixed>>
     */
    private function mergeJsonFields(array $field, array $data): array
    {
        $result = [];

        foreach (self::JSON_FIELDS as $jsonField) {
            $dbKey = $jsonField;
            $dataKey = $jsonField;
            $existing = $this->decodeJson($field[$dbKey] ?? '[]');
            $result[$jsonField] = $this->ensureAssociativeArray($data[$dataKey] ?? null, $existing);
        }

        // Handle fo_options separately (different key in DB vs data)
        $existing = $this->decodeJson($field['fo_options'] ?? '[]');
        $result['foOptions'] = $this->ensureAssociativeArray($data['foOptions'] ?? null, $existing);

        return $result;
    }

    /**
     * Decode JSON string to array.
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        return json_decode($json, true) ?: [];
    }

    /**
     * Ensure value is an associative array, fallback to existing if not.
     * @return array<string, mixed>
     */
    private function ensureAssociativeArray(mixed $value, array $fallback): array
    {
        if (!is_array($value)) {
            return $fallback;
        }

        // Check if sequential array (not associative)
        if ($value !== [] && array_keys($value) === range(0, count($value) - 1)) {
            return $fallback;
        }

        return $value;
    }

    /**
     * Handle translatable flag change - cleanup values if switching to non-translatable.
     */
    private function handleTranslatableChange(array $field, array $data): void
    {
        $wasTranslatable = (bool) ($field['translatable'] ?? false);
        $newTranslatable = (bool) ($data['translatable'] ?? $field['translatable']);

        if ($wasTranslatable && !$newTranslatable) {
            $this->valueRepository->deleteTranslatableValuesByField((int) $field['id_wepresta_acf_field']);
        }
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function serializeField(array $field): array
    {
        return [
            'id' => (int) $field['id_wepresta_acf_field'],
            'uuid' => $field['uuid'],
            'groupId' => (int) $field['id_wepresta_acf_group'],
            'parentId' => isset($field['id_parent']) && $field['id_parent'] ? (int) $field['id_parent'] : null,
            'type' => $field['type'],
            'title' => $field['title'],
            'slug' => $field['slug'],
            'instructions' => $field['instructions'] ?: null,
            'config' => $this->decodeJson($field['config'] ?? '[]'),
            'validation' => $this->decodeJson($field['validation'] ?? '[]'),
            'conditions' => $this->decodeJson($field['conditions'] ?? '[]'),
            'wrapper' => $this->decodeJson($field['wrapper'] ?? '[]'),
            'foOptions' => $this->decodeJson($field['fo_options'] ?? '[]'),
            'position' => (int) $field['position'],
            'translatable' => (bool) $field['translatable'],
            'active' => (bool) $field['active'],
        ];
    }

    /** @return array<string, mixed> */
    private function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload');
        }

        return $data;
    }

    private function jsonError(string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return $this->json(['success' => false, 'error' => $message], $status);
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
