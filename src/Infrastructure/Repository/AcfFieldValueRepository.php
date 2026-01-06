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

    protected function getTableName(): string
    {
        return 'wepresta_acf_field_value';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_field_value';
    }

    public function findByProduct(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        // Use generic method for backward compatibility
        return $this->findByEntity('product', $productId, $shopId, $langId);
    }

    public function findByProductWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        // Use generic method for backward compatibility
        return $this->findByEntityWithMeta('product', $productId, $shopId, $langId);
    }

    public function findByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): ?string
    {
        // Use generic method for backward compatibility
        return $this->findByFieldAndEntity($fieldId, 'product', $productId, $shopId, $langId);
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

    public function findProductsByFieldValue(int $fieldId, string $value, ?int $shopId = null): array
    {
        return $this->findEntitiesByFieldValue($fieldId, $value, 'product', $shopId);
    }

    // =========================================================================
    // NEW GENERIC ENTITY METHODS
    // =========================================================================

    public function findByEntity(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        $shopId ??= (int) Context::getContext()->shop->id;
        $langId ??= (int) Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.' . self::FIELD_FK)
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('fv.entity_type = "' . pSQL($entityType) . '"')
            ->where('fv.entity_id = ' . (int) $entityId)
            ->where('fv.id_shop = ' . (int) $shopId)
            ->where('(fv.id_lang = ' . (int) $langId . ' OR fv.id_lang IS NULL)')
            ->where('fv.' . $this->getPrimaryKey() . ' = (
                SELECT MAX(fv2.' . $this->getPrimaryKey() . ') FROM `' . $this->dbPrefix . $this->getTableName() . '` fv2
                WHERE fv2.' . self::FIELD_FK . ' = fv.' . self::FIELD_FK . '
                AND fv2.entity_type = fv.entity_type AND fv2.entity_id = fv.entity_id AND fv2.id_shop = fv.id_shop
                AND (fv2.id_lang = fv.id_lang OR (fv2.id_lang IS NULL AND fv.id_lang IS NULL))
            )');

        $results = $this->db->executeS($sql);
        if (!$results) {
            return [];
        }

        $values = [];
        foreach ($results as $row) {
            $value = $row['value'];
            if ($value === null) {
                $values[$row['slug']] = null;
                continue;
            }
            $decoded = json_decode($value, true);
            $values[$row['slug']] = $decoded !== null ? $decoded : $value;
        }

        return $values;
    }

    public function findByEntityWithMeta(string $entityType, int $entityId, ?int $shopId = null, ?int $langId = null): array
    {
        $shopId ??= (int) Context::getContext()->shop->id;
        $langId ??= (int) Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.title, f.type, f.instructions, f.config, f.fo_options, f.wrapper, f.position, f.' . self::FIELD_FK)
            ->from($this->getTableName(), 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('fv.entity_type = "' . pSQL($entityType) . '"')
            ->where('fv.entity_id = ' . (int) $entityId)
            ->where('fv.id_shop = ' . (int) $shopId)
            ->where('f.active = 1')
            ->where('(fv.id_lang = ' . (int) $langId . ' OR fv.id_lang IS NULL)')
            ->where('fv.' . $this->getPrimaryKey() . ' = (
                SELECT MAX(fv2.' . $this->getPrimaryKey() . ') FROM `' . $this->dbPrefix . $this->getTableName() . '` fv2
                WHERE fv2.' . self::FIELD_FK . ' = fv.' . self::FIELD_FK . '
                AND fv2.entity_type = fv.entity_type AND fv2.entity_id = fv.entity_id AND fv2.id_shop = fv.id_shop
                AND (fv2.id_lang = fv.id_lang OR (fv2.id_lang IS NULL AND fv.id_lang IS NULL))
            )')
            ->orderBy('f.position ASC');

        $results = $this->db->executeS($sql);
        if (!$results) {
            return [];
        }

        $fields = [];
        foreach ($results as $row) {
            $value = $row['value'];
            $decodedValue = $value === null ? null : (json_decode($value, true) ?? $value);
            $fields[] = [
                'slug' => $row['slug'],
                'title' => $row['title'],
                'type' => $row['type'],
                'value' => $decodedValue,
                'instructions' => $row['instructions'] ?: null,
                'config' => json_decode($row['config'] ?? '{}', true) ?? [],
                'fo_options' => json_decode($row['fo_options'] ?? '{}', true) ?? [],
                'wrapper' => json_decode($row['wrapper'] ?? '{}', true) ?? [],
            ];
        }

        return $fields;
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
            $isTranslatable = $field && (bool) ($field['translatable'] ?? false);
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
            ->where("value_index = '" . pSQL($value) . "'");

        if ($shopId !== null) {
            $sql->where('id_shop = ' . (int) $shopId);
        }

        $results = $this->db->executeS($sql);

        return $results ? array_column($results, 'entity_id') : [];
    }
}
