<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use Configuration;
use DbQuery;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

/**
 * Repository for ACF Fields extending WEDEV Core AbstractRepository.
 */
final class AcfFieldRepository extends AbstractRepository implements AcfFieldRepositoryInterface
{
    // =========================================================================
    // Constants
    // =========================================================================

    private const FK_GROUP = 'id_wepresta_acf_group';

    private const TABLE_LANG = 'wepresta_acf_field_lang';

    /** Fields that are always JSON encoded. */
    private const JSON_FIELDS = [
        'config' => 'config',
        'validation' => 'validation',
        'conditions' => 'conditions',
        'wrapper' => 'wrapper',
        'foOptions' => 'fo_options',
    ];

    // =========================================================================
    // Query Methods
    // =========================================================================

    public function findBySlug(string $slug): ?array
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findBySlugAndGroup(string $slug, int $groupId): ?array
    {
        return $this->findOneBy([
            'slug' => $slug,
            self::FK_GROUP => $groupId,
        ]);
    }

    public function findByGroup(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from($this->getTableName())
            ->where(self::FK_GROUP . ' = ' . (int) $groupId)
            ->where('id_parent IS NULL')
            ->where('active = 1')
            ->orderBy('position ASC');

        return $this->db->executeS($sql) ?: [];
    }

    public function findAllByGroup(int $groupId): array
    {
        return $this->findBy([self::FK_GROUP => $groupId], 'position ASC');
    }

    public function findByParent(int $parentId): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from($this->getTableName())
            ->where('id_parent = ' . (int) $parentId)
            ->where('active = 1')
            ->orderBy('position ASC');

