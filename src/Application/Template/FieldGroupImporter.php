<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Template;

use Exception;
use JsonException;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

/**
 * Imports field groups from JSON format.
 */
final class FieldGroupImporter
{
    public const STRATEGY_SKIP = 'skip';

    public const STRATEGY_REPLACE = 'replace';

    public const STRATEGY_MERGE = 'merge';

    public const STRATEGY_RENAME = 'rename';

    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function importJson(string $json, array $options = []): ImportResult
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return new ImportResult(false, 'Invalid JSON: ' . $e->getMessage());
        }

        return $this->import($data, $options);
    }

    /**
     * @param array<string, mixed> $data @param array<string, mixed> $options
     */
    public function import(array $data, array $options = []): ImportResult
    {
        $strategy = $options['strategy'] ?? self::STRATEGY_SKIP;
        $dryRun = $options['dry_run'] ?? false;
        $prefix = $options['prefix'] ?? '';

        $result = new ImportResult(true);
        $result->setVersion($data['version'] ?? 'unknown');
        $result->setSource($data['source'] ?? 'unknown');

        $groups = $data['groups'] ?? [];

        if (empty($groups)) {
            $result->addWarning('No groups to import');

            return $result;
        }

        foreach ($groups as $groupData) {
            try {
                $this->importGroup($groupData, $strategy, $dryRun, $prefix, $result);
            } catch (Exception $e) {
                $result->addError($groupData['slug'] ?? 'unknown', $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): ImportResult
    {
        return $this->import($data, ['dry_run' => true]);
    }

    /**
     * @param array<string, mixed> $groupData
     */
    private function importGroup(array $groupData, string $strategy, bool $dryRun, string $prefix, ImportResult $result): void
    {
        $slug = $prefix . ($groupData['slug'] ?? '');

        if (empty($slug)) {
            $result->addError('unknown', 'Group slug is required');

            return;
        }

        $existing = $this->groupRepository->findBySlug($slug);
        $existingId = $existing ? (int) $existing['id_wepresta_acf_group'] : null;

        if ($existingId !== null) {
            switch ($strategy) {
                case self::STRATEGY_SKIP:
                    $result->addSkipped($slug, 'Group already exists');

                    return;

                case self::STRATEGY_RENAME:
                    $slug = $this->generateUniqueSlug($slug);
                    $existingId = null;

                    break;

                case self::STRATEGY_REPLACE:
                    if (! $dryRun) {
                        $this->fieldRepository->deleteByGroup($existingId);
                        $this->groupRepository->delete($existingId);
                    }
                    $existingId = null;

                    break;
            }
        }

        if ($dryRun) {
            $existingId !== null ? $result->addUpdated($slug) : $result->addCreated($slug);

            return;
        }

        $groupToSave = [
            'slug' => $slug,
            'title' => $groupData['title'] ?? $slug,
            'location_rules' => json_encode($groupData['location'] ?? []),
            'bo_options' => json_encode($groupData['settings'] ?? []),
            'priority' => $groupData['position'] ?? 0,
            'active' => $groupData['active'] ?? true,
        ];

        if ($existingId !== null && $strategy === self::STRATEGY_MERGE) {
            $this->groupRepository->update($existingId, $groupToSave);
            $groupId = $existingId;
            $result->addUpdated($slug);
        } else {
            $groupId = $this->groupRepository->create($groupToSave);
            $result->addCreated($slug);
        }

        $this->importFields($groupId, $groupData['fields'] ?? [], $existingId !== null && $strategy === self::STRATEGY_MERGE, $result);
    }

    /**
     * @param array<array<string, mixed>> $fields
     */
    private function importFields(int $groupId, array $fields, bool $merge, ImportResult $result): void
    {
        $existingFields = [];

        if ($merge) {
            foreach ($this->fieldRepository->findByGroup($groupId) as $field) {
                $existingFields[$field['slug']] = $field;
            }
        }

        foreach ($fields as $fieldData) {
            $fieldSlug = $fieldData['slug'] ?? '';

            if (empty($fieldSlug)) {
                continue;
            }

            $fieldToSave = [
                'id_wepresta_acf_group' => $groupId,
                'slug' => $fieldSlug,
                'type' => $fieldData['type'] ?? 'text',
                'title' => $fieldData['label'] ?? $fieldSlug,
                'position' => $fieldData['position'] ?? 0,
                'translatable' => $fieldData['translatable'] ?? false,
                'config' => json_encode($fieldData['config'] ?? []),
                'validation' => json_encode($fieldData['validation'] ?? []),
            ];

            if (isset($existingFields[$fieldSlug])) {
                $this->fieldRepository->update((int) $existingFields[$fieldSlug]['id_wepresta_acf_field'], $fieldToSave);
            } else {
                $this->fieldRepository->create($fieldToSave);
            }
        }

        $result->addFieldsImported(\count($fields));
    }

    private function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while ($this->groupRepository->findBySlug($slug) !== null) {
            $slug = $baseSlug . '_' . $counter++;
        }

        return $slug;
    }
}
