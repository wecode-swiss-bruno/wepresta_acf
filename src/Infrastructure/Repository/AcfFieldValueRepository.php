<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Context;
use DbQuery;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

/**
 * Repository for ACF Field Values extending WEDEV Core AbstractRepository.
 */
final class AcfFieldValueRepository extends AbstractRepository implements AcfFieldValueRepositoryInterface
{
    // =========================================================================
    // Constants
    // =========================================================================

    private const TABLE_FIELD = 'wepresta_acf_field';

    private const TABLE_GROUP = 'wepresta_acf_group';

    private const FK_FIELD = 'id_wepresta_acf_field';

    private const FK_GROUP = 'id_wepresta_acf_group';

    // =========================================================================
    // Legacy Product Methods (Backward Compatibility)
    // =========================================================================

    public function findByProductWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        return $this->findByEntityWithMeta('product', $productId, $shopId, $langId);
    }

    public function save(
        int $fieldId,
        int $productId,
        ?string $value,
        ?int $shopId = null,
        ?int $langId = null,
        ?bool $isTranslatable = null,
        ?string $indexValue = null
    ): bool {
        return $this->saveEntity($fieldId, 'product', $productId, $value, $shopId, $langId, $isTranslatable, $indexValue);
    }

    public function deleteByProduct(int $productId, ?int $shopId = null): bool
    {
        return $this->deleteByEntity('product', $productId, $shopId);
    }

    public function deleteByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): bool
    {
        return $this->deleteByFieldAndEntity($fieldId, 'product', $productId, $shopId, $langId);
    }

    // =========================================================================
    // Field Deletion
    // =========================================================================

    public function deleteByField(int $fieldId): bool
    {
        return $this->deleteBy([self::FK_FIELD => $fieldId]) >= 0;
    }

    public function deleteTranslatableValuesByField(int $fieldId): bool
    {
        $sql = 'DELETE fvl FROM `' . $this->dbPrefix . $this->getLangTableName() . '` AS fvl
                INNER JOIN `' . $this->dbPrefix . $this->getTableName() . '` AS fv 
                    ON fvl.' . $this->getPrimaryKey() . ' = fv.' . $this->getPrimaryKey() . '
                WHERE fv.' . self::FK_FIELD . ' = ' . (int) $fieldId;

        return $this->db->execute($sql);
    }

    // =========================================================================
    // Generic Entity Query Methods
    // =========================================================================

    public function findByEntity(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        [$shopId, $langId] = $this->resolveContext($shopId, $langId);

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.' . self::FK_FIELD . ', f.value_translatable, fv.' . $this->getPrimaryKey())
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::TABLE_FIELD, 'f', 'fv.' . self::FK_FIELD . ' = f.' . self::FK_FIELD)
            ->where($this->buildEntityWhere($entityType, $entityId, $shopId));

        $results = $this->db->executeS($sql);

        if (!$results) {
            return [];
        }

        $values = [];

        foreach ($results as $row) {
            $value = $row['value'];

            // For translatable fields, try to get translation
            if ((bool) ($row['value_translatable'] ?? false)) {
                $langValue = $this->getLangValue((int) $row[$this->getPrimaryKey()], $langId);

                if ($langValue !== null) {
                    $value = $langValue;
                }
            }

            $values[$row['slug']] = $this->decodeValue($value);
        }

        return $values;
    }

    /**
     * Find all field values for an entity, including ALL languages for translatable fields.
     *
     * @return array<string, mixed> [slug => value] for non-translatable, [slug => [langId => value]] for translatable
     */
    public function findByEntityAllLanguages(string $entityType, int $entityId, ?int $shopId = null): array
    {
        $shopId ??= $this->getContextShopId();

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.value_translatable, f.' . self::FK_FIELD . ', fv.' . $this->getPrimaryKey())
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::TABLE_FIELD, 'f', 'fv.' . self::FK_FIELD . ' = f.' . self::FK_FIELD)
            ->where($this->buildEntityWhere($entityType, $entityId, $shopId))
            ->orderBy('f.slug ASC');

        $results = $this->db->executeS($sql);

        if (!$results) {
            return [];
        }

        $values = [];
        $defaultLangId = $this->getDefaultLangId();

        foreach ($results as $row) {
            $slug = $row['slug'];
            $mainValue = $this->decodeValue($row['value']);
            $isTranslatable = (bool) ($row['value_translatable'] ?? false);

            if (!$isTranslatable) {
                $values[$slug] = $mainValue;

                continue;
            }

            // Build translations array
            $translations = [$defaultLangId => $mainValue];
            $langResults = $this->getAllLangValues((int) $row[$this->getPrimaryKey()]);

            foreach ($langResults as $langRow) {
                $translations[(int) $langRow['id_lang']] = $this->decodeValue($langRow['value']);
            }

            $values[$slug] = $translations;
        }

        return $values;
    }

    /**
     * Find all field values for an entity, indexed by Field ID.
     * Including ALL languages for translatable fields.
     *
     * @return array<int, mixed> [fieldId => value] or [fieldId => [langId => value]]
     */
    public function findByEntityAllLanguagesIndexedById(string $entityType, int $entityId, ?int $shopId = null): array
    {
        $shopId ??= $this->getContextShopId();

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.value_translatable, f.' . self::FK_FIELD . ', fv.' . $this->getPrimaryKey())
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::TABLE_FIELD, 'f', 'fv.' . self::FK_FIELD . ' = f.' . self::FK_FIELD)
            ->where($this->buildEntityWhere($entityType, $entityId, $shopId))
            ->orderBy('f.slug ASC');

        $results = $this->db->executeS($sql);

        if (!$results) {
            return [];
        }

        $values = [];
        $defaultLangId = $this->getDefaultLangId();

        foreach ($results as $row) {
            $fieldId = (int) $row[self::FK_FIELD];
            $mainValue = $this->decodeValue($row['value']);
            $isTranslatable = (bool) ($row['value_translatable'] ?? false);

            if (!$isTranslatable) {
                $values[$fieldId] = $mainValue;
                continue;
            }

            // Build translations array
            $translations = [$defaultLangId => $mainValue];
            $langResults = $this->getAllLangValues((int) $row[$this->getPrimaryKey()]);

            foreach ($langResults as $langRow) {
                $translations[(int) $langRow['id_lang']] = $this->decodeValue($langRow['value']);
            }

            $values[$fieldId] = $translations;
        }

        return $values;
    }

    public function findByEntityWithMeta(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        [$shopId, $langId] = $this->resolveContext($shopId, $langId);

        $sql = new DbQuery();
        $sql->select($this->getMetaSelectFields())
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::TABLE_FIELD, 'f', 'fv.' . self::FK_FIELD . ' = f.' . self::FK_FIELD)
            ->innerJoin(self::TABLE_GROUP, 'g', 'f.' . self::FK_GROUP . ' = g.' . self::FK_GROUP)
            ->where($this->buildEntityWhere($entityType, $entityId, $shopId))
            ->where('(fv.id_lang = ' . $langId . ' OR fv.id_lang IS NULL)')
            ->where('f.active = 1')
            ->where('g.active = 1')
            ->orderBy('f.position ASC');

        $results = $this->db->executeS($sql);

        if (!$results) {
            return [];
        }

        return array_map([$this, 'decodeMetaRow'], $results);
    }

    public function findByFieldAndEntity(
        int $fieldId,
        string $entityType,
        int $entityId,
        ?int $shopId = null,
        ?int $langId = null
    ): ?string {
        [$shopId, $langId] = $this->resolveContext($shopId, $langId);

        $sql = new DbQuery();
        $sql->select('value')
            ->from($this->getTableName())
            ->where(self::FK_FIELD . ' = ' . (int) $fieldId)
            ->where($this->buildEntityWhere($entityType, $entityId, $shopId))
            ->where('(id_lang = ' . $langId . ' OR id_lang IS NULL)');

        $result = $this->db->getValue($sql);

        return $result ?: null;
    }

    /**
     * @return array<int>
     */
    public function findEntitiesByFieldValue(int $fieldId, string $value, string $entityType, ?int $shopId = null): array
    {
        $sql = new DbQuery();
        $sql->select('DISTINCT entity_id')
            ->from($this->getTableName())
            ->where(self::FK_FIELD . ' = ' . (int) $fieldId)
            ->where('entity_type = "' . pSQL($entityType) . '"')
            ->where('value_index = "' . pSQL($value) . '"');

        if ($shopId !== null) {
            $sql->where('id_shop = ' . (int) $shopId);
        }

        $results = $this->db->executeS($sql);

        return $results
            ? array_column($results, 'entity_id')
            : [];
    }

    public function findAllByGroup(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('fv.*')
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::TABLE_FIELD, 'f', 'fv.' . self::FK_FIELD . ' = f.' . self::FK_FIELD)
            ->where('f.' . self::FK_GROUP . ' = ' . (int) $groupId)
            ->orderBy('fv.' . $this->getPrimaryKey() . ' ASC');

        return $this->db->executeS($sql) ?: [];
    }

    // =========================================================================
    // Save Operations
    // =========================================================================

    public function saveEntity(
        int $fieldId,
        string $entityType,
        int $entityId,
        ?string $value,
        ?int $shopId = null,
        ?int $langId = null,
        ?bool $isTranslatable = null,
        ?string $indexValue = null
    ): bool {
        $shopId ??= $this->getContextShopId();
        $defaultLangId = $this->getDefaultLangId();
        $isTranslatable ??= $this->isFieldTranslatable($fieldId);
        $indexValue ??= $value !== null ? substr($value, 0, 255) : null;

        // For translatable fields with non-default language
        if ($isTranslatable && $langId !== null && $langId !== $defaultLangId) {
            return $this->saveTranslation($fieldId, $entityType, $entityId, $shopId, $langId, $value, $indexValue);
        }

        // For non-translatable fields OR default language of translatable field
        return $this->saveMainValue($fieldId, $entityType, $entityId, $shopId, $value, $indexValue, $isTranslatable, $langId);
    }

    // =========================================================================
    // Delete Operations
    // =========================================================================

    public function deleteByEntity(string $entityType, int $entityId, ?int $shopId = null): bool
    {
        $where = 'entity_type = "' . pSQL($entityType) . '" AND entity_id = ' . (int) $entityId;

        if ($shopId !== null) {
            $where .= ' AND id_shop = ' . (int) $shopId;
        }

        return $this->db->delete($this->getTableName(), $where);
    }

    public function deleteByFieldAndEntity(
        int $fieldId,
        string $entityType,
        int $entityId,
        ?int $shopId = null,
        ?int $langId = null
    ): bool {
        $where = $this->buildFieldEntityWhere($fieldId, $entityType, $entityId, $shopId);

        // Delete from lang table if langId specified
        if ($langId !== null) {
            $this->deleteLangValuesByWhere($where, $langId);
        }

        return $this->db->delete($this->getTableName(), $where);
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    protected function getTableName(): string
    {
        return 'wepresta_acf_field_value';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_field_value';
    }

    private function getLangTableName(): string
    {
        return $this->getTableName() . '_lang';
    }

    // =========================================================================
    // Private Helpers - Context
    // =========================================================================

    /**
     * @return array{int, int} [shopId, langId]
     */
    private function resolveContext(?int $shopId, ?int $langId): array
    {
        return [
            $shopId ?? $this->getContextShopId(),
            $langId ?? $this->getContextLangId(),
        ];
    }

    private function getContextShopId(): int
    {
        return (int) Context::getContext()->shop->id;
    }

    private function getContextLangId(): int
    {
        return (int) Context::getContext()->language->id;
    }

    private function getDefaultLangId(): int
    {
        return (int) Configuration::get('PS_LANG_DEFAULT');
    }

    // =========================================================================
    // Private Helpers - Query Building
    // =========================================================================

    private function buildEntityWhere(string $entityType, int $entityId, int $shopId): string
    {
        return 'fv.entity_type = "' . pSQL($entityType) . '"'
            . ' AND fv.entity_id = ' . $entityId
            . ' AND fv.id_shop = ' . $shopId;
    }

    private function buildFieldEntityWhere(int $fieldId, string $entityType, int $entityId, ?int $shopId): string
    {
        $where = self::FK_FIELD . ' = ' . $fieldId
            . ' AND entity_type = "' . pSQL($entityType) . '"'
            . ' AND entity_id = ' . $entityId;

        if ($shopId !== null) {
            $where .= ' AND id_shop = ' . $shopId;
        }

        return $where;
    }

    private function getMetaSelectFields(): string
    {
        return '
            fv.' . $this->getPrimaryKey() . ',
            fv.' . self::FK_FIELD . ',
            fv.entity_type,
            fv.entity_id,
            fv.id_shop,
            fv.id_lang,
            fv.value,
            fv.value_index,
            f.uuid as field_uuid,
            f.title as field_title,
            f.slug as field_slug,
            f.instructions as field_instructions,
            f.type as field_type,
            f.config as field_config,
            f.validation as field_validation,
            f.conditions as field_conditions,
            f.wrapper as field_wrapper,
            f.position as field_position,
            f.translatable as field_translatable,
            g.uuid as group_uuid,
            g.title as group_title,
            g.bo_options as group_bo_options
        ';
    }

    // =========================================================================
    // Private Helpers - Value Processing
    // =========================================================================

    private function decodeValue(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // First try direct decode
        $decoded = json_decode($value, true);

        // If decode succeeded, return it
        if ($decoded !== null || json_last_error() === JSON_ERROR_NONE) {
            return $decoded ?? $value;
        }

        // If decode failed, it might be due to literal newlines/tabs in the JSON
        // Try to sanitize and decode again
        $sanitized = preg_replace_callback(
            '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/s',
            function ($matches) {
                // Escape literal newlines and tabs inside string values
                $content = $matches[1];
                $content = str_replace(["\r\n", "\r", "\n", "\t"], ["\\r\\n", "\\r", "\\n", "\\t"], $content);
                return '"' . $content . '"';
            },
            $value
        );

        $decoded = json_decode($sanitized, true);

        // Return decoded if successful, otherwise original value
        if ($decoded !== null || json_last_error() === JSON_ERROR_NONE) {
            return $decoded ?? $value;
        }

        return $value;
    }

    private function decodeMetaRow(array $row): array
    {
        $jsonFields = ['field_config', 'field_validation', 'field_conditions', 'field_wrapper', 'group_bo_options'];
        $defaults = ['field_config' => [], 'field_validation' => [], 'field_conditions' => [], 'field_wrapper' => [], 'group_bo_options' => []];

        foreach ($jsonFields as $field) {
            $row[$field] = json_decode($row[$field] ?: '[]', true) ?? $defaults[$field];
        }

        return $row;
    }

    private function isFieldTranslatable(int $fieldId): bool
    {
        $fieldRepo = new AcfFieldRepository();
        $field = $fieldRepo->findById($fieldId);

        return $field && (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);
    }

    // =========================================================================
    // Private Helpers - Lang Table Operations
    // =========================================================================

    private function getLangValue(int $valueId, int $langId): ?string
    {
        $sql = 'SELECT value FROM `' . $this->dbPrefix . $this->getLangTableName() . '`
                WHERE ' . $this->getPrimaryKey() . ' = ' . $valueId . ' AND id_lang = ' . $langId;

        $result = $this->db->getRow($sql);

        return $result['value'] ?? null;
    }

    private function getAllLangValues(int $valueId): array
    {
        $sql = 'SELECT id_lang, value FROM `' . $this->dbPrefix . $this->getLangTableName() . '`
                WHERE ' . $this->getPrimaryKey() . ' = ' . $valueId;

        return $this->db->executeS($sql) ?: [];
    }

    private function saveLangValue(int $valueId, int $langId, ?string $value, ?string $indexValue): bool
    {
        $valueSql = $value !== null ? "'" . pSQL($value) . "'" : 'NULL';
        $indexSql = $indexValue !== null ? "'" . pSQL($indexValue) . "'" : 'NULL';

        $sql = 'INSERT INTO `' . $this->dbPrefix . $this->getLangTableName() . '`
                (`' . $this->getPrimaryKey() . '`, `id_lang`, `value`, `value_index`)
                VALUES (' . $valueId . ', ' . $langId . ', ' . $valueSql . ', ' . $indexSql . ')
                ON DUPLICATE KEY UPDATE `value` = ' . $valueSql . ', `value_index` = ' . $indexSql;

        return $this->db->execute($sql);
    }

    private function deleteLangValuesByWhere(string $mainWhere, int $langId): void
    {
        $values = $this->db->executeS(
            'SELECT ' . $this->getPrimaryKey() . ' FROM `' . $this->dbPrefix . $this->getTableName() . '` WHERE ' . $mainWhere
        );

        if (!$values) {
            return;
        }

        $valueIds = array_column($values, $this->getPrimaryKey());
        $langWhere = $this->getPrimaryKey() . ' IN (' . implode(',', $valueIds) . ') AND id_lang = ' . $langId;

        $this->db->delete($this->getLangTableName(), $langWhere);
    }

    // =========================================================================
    // Private Helpers - Save Logic
    // =========================================================================

    private function saveTranslation(
        int $fieldId,
        string $entityType,
        int $entityId,
        int $shopId,
        int $langId,
        ?string $value,
        ?string $indexValue
    ): bool {
        $valueId = $this->getOrCreateMainValueId($fieldId, $entityType, $entityId, $shopId);

        return $this->saveLangValue($valueId, $langId, $value, $indexValue);
    }

    private function saveMainValue(
        int $fieldId,
        string $entityType,
        int $entityId,
        int $shopId,
        ?string $value,
        ?string $indexValue,
        bool $isTranslatable,
        ?int $langId
    ): bool {
        $now = date('Y-m-d H:i:s');
        $where = $this->buildFieldEntityWhere($fieldId, $entityType, $entityId, $shopId);

        $existing = $this->db->getRow(
            'SELECT ' . $this->getPrimaryKey() . ' FROM `' . $this->dbPrefix . $this->getTableName() . '` WHERE ' . $where
        );

        if ($existing) {
            $valueId = (int) $existing[$this->getPrimaryKey()];
            // Note: Do NOT use pSQL() here - Db::update() already escapes values
            $this->db->update($this->getTableName(), [
                'value' => $value,
                'value_index' => $indexValue !== null ? substr($indexValue, 0, 255) : null,
                'date_upd' => $now,
            ], $where);
        } else {
            // Note: Do NOT use pSQL() here - Db::insert() already escapes values
            $success = $this->db->insert($this->getTableName(), [
                self::FK_FIELD => $fieldId,
                'entity_type' => pSQL($entityType),
                'entity_id' => $entityId,
                'id_shop' => $shopId,
                'value' => $value,
                'value_index' => $indexValue !== null ? substr($indexValue, 0, 255) : null,
                'date_add' => $now,
                'date_upd' => $now,
            ]);

            if (!$success) {
                return false;
            }

            $valueId = (int) $this->db->Insert_ID();
        }

        // For translatable fields, also save to lang table
        if ($isTranslatable) {
            $effectiveLangId = $langId ?? $this->getDefaultLangId();
            return $this->saveLangValue($valueId, $effectiveLangId, $value, $indexValue);
        }

        return true;
    }

    private function getOrCreateMainValueId(int $fieldId, string $entityType, int $entityId, int $shopId): int
    {
        $where = $this->buildFieldEntityWhere($fieldId, $entityType, $entityId, $shopId);

        $existing = $this->db->getRow(
            'SELECT ' . $this->getPrimaryKey() . ' FROM `' . $this->dbPrefix . $this->getTableName() . '` WHERE ' . $where
        );

        if ($existing) {
            return (int) $existing[$this->getPrimaryKey()];
        }

        $now = date('Y-m-d H:i:s');

        $this->db->insert($this->getTableName(), [
            self::FK_FIELD => $fieldId,
            'entity_type' => pSQL($entityType),
            'entity_id' => $entityId,
            'id_shop' => $shopId,
            'value' => null,
            'value_index' => null,
            'date_add' => $now,
            'date_upd' => $now,
        ]);

        return (int) $this->db->Insert_ID();
    }
}
