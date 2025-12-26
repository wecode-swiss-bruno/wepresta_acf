<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use Db;
use DbQuery;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;

final class AcfFieldValueRepository implements AcfFieldValueRepositoryInterface
{
    private const TABLE = 'wepresta_acf_field_value';
    private const FIELD_TABLE = 'wepresta_acf_field';
    private const PK = 'id_wepresta_acf_field_value';
    private const FIELD_FK = 'id_wepresta_acf_field';

    public function findByProduct(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        $shopId ??= (int) \Context::getContext()->shop->id;
        $langId ??= (int) \Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.' . self::FIELD_FK)
            ->from(self::TABLE, 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('fv.id_product = ' . (int) $productId)
            ->where('fv.id_shop = ' . (int) $shopId)
            ->where('(fv.id_lang = ' . (int) $langId . ' OR fv.id_lang IS NULL)')
            ->where('fv.' . self::PK . ' = (
                SELECT MAX(fv2.' . self::PK . ') FROM `' . _DB_PREFIX_ . self::TABLE . '` fv2 
                WHERE fv2.' . self::FIELD_FK . ' = fv.' . self::FIELD_FK . ' 
                AND fv2.id_product = fv.id_product AND fv2.id_shop = fv.id_shop 
                AND (fv2.id_lang = fv.id_lang OR (fv2.id_lang IS NULL AND fv.id_lang IS NULL))
            )');

        $results = Db::getInstance()->executeS($sql);
        if (!$results) { return []; }

        $values = [];
        foreach ($results as $row) {
            $value = $row['value'];
            if ($value === null) { $values[$row['slug']] = null; continue; }
            $decoded = json_decode($value, true);
            $values[$row['slug']] = $decoded !== null ? $decoded : $value;
        }
        return $values;
    }

    public function findByProductWithMeta(int $productId, ?int $shopId = null, ?int $langId = null): array
    {
        $shopId ??= (int) \Context::getContext()->shop->id;
        $langId ??= (int) \Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('fv.value, f.slug, f.title, f.type, f.instructions, f.config, f.fo_options, f.position, f.' . self::FIELD_FK)
            ->from(self::TABLE, 'fv')
            ->innerJoin(self::FIELD_TABLE, 'f', 'fv.' . self::FIELD_FK . ' = f.' . self::FIELD_FK)
            ->where('fv.id_product = ' . (int) $productId)
            ->where('fv.id_shop = ' . (int) $shopId)
            ->where('f.active = 1')
            ->where('(fv.id_lang = ' . (int) $langId . ' OR fv.id_lang IS NULL)')
            ->where('fv.' . self::PK . ' = (
                SELECT MAX(fv2.' . self::PK . ') FROM `' . _DB_PREFIX_ . self::TABLE . '` fv2 
                WHERE fv2.' . self::FIELD_FK . ' = fv.' . self::FIELD_FK . ' 
                AND fv2.id_product = fv.id_product AND fv2.id_shop = fv.id_shop 
                AND (fv2.id_lang = fv.id_lang OR (fv2.id_lang IS NULL AND fv.id_lang IS NULL))
            )')
            ->orderBy('f.position ASC');

        $results = Db::getInstance()->executeS($sql);
        if (!$results) { return []; }

        $fields = [];
        foreach ($results as $row) {
            $value = $row['value'];
            $decodedValue = $value === null ? null : (json_decode($value, true) ?? $value);
            $fields[] = [
                'slug' => $row['slug'], 'title' => $row['title'], 'type' => $row['type'],
                'value' => $decodedValue, 'instructions' => $row['instructions'] ?: null,
                'config' => json_decode($row['config'] ?? '{}', true) ?? [],
                'fo_options' => json_decode($row['fo_options'] ?? '{}', true) ?? [],
            ];
        }
        return $fields;
    }

    public function findByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): ?string
    {
        $shopId ??= (int) \Context::getContext()->shop->id;
        $langId ??= (int) \Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('value')->from(self::TABLE)
            ->where(self::FIELD_FK . ' = ' . (int) $fieldId)
            ->where('id_product = ' . (int) $productId)
            ->where('id_shop = ' . (int) $shopId)
            ->where('(id_lang = ' . (int) $langId . ' OR id_lang IS NULL)');
        $result = Db::getInstance()->getValue($sql);
        return $result ?: null;
    }

