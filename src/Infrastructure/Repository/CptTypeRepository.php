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
        // Always fetch translations, if langId is specific we might filter later or just fetch all
        // Ideally we want all translations if we are in the builder (admin)
        $type->setTranslations($this->getTranslations($id, $langId));
        return $type;
    }

    public function findBySlug(string $slug, ?int $langId = null, ?int $shopId = null): ?CptType
    {
        $row = $this->findOneBy(['slug' => $slug]);
        if (!$row) {
            return null;
        }

        $type = new CptType($row);
        $type->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_type'], $langId));
        return $type;
    }

    public function findActive(?int $langId = null, ?int $shopId = null): array
    {
        $rows = $this->findBy(['active' => 1], 'position ASC');
        return array_map(function ($row) use ($langId) {
            $type = new CptType($row);
            $type->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_type'], $langId));
            return $type;
        }, $rows);
    }

    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $rows = $this->findBy([], 'position ASC', $limit);
        return array_map(function ($row) {
            $type = new CptType($row);
            $type->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_type']));
            return $type;
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

    public function findFull(int $id, ?int $langId = null, ?int $shopId = null): ?CptType
    {
        $type = $this->find($id, $langId, $shopId);
        if (!$type) {
            return null;
        }
        $type->setAcfGroups($this->getAttachedGroups($id));
        $type->setTaxonomies($this->getAttachedTaxonomies($id));
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
            if (is_array($groupId)) {
                $groupId = (int) ($groupId['id_wepresta_acf_group'] ?? $groupId['id'] ?? 0);
            }
            if ($groupId > 0) {
                $this->attachGroup($typeId, (int) $groupId, $position);
            }
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
        $ids = [];
        foreach ($taxonomyIds as $id) {
            if (is_array($id)) {
                $id = (int) ($id['id_wepresta_acf_cpt_taxonomy'] ?? $id['id'] ?? 0);
            }
            if ($id > 0) {
                $ids[] = (int) $id;
            }
        }
        $this->syncAttached('wepresta_acf_cpt_type_taxonomy', 'id_wepresta_acf_cpt_taxonomy', $typeId, $ids);
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

    private function getTranslations(int $id, ?int $langId = null): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_lang 
                WHERE id_wepresta_acf_cpt_type = ' . (int) $id;

        if ($langId !== null) {
            $sql .= ' AND id_lang = ' . (int) $langId;
            $row = \Db::getInstance()->getRow($sql);
            return $row ? [$langId => $row] : []; // Return format compatible with setTranslations expecting [langId => data] or array of rows?
            // Actually setTranslations expects: [langId => ['name' => ..., 'description' => ...]]
            // getRow returns ['name' => ..., 'description' => ...]
            // So if single row, wrap it.
        }

        $rows = \Db::getInstance()->executeS($sql);
        $translations = [];
        if ($rows) {
            foreach ($rows as $row) {
                $translations[$row['id_lang']] = $row;
            }
        }
        return $translations;
    }

    private function saveTranslations(int $id, array $translations): void
    {
        foreach ($translations as $langId => $trans) {
            // Allow saving if either name or description is set
            if (empty($trans['name']) && empty($trans['description'])) {
                continue;
            }

            // Ensure we store plain strings, not JSON or arrays
            $name = $trans['name'] ?? '';
            if (is_array($name)) {
                // If it's an array, take the value for this language or first available
                $name = $name[$langId] ?? (reset($name) ?: '');
            }

            $description = $trans['description'] ?? '';
            if (is_array($description)) {
                $description = $description[$langId] ?? (reset($description) ?: '');
            }

            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_lang 
                    (id_wepresta_acf_cpt_type, id_lang, name, description) 
                    VALUES (' . (int) $id . ', ' . (int) $langId . ', "' . pSQL($name) . '", "' . pSQL($description) . '")
                    ON DUPLICATE KEY UPDATE name = "' . pSQL($name) . '", description = "' . pSQL($description) . '"';
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
