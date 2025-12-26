<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Template;

use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

/**
 * Exports field groups to JSON format
 */
final class FieldGroupExporter
{
    private const FORMAT_VERSION = '1.0';

    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository
    ) {}

    /** @param array<string, mixed> $options @return array<string, mixed>|null */
    public function exportGroup(int $groupId, array $options = []): ?array
    {
        $group = $this->groupRepository->findById($groupId);
        if ($group === null) { return null; }
        $fields = $this->fieldRepository->findByGroup($groupId);
        return $this->formatGroup($group, $fields, $options);
    }

    /** @param array<string, mixed> $options @return array<string, mixed>|null */
    public function exportGroupBySlug(string $slug, array $options = []): ?array
    {
        $group = $this->groupRepository->findBySlug($slug);
        if ($group === null) { return null; }
        return $this->exportGroup((int) $group['id_wepresta_acf_group'], $options);
    }

    /** @param array<int> $groupIds @param array<string, mixed> $options @return array<string, mixed> */
    public function exportGroups(array $groupIds, array $options = []): array
    {
        $groups = [];
        foreach ($groupIds as $groupId) {
            $exported = $this->exportGroup($groupId, $options);
            if ($exported !== null) { $groups[] = $exported; }
        }
        return $this->wrapExport($groups, $options);
    }

    /** @param array<string, mixed> $options @return array<string, mixed> */
    public function exportAll(array $options = []): array
    {
        $allGroups = $this->groupRepository->findAll();
        $groups = [];
        foreach ($allGroups as $group) {
            $fields = $this->fieldRepository->findByGroup((int) $group['id_wepresta_acf_group']);
            $groups[] = $this->formatGroup($group, $fields, $options);
        }
        return $this->wrapExport($groups, $options);
    }

    /** @param array<string, mixed> $options */
    public function toJson(int $groupId, array $options = []): ?string
    {
        $data = $this->exportGroup($groupId, $options);
        if ($data === null) { return null; }
        return json_encode($this->wrapExport([$data], $options), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    /** @param array<string, mixed> $group @param array<array<string, mixed>> $fields @param array<string, mixed> $options @return array<string, mixed> */
    private function formatGroup(array $group, array $fields, array $options = []): array
    {
        $lockLevel = $options['lock_level'] ?? 'extend';
        $includeIds = $options['include_ids'] ?? false;

        $exportedFields = [];
        foreach ($fields as $field) {
            $exportedField = [
                'slug' => $field['slug'],
                'type' => $field['type'],
                'label' => $field['title'] ?? $field['slug'],
                'position' => (int) ($field['position'] ?? 0),
                'translatable' => (bool) ($field['translatable'] ?? false),
                'config' => $this->decodeJson($field['config'] ?? '{}'),
                'validation' => $this->decodeJson($field['validation'] ?? '{}'),
                'lock_level' => $options['field_lock_level'] ?? 'extend',
            ];
            if ($includeIds) { $exportedField['_id'] = (int) $field['id_wepresta_acf_field']; }
            $exportedFields[] = $exportedField;
        }

        usort($exportedFields, fn($a, $b) => $a['position'] <=> $b['position']);

        $exported = [
            'slug' => $group['slug'],
            'title' => $group['title'],
            'lock_level' => $lockLevel,
            'location' => $this->decodeJson($group['location_rules'] ?? '{}'),
            'settings' => $this->decodeJson($group['bo_options'] ?? '{}'),
            'position' => (int) ($group['priority'] ?? 0),
            'active' => (bool) ($group['active'] ?? true),
            'fields' => $exportedFields,
        ];

        if ($includeIds) { $exported['_id'] = (int) $group['id_wepresta_acf_group']; }
        return $exported;
    }

    /** @param array<array<string, mixed>> $groups @param array<string, mixed> $options @return array<string, mixed> */
    private function wrapExport(array $groups, array $options = []): array
    {
        return [
            'version' => self::FORMAT_VERSION,
            'exported_at' => date('c'),
            'source' => $options['source'] ?? 'wepresta_acf',
            'source_version' => $options['source_version'] ?? '1.0.0',
            'template' => $options['template_name'] ?? null,
            'template_version' => $options['template_version'] ?? null,
            'groups' => $groups,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $json): array
    {
        if (empty($json) || $json === '{}' || $json === '[]') { return []; }
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (\JsonException $e) {
            return [];
        }
    }
}

