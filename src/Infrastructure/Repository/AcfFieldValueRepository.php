<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use Context;
use DbQuery;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

/**
 * Repository for ACF Field Values extending WEDEV Core AbstractRepository.
 */
final class AcfFieldValueRepository extends AbstractRepository implements AcfFieldValueRepositoryInterface
{
    private const FIELD_TABLE = 'wepresta_acf_field';
    private const FIELD_FK = 'id_wepresta_acf_field';
    private const GROUP_TABLE = 'wepresta_acf_group';

    protected function getTableName(): string
    {
        return 'wepresta_acf_field_value';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_field_value';
    }

    public function findByProductWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        // Use generic method for backward compatibility
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
        // Use generic method for backward compatibility
        return $this->saveEntity($fieldId, 'product', $productId, $value, $shopId, $langId, $isTranslatable, $indexValue);
    }

    public function deleteByProduct(int $productId, ?int $shopId = null): bool
    {
        // Use generic method for backward compatibility
        return $this->deleteByEntity('product', $productId, $shopId);
    }

    public function deleteByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): bool
    {
        // Use generic method for backward compatibility
        return $this->deleteByFieldAndEntity($fieldId, 'product', $productId, $shopId, $langId);
    }

    public function deleteByField(int $fieldId): bool
    {
        return $this->deleteBy([self::FIELD_FK => $fieldId]) >= 0;
    }

    public function deleteTranslatableValuesByField(int $fieldId): bool
    {
        $where = self::FIELD_FK . ' = ' . (int) $fieldId . ' AND id_lang IS NOT NULL';
        return $this->db->delete($this->getTableName(), $where);
    }

    // =========================================================================
    // NEW GENERIC ENTITY METHODS
    // =========================================================================

    public function findByEntity(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        $shopId ??= (int) Context::getContext()->shop->id;
        $langId ??= (int) Context::getContext()->language->id;

        // Get the latest value for each field without complex MAX subquery
        // This handles NULL id_lang correctly
        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.' . self::FIELD_FK . ', fv.' . $this->getPrimaryKey())
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('fv.entity_type = "' . pSQL($entityType) . '"')
            ->where('fv.entity_id = ' . (int) $entityId)
            ->where('fv.id_shop = ' . (int) $shopId)
            ->where('(fv.id_lang = ' . (int) $langId . ' OR fv.id_lang IS NULL)')
            ->orderBy('fv.' . $this->getPrimaryKey() . ' DESC');
        
        // Get all results and group by field, keeping only the latest
        $allResults = $this->db->executeS($sql);
        
        // Group by field ID and keep only the latest value for each field
        $results = [];
        $seenFields = [];
        if ($allResults) {
            foreach ($allResults as $row) {
                $fieldId = (int) $row[self::FIELD_FK];
                if (!isset($seenFields[$fieldId])) {
                    $seenFields[$fieldId] = true;
                    $results[] = $row;
                }
            }
        }
        
        if (!$results) {
            return [];
        }

        $values = [];
        foreach ($results as $row) {
            $value = $row['value'];
            $slug = $row['slug'];
            
            if ($value === null) {
                $values[$slug] = null;
                continue;
            }
            
            $decoded = json_decode($value, true);
            
            // If JSON decode failed or returned null, use original value
            // For relation fields, a single integer should be kept as-is (will be converted to array in getRawIds)
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                // Not valid JSON, use as-is
                $finalValue = $value;
            } else {
                // Valid JSON or null
                $finalValue = $decoded !== null ? $decoded : $value;
            }
            
            $values[$slug] = $finalValue;
        }

        return $values;
    }

    /**
     * Find all field values for an entity, including ALL languages for translatable fields.
     * Returns: [slug => value] for non-translatable, [slug => [langId => value]] for translatable
     *
     * @return array<string, mixed>
     */
    public function findByEntityAllLanguages(string $entityType, int $entityId, ?int $shopId = null): array
    {
        $shopId ??= (int) Context::getContext()->shop->id;

        // Get ALL values including all languages
        $sql = new DbQuery();
        $sql->select('fv.value, fv.id_lang, f.slug, f.value_translatable, f.' . self::FIELD_FK . ', fv.' . $this->getPrimaryKey())
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('fv.entity_type = "' . pSQL($entityType) . '"')
            ->where('fv.entity_id = ' . (int) $entityId)
            ->where('fv.id_shop = ' . (int) $shopId)
            ->orderBy('f.slug ASC, fv.id_lang ASC');

        $allResults = $this->db->executeS($sql);

        if (!$allResults) {
            return [];
        }

        $values = [];
        foreach ($allResults as $row) {
            $slug = $row['slug'];
            $value = $row['value'];
            $langId = $row['id_lang'];
            $isTranslatable = (bool) $row['value_translatable'];

            // Decode JSON values
            if ($value !== null) {
                $decoded = json_decode($value, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    $finalValue = $value;
                } else {
                    $finalValue = $decoded !== null ? $decoded : $value;
                }
            } else {
                $finalValue = null;
            }

            if ($isTranslatable && $langId !== null) {
                // Translatable field: group by language
                if (!isset($values[$slug]) || !is_array($values[$slug])) {
                    $values[$slug] = [];
                }
                $values[$slug][(int) $langId] = $finalValue;
            } else {
                // Non-translatable field: simple value
                $values[$slug] = $finalValue;
            }
        }

        return $values;
    }


    public function findByEntityWithMeta(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        // Fallback: get all fields without hook filtering
        $shopId ??= (int) Context::getContext()->shop->id;
        $langId ??= (int) Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('
            fv.id_wepresta_acf_field_value,
            fv.id_wepresta_acf_field,
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
        ');
        $sql->from('wepresta_acf_field_value', 'fv');
        $sql->innerJoin('wepresta_acf_field', 'f', 'fv.id_wepresta_acf_field = f.id_wepresta_acf_field');
        $sql->innerJoin('wepresta_acf_group', 'g', 'f.id_wepresta_acf_group = g.id_wepresta_acf_group');
        
        // Filter by entity
        $sql->where('fv.entity_type = "' . pSQL($entityType) . '"');
        $sql->where('fv.entity_id = ' . (int) $entityId);
        $sql->where('fv.id_shop = ' . (int) $shopId);
        $sql->where('(fv.id_lang = ' . (int) $langId . ' OR fv.id_lang IS NULL)');
        
        // Only active fields and groups
        $sql->where('f.active = 1');
        $sql->where('g.active = 1');
        
        $sql->orderBy('f.position ASC');

        $results = $this->db->executeS($sql);
        
        if (!$results) {
            return [];
        }

        $processedResults = [];
        foreach ($results as $row) {
            // Decode JSON fields
            $row['field_config'] = json_decode($row['field_config'] ?: '[]', true);
            $row['field_validation'] = json_decode($row['field_validation'] ?: '[]', true);
            $row['field_conditions'] = json_decode($row['field_conditions'] ?: '[]', true);
            $row['field_wrapper'] = json_decode($row['field_wrapper'] ?: '{}', true);
            $row['group_bo_options'] = json_decode($row['group_bo_options'] ?: '{}', true);
            
            $processedResults[] = $row;
        }

        return $processedResults;
    }

    public function findByFieldAndEntity(int $fieldId, string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): ?string
    {
        $shopId ??= (int) Context::getContext()->shop->id;
        $langId ??= (int) Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('value')
            ->from($this->getTableName())
            ->where(self::FIELD_FK . ' = ' . (int) $fieldId)
            ->where('entity_type = "' . pSQL($entityType) . '"')
            ->where('entity_id = ' . (int) $entityId)
            ->where('id_shop = ' . (int) $shopId)
            ->where('(id_lang = ' . (int) $langId . ' OR id_lang IS NULL)');

        $result = $this->db->getValue($sql);

        return $result ?: null;
    }

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
        $shopId ??= (int) Context::getContext()->shop->id;

        if ($isTranslatable === null) {
            $fieldRepo = new AcfFieldRepository();
            $field = $fieldRepo->findById($fieldId);
            $isTranslatable = $field && (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false);
        }

        $effectiveLangId = $isTranslatable ? ($langId ?? (int) Context::getContext()->language->id) : null;
        if ($indexValue === null && $value !== null) {
            $indexValue = substr($value, 0, 255);
        }

        $now = date('Y-m-d H:i:s');

        // MySQL NULL != NULL workaround for non-translatable fields
        if ($effectiveLangId === null) {
            $deleteWhere = self::FIELD_FK . ' = ' . (int) $fieldId
                . ' AND entity_type = "' . pSQL($entityType) . '"'
                . ' AND entity_id = ' . (int) $entityId
                . ' AND id_shop = ' . (int) $shopId
                . ' AND id_lang IS NULL';
            $this->db->delete($this->getTableName(), $deleteWhere);

            $insert = [
                self::FIELD_FK => (int) $fieldId,
                'entity_type' => pSQL($entityType),
                'entity_id' => (int) $entityId,
                'id_shop' => (int) $shopId,
                'value' => $value !== null ? pSQL($value) : null,
                'value_index' => $indexValue !== null ? pSQL(substr($indexValue, 0, 255)) : null,
                'date_add' => $now,
                'date_upd' => $now,
            ];

            return $this->db->insert($this->getTableName(), $insert);
        }

        // For translatable fields, use upsert
        $valueSql = $value !== null ? "'" . pSQL($value) . "'" : 'NULL';
        $valueIndexSql = $indexValue !== null ? "'" . pSQL($indexValue) . "'" : 'NULL';

        $sql = 'INSERT INTO `' . $this->dbPrefix . $this->getTableName() . "`
                (`" . self::FIELD_FK . "`, `entity_type`, `entity_id`, `id_shop`, `id_lang`, `value`, `value_index`, `date_add`, `date_upd`)
                VALUES (" . (int) $fieldId . ', "' . pSQL($entityType) . '", ' . (int) $entityId . ', ' . (int) $shopId . ', ' . (int) $effectiveLangId . ', ' . $valueSql . ', ' . $valueIndexSql . ", '" . $now . "', '" . $now . "')
                ON DUPLICATE KEY UPDATE `value` = " . $valueSql . ', `value_index` = ' . $valueIndexSql . ", `date_upd` = '" . $now . "'";

        return $this->db->execute($sql);
    }

    public function deleteByEntity(string $entityType, int $entityId, ?int $shopId = null): bool
    {
        $where = 'entity_type = "' . pSQL($entityType) . '" AND entity_id = ' . (int) $entityId;
        if ($shopId !== null) {
            $where .= ' AND id_shop = ' . (int) $shopId;
        }

        return $this->db->delete($this->getTableName(), $where);
    }

    public function deleteByFieldAndEntity(int $fieldId, string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): bool
    {
        $where = self::FIELD_FK . ' = ' . (int) $fieldId
            . ' AND entity_type = "' . pSQL($entityType) . '"'
            . ' AND entity_id = ' . (int) $entityId;
        if ($shopId !== null) {
            $where .= ' AND id_shop = ' . (int) $shopId;
        }
        if ($langId !== null) {
            $where .= ' AND id_lang = ' . (int) $langId;
        }

        return $this->db->delete($this->getTableName(), $where);
    }

    /** @return array<int> */
    public function findEntitiesByFieldValue(int $fieldId, string $value, string $entityType, ?int $shopId = null): array
    {
        $sql = new DbQuery();
        $sql->select('DISTINCT entity_id')
            ->from($this->getTableName())
            ->where(self::FIELD_FK . ' = ' . (int) $fieldId)
            ->where('entity_type = "' . pSQL($entityType) . '"')
            ->where('value_index = "' . pSQL($value) . '"');

        if ($shopId !== null) {
            $sql->where('id_shop = ' . (int) $shopId);
        }

        $results = $this->db->executeS($sql);
        return $results ? array_map(fn($r) => (int) $r['entity_id'], $results) : [];
    }

    /**
     * Find all field values for all fields in a group.
     * Used for export functionality.
     */
    public function findAllByGroup(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('fv.*')
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('f.id_wepresta_acf_group = ' . (int) $groupId)
            ->orderBy('fv.' . $this->getPrimaryKey() . ' ASC');

        $results = $this->db->executeS($sql);
        return $results ?: [];
    }
}
