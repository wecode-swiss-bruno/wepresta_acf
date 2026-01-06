<?php
/**
 * SyncService - JSON Sync for Field Groups
 *
 * Handles bidirectional synchronization between database and theme JSON files.
 * Inspired by ACF Extended JSON/PHP Sync feature.
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Trait\LoggerTrait;

final class SyncService
{
    use LoggerTrait;

    private const JSON_VERSION = '1.0';
    private const GROUPS_SUBDIR = 'groups';
    private const CHECKSUM_ALGO = 'sha256';

    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly ConfigurationAdapter $config,
        private readonly string $modulePath
    ) {
    }

    /**
     * Check if sync is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->config->getBool('WEPRESTA_ACF_SYNC_ENABLED', false);
    }

    /**
     * Get the resolved sync path.
     */
    public function getSyncPath(): string
    {
        $pathType = $this->config->getString('WEPRESTA_ACF_SYNC_PATH_TYPE') ?: 'theme';

        return match ($pathType) {
            'parent' => $this->getParentThemePath(),
            'custom' => $this->getCustomPath(),
            default => $this->getActiveThemePath(),
        };
    }

    /**
     * Get groups directory path.
     */
    public function getGroupsPath(): string
    {
        return $this->getSyncPath() . self::GROUPS_SUBDIR . '/';
    }

    /**
     * Push a group to theme JSON.
     */
    public function pushGroup(int $groupId): array
    {
        $group = $this->groupRepository->findOneBy(['id_wepresta_acf_group' => $groupId]);
        if (!$group) {
            return ['success' => false, 'error' => 'Group not found'];
        }

        $fields = $this->fieldRepository->findAllByGroup($groupId);
        $jsonData = $this->buildGroupJson($group, $fields);

        $groupsPath = $this->getGroupsPath();
        if (!$this->ensureDirectory($groupsPath)) {
            return ['success' => false, 'error' => 'Cannot create sync directory'];
        }

        $filePath = $groupsPath . $group['slug'] . '.json';
        $result = file_put_contents(
            $filePath,
            json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        if ($result === false) {
            return ['success' => false, 'error' => 'Failed to write JSON file'];
        }

        $this->logInfo('Group pushed to theme', [
            'group_id' => $groupId,
            'slug' => $group['slug'],
            'path' => $filePath,
        ]);

        return [
            'success' => true,
            'path' => $filePath,
            'checksum' => $jsonData['checksum'],
        ];
    }

    /**
     * Push all groups to theme JSON.
     */
    public function pushAllGroups(): array
    {
        $groups = $this->groupRepository->findAll();
        $results = ['pushed' => 0, 'failed' => 0, 'errors' => []];

        foreach ($groups as $group) {
            $result = $this->pushGroup((int) $group['id_wepresta_acf_group']);
            if ($result['success']) {
                $results['pushed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $group['slug'] . ': ' . ($result['error'] ?? 'Unknown error');
            }
        }

        return $results;
    }

    /**
     * Pull a group from theme JSON by slug.
     */
    public function pullGroup(string $slug): array
    {
        $filePath = $this->getGroupsPath() . $slug . '.json';
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'JSON file not found'];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['success' => false, 'error' => 'Cannot read JSON file'];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ['success' => false, 'error' => 'Invalid JSON: ' . $e->getMessage()];
        }

        if (!isset($data['group']) || !isset($data['fields'])) {
            return ['success' => false, 'error' => 'Invalid JSON structure'];
        }

        // Check if group exists
        $existing = $this->groupRepository->findBySlug($slug);

        if ($existing) {
            // Update existing
            $groupId = (int) $existing['id_wepresta_acf_group'];
            $this->groupRepository->update($groupId, $this->mapJsonToGroupData($data['group']));

            // Delete existing fields and recreate
            $this->fieldRepository->deleteByGroup($groupId);
        } else {
            // Create new
            $groupData = $this->mapJsonToGroupData($data['group']);
            $groupData['uuid'] = $this->generateUuid();
            $groupId = $this->groupRepository->create($groupData);
            $this->groupRepository->addAllShopAssociations($groupId);
        }

        // Create fields
        foreach ($data['fields'] as $position => $fieldData) {
            $this->createFieldFromJson($groupId, $fieldData, $position);
        }

        $this->logInfo('Group pulled from theme', [
            'slug' => $slug,
            'group_id' => $groupId,
            'fields_count' => count($data['fields']),
        ]);

        return [
            'success' => true,
            'group_id' => $groupId,
            'fields_count' => count($data['fields']),
            'action' => $existing ? 'updated' : 'created',
        ];
    }

    /**
     * Pull all groups from theme JSON.
     */
    public function pullAllGroups(): array
    {
        $groupsPath = $this->getGroupsPath();
        if (!is_dir($groupsPath)) {
            return ['pulled' => 0, 'failed' => 0, 'errors' => ['Sync directory does not exist']];
        }

        $files = glob($groupsPath . '*.json');
        $results = ['pulled' => 0, 'failed' => 0, 'errors' => []];

        foreach ($files as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $result = $this->pullGroup($slug);

            if ($result['success']) {
                $results['pulled']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $slug . ': ' . ($result['error'] ?? 'Unknown error');
            }
        }

        return $results;
    }

    /**
     * Get sync status for all groups.
     */
    public function getSyncStatus(): array
    {
        $dbGroups = $this->groupRepository->findAll();
        $themeFiles = $this->getThemeJsonFiles();

        $status = [
            'db_count' => count($dbGroups),
            'theme_count' => count($themeFiles),
            'synced' => 0,
            'need_push' => 0,
            'need_pull' => 0,
            'groups' => [],
        ];

        $dbSlugs = array_column($dbGroups, 'slug');
        $themeSlugs = array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $themeFiles);

        // Check DB groups
        foreach ($dbGroups as $group) {
            $slug = $group['slug'];
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findAllByGroup($groupId);

            $dbChecksum = $this->calculateChecksum($group, $fields);
            $themeChecksum = $this->getThemeFileChecksum($slug);

            if (!in_array($slug, $themeSlugs, true)) {
                $groupStatus = 'need_push';
                $status['need_push']++;
            } elseif ($dbChecksum === $themeChecksum) {
                $groupStatus = 'synced';
                $status['synced']++;
            } else {
                $groupStatus = 'modified';
                $status['need_push']++;
            }

            $status['groups'][] = [
                'id' => $groupId,
                'slug' => $slug,
                'title' => $group['title'],
                'source' => 'database',
                'status' => $groupStatus,
                'db_checksum' => $dbChecksum,
                'theme_checksum' => $themeChecksum,
            ];
        }

        // Check theme-only groups
        foreach ($themeSlugs as $slug) {
            if (!in_array($slug, $dbSlugs, true)) {
                $status['need_pull']++;
                $status['groups'][] = [
                    'id' => null,
                    'slug' => $slug,
                    'title' => $slug,
                    'source' => 'theme',
                    'status' => 'theme_only',
                    'db_checksum' => null,
                    'theme_checksum' => $this->getThemeFileChecksum($slug),
                ];
            }
        }

        return $status;
    }

    /**
     * Get sync status for a single group.
     */
    public function getGroupSyncStatus(int $groupId): ?array
    {
        $group = $this->groupRepository->findOneBy(['id_wepresta_acf_group' => $groupId]);
        if (!$group) {
            return null;
        }

        $fields = $this->fieldRepository->findAllByGroup($groupId);
        $dbChecksum = $this->calculateChecksum($group, $fields);
        $themeChecksum = $this->getThemeFileChecksum($group['slug']);

        if ($themeChecksum === null) {
            $status = 'need_push';
        } elseif ($dbChecksum === $themeChecksum) {
            $status = 'synced';
        } else {
            $status = 'modified';
        }

        return [
            'status' => $status,
            'db_checksum' => $dbChecksum,
            'theme_checksum' => $themeChecksum,
        ];
    }

    /**
     * Auto-sync on save if enabled.
     */
    public function autoSyncOnSave(int $groupId): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (!$this->config->getBool('WEPRESTA_ACF_AUTO_SYNC_ON_SAVE', false)) {
            return;
        }

        $this->pushGroup($groupId);
    }

    /**
     * Build JSON data for a group.
     */
    private function buildGroupJson(array $group, array $fields): array
    {
        $groupData = [
            'slug' => $group['slug'],
            'title' => $group['title'],
            'description' => $group['description'] ?? '',
            'location_rules' => json_decode($group['location_rules'] ?? '[]', true),
            'placement_tab' => $group['placement_tab'] ?? 'description',
            'placement_position' => $group['placement_position'] ?? '',
            'priority' => (int) ($group['priority'] ?? 10),
            'bo_options' => json_decode($group['bo_options'] ?? '{}', true),
            'fo_options' => json_decode($group['fo_options'] ?? '{}', true),
            'active' => (bool) ($group['active'] ?? true),
        ];

        $fieldsData = array_map(fn($f) => $this->mapFieldToJson($f), $fields);

        $checksum = $this->calculateChecksum($group, $fields);

        return [
            'version' => self::JSON_VERSION,
            'module_version' => \WeprestaAcf::VERSION,
            'exported_at' => date('c'),
            'checksum' => $checksum,
            'group' => $groupData,
            'fields' => $fieldsData,
        ];
    }

    /**
     * Map a field to JSON format.
     */
    private function mapFieldToJson(array $field): array
    {
        return [
            'slug' => $field['slug'],
            'type' => $field['type'],
            'title' => $field['title'],
            'instructions' => $field['instructions'] ?? '',
            'position' => (int) ($field['position'] ?? 0),
            'config' => json_decode($field['config'] ?? '{}', true),
            'validation' => json_decode($field['validation'] ?? '{}', true),
            'conditions' => json_decode($field['conditions'] ?? '[]', true),
            'wrapper' => json_decode($field['wrapper'] ?? '{}', true),
            'fo_options' => json_decode($field['fo_options'] ?? '{}', true),
            'value_translatable' => (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false),
            'active' => (bool) ($field['active'] ?? true),
        ];
    }

    /**
     * Map JSON group data to repository format.
     */
    private function mapJsonToGroupData(array $json): array
    {
        return [
            'title' => $json['title'] ?? '',
            'slug' => $json['slug'] ?? '',
            'description' => $json['description'] ?? '',
            'locationRules' => $json['location_rules'] ?? [],
            'placementTab' => $json['placement_tab'] ?? 'description',
            'placementPosition' => $json['placement_position'] ?? '',
            'priority' => $json['priority'] ?? 10,
            'boOptions' => $json['bo_options'] ?? [],
            'foOptions' => $json['fo_options'] ?? [],
            'active' => $json['active'] ?? true,
        ];
    }

    /**
     * Create a field from JSON data.
     */
    private function createFieldFromJson(int $groupId, array $json, int $position): int
    {
        return $this->fieldRepository->create([
            'uuid' => $this->generateUuid(),
            'idAcfGroup' => $groupId,
            'type' => $json['type'] ?? 'text',
            'title' => $json['title'] ?? '',
            'slug' => $json['slug'] ?? '',
            'instructions' => $json['instructions'] ?? '',
            'config' => $json['config'] ?? [],
            'validation' => $json['validation'] ?? [],
            'conditions' => $json['conditions'] ?? [],
            'wrapper' => $json['wrapper'] ?? [],
            'foOptions' => $json['fo_options'] ?? [],
            'position' => $json['position'] ?? $position,
            'translatable' => $json['translatable'] ?? false,
            'active' => $json['active'] ?? true,
        ]);
    }

    /**
     * Calculate checksum for a group and its fields.
     */
    private function calculateChecksum(array $group, array $fields): string
    {
        $data = [
            'group' => [
                'slug' => $group['slug'],
                'title' => $group['title'],
                'location_rules' => $group['location_rules'],
                'placement_tab' => $group['placement_tab'],
                'priority' => $group['priority'],
                'active' => $group['active'],
            ],
            'fields' => array_map(fn($f) => [
                'slug' => $f['slug'],
                'type' => $f['type'],
                'title' => $f['title'],
                'config' => $f['config'],
                'validation' => $f['validation'],
            ], $fields),
        ];

        $json = json_encode($data, JSON_THROW_ON_ERROR);

        return self::CHECKSUM_ALGO . ':' . hash(self::CHECKSUM_ALGO, $json);
    }

    /**
     * Get checksum from theme JSON file.
     */
    private function getThemeFileChecksum(string $slug): ?string
    {
        $filePath = $this->getGroupsPath() . $slug . '.json';
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return $data['checksum'] ?? null;
    }

    /**
     * Get list of theme JSON files.
     */
    private function getThemeJsonFiles(): array
    {
        $groupsPath = $this->getGroupsPath();
        if (!is_dir($groupsPath)) {
            return [];
        }

        return glob($groupsPath . '*.json') ?: [];
    }

    /**
     * Get active theme ACF path.
     */
    private function getActiveThemePath(): string
    {
        $themeName = \Configuration::get('PS_THEME_NAME') ?: 'classic';

        return _PS_THEME_DIR_ . 'acf/';
    }

    /**
     * Get parent theme ACF path.
     */
    private function getParentThemePath(): string
    {
        // Check if current theme has a parent
        $themeName = \Configuration::get('PS_THEME_NAME') ?: 'classic';
        $themeDir = _PS_ALL_THEMES_DIR_ . $themeName . '/';

        if (file_exists($themeDir . 'theme.yml')) {
            $themeConfig = \Symfony\Component\Yaml\Yaml::parseFile($themeDir . 'theme.yml');
            if (!empty($themeConfig['parent'])) {
                return _PS_ALL_THEMES_DIR_ . $themeConfig['parent'] . '/acf/';
            }
        }

        // Fallback to active theme
        return $this->getActiveThemePath();
    }

    /**
     * Get custom sync path.
     */
    private function getCustomPath(): string
    {
        $customPath = $this->config->getString('WEPRESTA_ACF_SYNC_CUSTOM_PATH');
        if (empty($customPath)) {
            return $this->getActiveThemePath();
        }

        // Ensure trailing slash
        return rtrim($customPath, '/') . '/';
    }

    /**
     * Ensure directory exists.
     */
    private function ensureDirectory(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0755, true);
    }

    /**
     * Generate UUID v4.
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

