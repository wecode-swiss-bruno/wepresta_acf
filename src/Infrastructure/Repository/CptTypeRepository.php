<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use WeprestaAcf\Domain\Entity\CptType;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTypeRepository extends AbstractRepository implements CptTypeRepositoryInterface
{
    protected function getTableName(): string
    {
        return 'wepresta_acf_cpt_type';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_cpt_type';
    }

    public function find(int $id, ?int $langId = null, ?int $shopId = null): ?CptType
    {
        $row = $this->findOneBy([$this->getPrimaryKey() => $id]);
        if (!$row) {
            return null;
        }

        $type = new CptType($row);
        if ($langId !== null) {
            $type->setTranslations($this->getTranslations($id, $langId));
        }
        return $type;
    }

    public function findBySlug(string $slug, ?int $langId = null, ?int $shopId = null): ?CptType
    {
        $row = $this->findOneBy(['slug' => $slug]);
        if (!$row) {
            return null;
        }

        $type = new CptType($row);
        if ($langId !== null) {
            $type->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_type'], $langId));
        }
        return $type;
    }

    public function findActive(?int $langId = null, ?int $shopId = null): array
    {
        $rows = $this->findBy(['active' => 1], 'position ASC');
        return array_map(function ($row) use ($langId) {
            $type = new CptType($row);
            if ($langId !== null) {
                $type->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_type'], $langId));
            }
            return $type;
        }, $rows);
    }

    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $rows = $this->findBy([], 'position ASC', $limit);
        return array_map(function ($row) {
            return new CptType($row);
        }, $rows);
    }

    public function findWithGroups(int $id, ?int $langId = null, ?int $shopId = null): ?CptType
    {
        $type = $this->find($id, $langId, $shopId);
        if (!$type) {
            return null;
        }
        $groups = $this->getAttachedGroups($id);
        $type->setAcfGroups($groups);
        return $type;
    }

    public function findWithTaxonomies(int $id, ?int $langId = null, ?int $shopId = null): ?CptType
    {
        $type = $this->find($id, $langId, $shopId);
        if (!$type) {
            return null;
        }
        $taxonomies = $this->getAttachedTaxonomies($id);
        $type->setTaxonomies($taxonomies);
        return $type;
    }

    public function save(CptType $type, ?int $shopId = null): int
    {
        $data = $type->toArray();
        $id = $data['id_wepresta_acf_cpt_type'];
        unset($data['id_wepresta_acf_cpt_type']);

        if ($id) {
            $this->update($id, $data);
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');
            $data['date_upd'] = date('Y-m-d H:i:s');
            $id = $this->insert($data);
            $type->setId($id);
        }

        $this->saveTranslations($id, $type->getTranslations());

        if ($shopId !== null) {
            $this->attachToShop($id, $shopId);
        }

        return $id;
    }

    public function delete(int $id): bool
    {
        return $this->deleteBy([$this->getPrimaryKey() => $id]) > 0;
    }

    public function attachGroup(int $typeId, int $groupId, int $position = 0): bool
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_group 
                (id_wepresta_acf_cpt_type, id_wepresta_acf_group, position) 
                VALUES (' . (int) $typeId . ', ' . (int) $groupId . ', ' . (int) $position . ')
                ON DUPLICATE KEY UPDATE position = ' . (int) $position;
        return \Db::getInstance()->execute($sql);
    }

    public function detachGroup(int $typeId, int $groupId): bool
    {
        return $this->detachFrom('wepresta_acf_cpt_type_group', 'id_wepresta_acf_group', $typeId, $groupId);
    }

    public function syncGroups(int $typeId, array $groupIds): void
    {
        \Db::getInstance()->delete('wepresta_acf_cpt_type_group', 'id_wepresta_acf_cpt_type = ' . (int) $typeId);
        foreach ($groupIds as $position => $groupId) {
            $this->attachGroup($typeId, $groupId, $position);
        }
    }

    public function attachTaxonomy(int $typeId, int $taxonomyId): bool
    {
        return $this->attachTo('wepresta_acf_cpt_type_taxonomy', 'id_wepresta_acf_cpt_taxonomy', $typeId, $taxonomyId);
    }

    public function detachTaxonomy(int $typeId, int $taxonomyId): bool
    {
        return $this->detachFrom('wepresta_acf_cpt_type_taxonomy', 'id_wepresta_acf_cpt_taxonomy', $typeId, $taxonomyId);
    }

    public function syncTaxonomies(int $typeId, array $taxonomyIds): void
    {
        $this->syncAttached('wepresta_acf_cpt_type_taxonomy', 'id_wepresta_acf_cpt_taxonomy', $typeId, $taxonomyIds);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $result = $this->findOneBy(['slug' => $slug]);
        if (!$result) {
            return false;
        }
        if ($excludeId && (int) $result['id_wepresta_acf_cpt_type'] === $excludeId) {
            return false;
        }
        return true;
    }

    private function getTranslations(int $id, int $langId): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_lang 
                WHERE id_wepresta_acf_cpt_type = ' . (int) $id . ' AND id_lang = ' . (int) $langId;
        return \Db::getInstance()->getRow($sql) ?: [];
    }

    private function saveTranslations(int $id, array $translations): void
    {
        foreach ($translations as $langId => $trans) {
            if (empty($trans['name'])) {
                continue;
            }
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_lang 
                    (id_wepresta_acf_cpt_type, id_lang, name, description) 
                    VALUES (' . (int) $id . ', ' . (int) $langId . ', "' . pSQL($trans['name']) . '", "' . pSQL($trans['description'] ?? '') . '")
                    ON DUPLICATE KEY UPDATE name = "' . pSQL($trans['name']) . '", description = "' . pSQL($trans['description'] ?? '') . '"';
            \Db::getInstance()->execute($sql);
        }
    }

    private function getAttachedGroups(int $typeId): array
    {
        $sql = 'SELECT g.*, tg.position 
                FROM ' . _DB_PREFIX_ . 'wepresta_acf_group g
                INNER JOIN ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_group tg 
                    ON g.id_wepresta_acf_group = tg.id_wepresta_acf_group
                WHERE tg.id_wepresta_acf_cpt_type = ' . (int) $typeId . '
                ORDER BY tg.position ASC';
        return \Db::getInstance()->executeS($sql) ?: [];
    }

    private function getAttachedTaxonomies(int $typeId): array
    {
        $sql = 'SELECT t.* 
                FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_taxonomy t
                INNER JOIN ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_taxonomy tt 
                    ON t.id_wepresta_acf_cpt_taxonomy = tt.id_wepresta_acf_cpt_taxonomy
                WHERE tt.id_wepresta_acf_cpt_type = ' . (int) $typeId;
        return \Db::getInstance()->executeS($sql) ?: [];
    }

    private function attachToShop(int $typeId, int $shopId): void
    {
        $sql = 'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_shop 
                (id_wepresta_acf_cpt_type, id_shop) VALUES (' . (int) $typeId . ', ' . (int) $shopId . ')';
        \Db::getInstance()->execute($sql);
    }
}
