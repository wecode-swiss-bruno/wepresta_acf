<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly SlugGenerator $slugGenerator,
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
                'uuid' => Uuid::uuid4()->toString(), 'title' => $data['title'], 'slug' => $slug,
                'description' => $data['description'] ?? null, 'locationRules' => $data['locationRules'] ?? [],
                'placementTab' => $data['placementTab'] ?? 'modules', 'placementPosition' => $data['placementPosition'] ?? null,
                'priority' => $data['priority'] ?? 10, 'boOptions' => $data['boOptions'] ?? [],
                'foOptions' => $data['foOptions'] ?? [], 'active' => $data['active'] ?? true,
            ]);
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
                'boOptions' => $data['boOptions'] ?? json_decode($group['bo_options'] ?? '[]', true),
                'foOptions' => $data['foOptions'] ?? json_decode($group['fo_options'] ?? '[]', true),
                'active' => $data['active'] ?? $group['active'],
            ]);
            return $this->json(['success' => true, 'data' => $this->serializeGroup($this->groupRepository->findById($id), true)]);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->groupRepository->findById($id)) { return $this->jsonError('Group not found', Response::HTTP_NOT_FOUND); }
            $this->groupRepository->delete($id);
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
                'uuid' => Uuid::uuid4()->toString(), 'title' => $group['title'] . ' (Copy)', 'slug' => $newSlug,
                'description' => $group['description'], 'locationRules' => json_decode($group['location_rules'] ?? '[]', true),
                'placementTab' => $group['placement_tab'], 'placementPosition' => $group['placement_position'],
                'priority' => $group['priority'], 'boOptions' => json_decode($group['bo_options'] ?? '[]', true),
                'foOptions' => json_decode($group['fo_options'] ?? '[]', true), 'active' => false,
            ]);

            foreach ($this->fieldRepository->findAllByGroup((int) $group['id_wepresta_acf_group']) as $field) {
                $this->fieldRepository->create([
                    'uuid' => Uuid::uuid4()->toString(), 'idAcfGroup' => $newGroupId,
                    'type' => $field['type'], 'title' => $field['title'],
                    'slug' => $this->slugGenerator->generateUnique($field['slug'], fn($s, $i) => $this->fieldRepository->slugExistsInGroup($s, $newGroupId, $i)),
                    'instructions' => $field['instructions'], 'config' => json_decode($field['config'] ?? '[]', true),
                    'validation' => json_decode($field['validation'] ?? '[]', true), 'conditions' => json_decode($field['conditions'] ?? '[]', true),
                    'wrapper' => json_decode($field['wrapper'] ?? '[]', true), 'foOptions' => json_decode($field['fo_options'] ?? '[]', true),
                    'position' => $field['position'], 'translatable' => $field['translatable'], 'active' => $field['active'],
                ]);
            }
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
            'priority' => (int) $group['priority'], 'boOptions' => json_decode($group['bo_options'] ?? '[]', true),
            'foOptions' => json_decode($group['fo_options'] ?? '[]', true), 'active' => (bool) $group['active'],
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
        $result = [
            'id' => (int) $field['id_wepresta_acf_field'], 'uuid' => $field['uuid'],
            'groupId' => (int) $field['id_wepresta_acf_group'],
            'parentId' => isset($field['id_parent']) && $field['id_parent'] ? (int) $field['id_parent'] : null,
            'type' => $field['type'], 'title' => $field['title'], 'slug' => $field['slug'],
            'instructions' => $field['instructions'] ?: null, 'config' => json_decode($field['config'] ?? '[]', true),
            'validation' => json_decode($field['validation'] ?? '[]', true), 'conditions' => json_decode($field['conditions'] ?? '[]', true),
            'wrapper' => json_decode($field['wrapper'] ?? '[]', true), 'foOptions' => json_decode($field['fo_options'] ?? '[]', true),
            'position' => (int) $field['position'], 'translatable' => (bool) $field['translatable'], 'active' => (bool) $field['active'],
            'dateAdd' => $field['date_add'], 'dateUpd' => $field['date_upd'],
        ];
        if ($field['type'] === 'repeater') {
            $result['children'] = array_map(fn($c) => $this->serializeField($c), $this->fieldRepository->findByParent((int) $field['id_wepresta_acf_field']));
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
}