    public function save(int $fieldId, int $productId, ?string $value, ?int $shopId = null, ?int $langId = null, ?bool $isTranslatable = null, ?string $indexValue = null): bool
    {
        $shopId ??= (int) \Context::getContext()->shop->id;

        if ($isTranslatable === null) {
            $fieldRepo = new AcfFieldRepository();
            $field = $fieldRepo->findById($fieldId);
            $isTranslatable = $field && (bool) ($field['translatable'] ?? false);
        }

        $effectiveLangId = $isTranslatable ? ($langId ?? (int) \Context::getContext()->language->id) : null;
        if ($indexValue === null && $value !== null) { $indexValue = substr($value, 0, 255); }

        $now = date('Y-m-d H:i:s');
        $db = Db::getInstance();

        // MySQL NULL != NULL workaround for non-translatable fields
        if ($effectiveLangId === null) {
            $deleteWhere = self::FIELD_FK . ' = ' . (int) $fieldId
                . ' AND id_product = ' . (int) $productId
                . ' AND id_shop = ' . (int) $shopId
                . ' AND id_lang IS NULL';
            $db->delete(self::TABLE, $deleteWhere);

            $insert = [
                self::FIELD_FK => (int) $fieldId,
                'id_product' => (int) $productId,
                'id_shop' => (int) $shopId,
                'value' => $value !== null ? pSQL($value) : null,
                'value_index' => $indexValue !== null ? pSQL(substr($indexValue, 0, 255)) : null,
                'date_add' => $now,
                'date_upd' => $now,
            ];
            return $db->insert(self::TABLE, $insert);
        }

        // For translatable fields, use upsert
        $valueSql = $value !== null ? "'" . pSQL($value) . "'" : 'NULL';
        $valueIndexSql = $indexValue !== null ? "'" . pSQL($indexValue) . "'" : 'NULL';

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . self::TABLE . "` 
                (`" . self::FIELD_FK . "`, `id_product`, `id_shop`, `id_lang`, `value`, `value_index`, `date_add`, `date_upd`)
                VALUES (" . (int) $fieldId . ', ' . (int) $productId . ', ' . (int) $shopId . ', ' . (int) $effectiveLangId . ', ' . $valueSql . ', ' . $valueIndexSql . ", '" . $now . "', '" . $now . "')
                ON DUPLICATE KEY UPDATE `value` = " . $valueSql . ', `value_index` = ' . $valueIndexSql . ", `date_upd` = '" . $now . "'";
        return $db->execute($sql);
    }

    public function deleteByProduct(int $productId, ?int $shopId = null): bool
    {
        $where = 'id_product = ' . (int) $productId;
        if ($shopId !== null) { $where .= ' AND id_shop = ' . (int) $shopId; }
        return Db::getInstance()->delete(self::TABLE, $where);
    }

    public function deleteByFieldAndProduct(int $fieldId, int $productId, ?int $shopId = null, ?int $langId = null): bool
    {
        $where = self::FIELD_FK . ' = ' . (int) $fieldId . ' AND id_product = ' . (int) $productId;
        if ($shopId !== null) { $where .= ' AND id_shop = ' . (int) $shopId; }
        if ($langId !== null) { $where .= ' AND id_lang = ' . (int) $langId; }
        return Db::getInstance()->delete(self::TABLE, $where);
    }

    public function deleteByField(int $fieldId): bool
    {
        return Db::getInstance()->delete(self::TABLE, self::FIELD_FK . ' = ' . (int) $fieldId);
    }

    public function findProductsByFieldValue(int $fieldId, string $value, ?int $shopId = null): array
    {
        $sql = new DbQuery();
        $sql->select('DISTINCT id_product')->from(self::TABLE)
            ->where(self::FIELD_FK . ' = ' . (int) $fieldId)
            ->where("value_index = '" . pSQL($value) . "'");
        if ($shopId !== null) { $sql->where('id_shop = ' . (int) $shopId); }
        $results = Db::getInstance()->executeS($sql);
        return $results ? array_column($results, 'id_product') : [];
    }
}