        return $this->db->executeS($sql) ?: [];
    }

    public function slugExistsInGroup(string $slug, int $groupId, ?int $excludeId = null): bool
    {
        $sql = new DbQuery();
        $sql->select('1')
            ->from($this->getTableName())
            ->where("slug = '" . pSQL($slug) . "'")
            ->where(self::FK_GROUP . ' = ' . (int) $groupId);

        if ($excludeId !== null) {
            $sql->where($this->getPrimaryKey() . ' != ' . (int) $excludeId);
        }

        return (bool) $this->db->getValue($sql);
    }

    public function getNextPosition(int $groupId): int
    {
        $sql = new DbQuery();
        $sql->select('MAX(position)')
            ->from($this->getTableName())
            ->where(self::FK_GROUP . ' = ' . (int) $groupId);

        $maxPosition = $this->db->getValue($sql);

        return $maxPosition !== false ? ((int) $maxPosition + 1) : 0;
    }

    public function countByGroup(int $groupId): int
    {
        return $this->count([self::FK_GROUP => $groupId]);
    }

    // =========================================================================
    // CRUD Operations
    // =========================================================================

    public function create(array $data): int
    {
        $mapped = $this->mapDataForInsert($data);

        if (! empty($data['idParent'])) {
            $mapped['id_parent'] = (int) $data['idParent'];
        }

        return $this->insert($mapped);
    }

    public function update(int $id, array $data): bool
    {
        return parent::update($id, $this->mapDataForUpdate($data));
    }

    public function deleteByGroup(int $groupId): bool
    {
        return $this->deleteBy([self::FK_GROUP => $groupId]) >= 0;
    }

    // =========================================================================
    // Translations
    // =========================================================================

    /**
     * Save field translations (multilingual metadata: title, instructions, placeholder).
     *
     * @param array $translations Format: ['en' => ['title' => ..., 'instructions' => ..., 'placeholder' => ...], ...]
     */
    public function saveFieldTranslations(int $fieldId, array $translations): bool
    {
        foreach ($translations as $langIdOrCode => $data) {
            $langId = $this->resolveLangId($langIdOrCode);

            if ($langId <= 0) {
                continue;
            }

            $placeholderSql = isset($data['placeholder'])
                ? "'" . pSQL($data['placeholder']) . "'"
                : 'NULL';

            $sql = 'REPLACE INTO `' . $this->dbPrefix . self::TABLE_LANG . '`
                    (`' . $this->getPrimaryKey() . '`, `id_lang`, `title`, `instructions`, `placeholder`)
                    VALUES (
                        ' . $fieldId . ',
                        ' . $langId . ',
                        "' . pSQL($data['title'] ?? '') . '",
                        "' . pSQL($data['instructions'] ?? '') . '",
                        ' . $placeholderSql . '
                    )';

            $this->db->execute($sql);
        }

        return true;
    }

    /**
     * Get field translations for all languages.
     *
     * @return array Format: ['en' => ['title' => ..., 'instructions' => ..., 'placeholder' => ...], ...]
     */
    public function getFieldTranslations(int $fieldId): array
    {
        $sql = new DbQuery();
        $sql->select('fl.title, fl.instructions, fl.placeholder, l.iso_code')
            ->from(self::TABLE_LANG, 'fl')
            ->leftJoin('lang', 'l', 'l.id_lang = fl.id_lang')
            ->where('fl.' . $this->getPrimaryKey() . ' = ' . (int) $fieldId);

        $results = $this->db->executeS($sql);

        if (! $results) {
            return [];
        }

        return array_combine(
            array_column($results, 'iso_code'),
            array_map(static fn (array $row): array => [
                'title' => $row['title'],
                'instructions' => $row['instructions'],
                'placeholder' => $row['placeholder'],
            ], $results)
        );
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    protected function getTableName(): string
    {
        return 'wepresta_acf_field';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_field';
    }

    // =========================================================================
    // Private Helpers - Data Mapping
    // =========================================================================

    private function mapDataForInsert(array $data): array
    {
        $title = $this->extractTranslatedValue($data, 'title');
        $instructions = $this->extractTranslatedValue($data, 'instructions');
        $slug = $this->resolveSlug($data, $title);

        return [
            'uuid' => $data['uuid'] ?? '',
            self::FK_GROUP => (int) ($data['idAcfGroup'] ?? $data[self::FK_GROUP] ?? 0),
            'type' => $data['type'] ?? 'text',
            'title' => $title,
            'slug' => $slug,
            'instructions' => $instructions,
            'position' => (int) ($data['position'] ?? 0),
            'value_translatable' => $this->resolveTranslatable($data),
            'active' => (int) ($data['active'] ?? 1),
            ...$this->mapJsonFields($data),
        ];
    }

    private function mapDataForUpdate(array $data): array
    {
        $mapped = [
            'position' => (int) ($data['position'] ?? 0),
            'value_translatable' => $this->resolveTranslatable($data),
            'active' => (int) ($data['active'] ?? 1),
            ...$this->mapJsonFields($data),
        ];

        // Title - extract from translations or use direct value
        if (isset($data['title']) || isset($data['translations'])) {
            $title = $this->extractTranslatedValue($data, 'title');

            if ($title !== '' || isset($data['title'])) {
                $mapped['title'] = $title !== '' ? $title : trim($data['title'] ?? '');
            }
        }

        // Instructions - extract from translations or use direct value
        if (isset($data['instructions']) || isset($data['translations'])) {
            $instructions = $this->extractTranslatedValue($data, 'instructions');

            if ($instructions !== '' || isset($data['instructions'])) {
                $mapped['instructions'] = $instructions !== '' ? $instructions : trim($data['instructions'] ?? '');
            }
        }

        // Slug - only update if provided
        if (isset($data['slug'])) {
            $mapped['slug'] = $this->resolveSlug($data, $mapped['title'] ?? '');
        }

        return $mapped;
    }

    private function mapJsonFields(array $data): array
    {
        $mapped = [];

        foreach (self::JSON_FIELDS as $source => $target) {
            $mapped[$target] = json_encode($data[$source] ?? [], JSON_THROW_ON_ERROR);
        }

        return $mapped;
    }

    // =========================================================================
    // Private Helpers - Value Resolution
    // =========================================================================

    private function resolveTranslatable(array $data): int
    {
        return (int) ($data['valueTranslatable'] ?? $data['value_translatable'] ?? $data['translatable'] ?? 0);
    }

    private function resolveSlug(array $data, string $fallbackTitle): string
    {
        $slug = trim($data['slug'] ?? '');

        if ($slug !== '' && $slug !== '-') {
            return $slug;
        }

        // Generate from title if available
        if ($fallbackTitle !== '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $fallbackTitle));

            return substr($slug, 0, 255) ?: 'field';
        }

        // Generate unique slug with timestamp
        return substr('field_' . time() . '_' . uniqid('', true), 0, 255);
    }

    // =========================================================================
    // Private Helpers - Translations
    // =========================================================================

    /**
     * Extract value from translations (default language) or use direct value as fallback.
     */
    private function extractTranslatedValue(array $data, string $key): string
    {
        if (! isset($data['translations']) || ! \is_array($data['translations'])) {
            return trim((string) ($data[$key] ?? ''));
        }

        // Try default language by ISO code
        $defaultLangCode = $this->getDefaultLangCode();

        if ($defaultLangCode !== null) {
            $value = $this->getTranslationValue($data['translations'], $defaultLangCode, $key);

            if ($value !== null) {
                return $value;
            }
        }

        // Try default language by ID
        $defaultLangId = $this->getDefaultLangId();

        foreach ($data['translations'] as $langIdOrCode => $translation) {
            if (! \is_array($translation)) {
                continue;
            }

            $langId = $this->resolveLangId($langIdOrCode);

            if ($langId === $defaultLangId && isset($translation[$key])) {
                return trim((string) $translation[$key]);
            }
        }

        // Fallback to first available translation
        foreach ($data['translations'] as $translation) {
            if (\is_array($translation) && ! empty($translation[$key])) {
                return trim((string) $translation[$key]);
            }
        }

        // Final fallback to direct value
        return trim((string) ($data[$key] ?? ''));
    }

    private function getTranslationValue(array $translations, string $langCode, string $key): ?string
    {
        if (! isset($translations[$langCode]) || ! \is_array($translations[$langCode])) {
            return null;
        }

        if (! isset($translations[$langCode][$key])) {
            return null;
        }

        return trim((string) $translations[$langCode][$key]);
    }

    // =========================================================================
    // Private Helpers - Language Resolution
    // =========================================================================

    /**
     * @param int|string $langIdOrCode
     */
    private function resolveLangId($langIdOrCode): int
    {
        return is_numeric($langIdOrCode)
            ? (int) $langIdOrCode
            : $this->getLangIdByCode((string) $langIdOrCode);
    }

    private function getLangIdByCode(string $code): int
    {
        $sql = new DbQuery();
        $sql->select('id_lang')
            ->from('lang')
            ->where("iso_code = '" . pSQL($code) . "'");

        return (int) $this->db->getValue($sql);
    }

    private function getDefaultLangId(): int
    {
        return (int) Configuration::get('PS_LANG_DEFAULT');
    }

    private function getDefaultLangCode(): ?string
    {
        $defaultLangId = $this->getDefaultLangId();

        if ($defaultLangId <= 0) {
            return null;
        }

        $sql = new DbQuery();
        $sql->select('iso_code')
            ->from('lang')
            ->where('id_lang = ' . $defaultLangId);

        $code = $this->db->getValue($sql);

        return $code ?: null;
    }
}
