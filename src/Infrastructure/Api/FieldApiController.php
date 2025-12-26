<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfGroupRepositoryInterface $groupRepository,
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

            $slug = $data['slug'] ?? '';
            if (empty($slug)) {
                $slug = $this->slugGenerator->generateUnique($data['title'], fn($s, $id) => $this->fieldRepository->slugExistsInGroup($s, $groupId, $id));
            } elseif ($this->fieldRepository->slugExistsInGroup($slug, $groupId)) {
                return $this->jsonError('Slug already exists in group', Response::HTTP_BAD_REQUEST);
            }

            $fieldId = $this->fieldRepository->create([
                'uuid' => Uuid::uuid4()->toString(), 'idAcfGroup' => $groupId,
                'idParent' => $data['parentId'] ?? null, 'type' => $data['type'], 'title' => $data['title'], 'slug' => $slug,
                'instructions' => $data['instructions'] ?? null, 'config' => $data['config'] ?? [],
                'validation' => $data['validation'] ?? [], 'conditions' => $data['conditions'] ?? [],
                'wrapper' => $data['wrapper'] ?? [], 'foOptions' => $data['foOptions'] ?? [],
                'position' => $data['position'] ?? $this->fieldRepository->getNextPosition($groupId),
                'translatable' => $data['translatable'] ?? false, 'active' => $data['active'] ?? true,
            ]);

            return $this->json(['success' => true, 'data' => $this->serializeField($this->fieldRepository->findById($fieldId))], Response::HTTP_CREATED);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $field = $this->fieldRepository->findById($id);
            if (!$field) { return $this->jsonError('Field not found', Response::HTTP_NOT_FOUND); }
            $data = $this->getJsonPayload($request);
            $groupId = (int) $field['id_wepresta_acf_group'];

            $newSlug = $data['slug'] ?? $field['slug'];
            if ($newSlug !== $field['slug'] && $this->fieldRepository->slugExistsInGroup($newSlug, $groupId, $id)) {
                return $this->jsonError('Slug already exists in group', Response::HTTP_BAD_REQUEST);
            }

            $this->fieldRepository->update($id, [
                'title' => $data['title'] ?? $field['title'], 'slug' => $newSlug,
                'instructions' => $data['instructions'] ?? $field['instructions'],
                'config' => $data['config'] ?? json_decode($field['config'] ?? '[]', true),
                'validation' => $data['validation'] ?? json_decode($field['validation'] ?? '[]', true),
                'conditions' => $data['conditions'] ?? json_decode($field['conditions'] ?? '[]', true),
                'wrapper' => $data['wrapper'] ?? json_decode($field['wrapper'] ?? '[]', true),
                'foOptions' => $data['foOptions'] ?? json_decode($field['fo_options'] ?? '[]', true),
                'position' => $data['position'] ?? $field['position'],
                'translatable' => $data['translatable'] ?? $field['translatable'],
                'active' => $data['active'] ?? $field['active'],
            ]);

            return $this->json(['success' => true, 'data' => $this->serializeField($this->fieldRepository->findById($id))]);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->fieldRepository->findById($id)) { return $this->jsonError('Field not found', Response::HTTP_NOT_FOUND); }
            $this->fieldRepository->delete($id);
            return $this->json(['success' => true, 'message' => 'Field deleted successfully']);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
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
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    /** @param array<string, mixed> $field @return array<string, mixed> */
    private function serializeField(array $field): array
    {
        return [
            'id' => (int) $field['id_wepresta_acf_field'], 'uuid' => $field['uuid'],
            'groupId' => (int) $field['id_wepresta_acf_group'],
            'parentId' => isset($field['id_parent']) && $field['id_parent'] ? (int) $field['id_parent'] : null,
            'type' => $field['type'], 'title' => $field['title'], 'slug' => $field['slug'],
            'instructions' => $field['instructions'] ?: null, 'config' => json_decode($field['config'] ?? '[]', true),
            'validation' => json_decode($field['validation'] ?? '[]', true), 'conditions' => json_decode($field['conditions'] ?? '[]', true),
            'wrapper' => json_decode($field['wrapper'] ?? '[]', true), 'foOptions' => json_decode($field['fo_options'] ?? '[]', true),
            'position' => (int) $field['position'], 'translatable' => (bool) $field['translatable'], 'active' => (bool) $field['active'],
        ];
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
}

