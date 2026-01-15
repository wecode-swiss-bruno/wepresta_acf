<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use WeprestaAcf\Domain\Entity\CptTerm;
use WeprestaAcf\Domain\Repository\CptTermRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTermRepository extends AbstractRepository implements CptTermRepositoryInterface
{
    protected function getTableName(): string
    {
        return 'wepresta_acf_cpt_term';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_cpt_term';
    }

    public function find(int $id, ?int $langId = null): ?CptTerm
    {
        $row = $this->findOneBy([$this->getPrimaryKey() => $id]);
        if (!$row) {
            return null;
        }
        $term = new CptTerm($row);
        if ($langId !== null) {
            $term->setTranslations($this->getTranslations($id, $langId));
        }
        return $term;
    }

    public function findBySlug(string $slug, int $taxonomyId, ?int $langId = null): ?CptTerm
    {
        $row = $this->findOneBy(['slug' => $slug, 'id_wepresta_acf_cpt_taxonomy' => $taxonomyId]);
        if (!$row) {
            return null;
        }
        $term = new CptTerm($row);
        if ($langId !== null) {
            $term->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_term'], $langId));
        }
        return $term;
    }

    public function findByTaxonomy(int $taxonomyId, ?int $langId = null): array
    {
        $rows = $this->findBy(['id_wepresta_acf_cpt_taxonomy' => $taxonomyId], 'position ASC');
        return array_map(function ($row) use ($langId) {
            $term = new CptTerm($row);
            if ($langId !== null) {
                $term->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_term'], $langId));
            }
            return $term;
        }, $rows);
    }

    public function findTopLevel(int $taxonomyId, ?int $langId = null): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_term 
                WHERE id_wepresta_acf_cpt_taxonomy = ' . (int) $taxonomyId . ' AND id_parent IS NULL ORDER BY position ASC';
        $rows = \Db::getInstance()->executeS($sql) ?: [];
        return array_map(function ($row) use ($langId) {
            $term = new CptTerm($row);
            if ($langId !== null) {
                $term->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_term'], $langId));
            }
            return $term;
        }, $rows);
    }

    public function findChildren(int $parentId, ?int $langId = null): array
    {
        $rows = $this->findBy(['id_parent' => $parentId], 'position ASC');
        return array_map(function ($row) use ($langId) {
            $term = new CptTerm($row);
            if ($langId !== null) {
                $term->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_term'], $langId));
            }
            return $term;
        }, $rows);
    }

    public function getTree(int $taxonomyId, ?int $langId = null): array
    {
        $topLevelTerms = $this->findTopLevel($taxonomyId, null);
        foreach ($topLevelTerms as $term) {
            $this->loadChildren($term, null);
        }
        return $topLevelTerms;
    }

    public function save(CptTerm $term): int
    {
        $data = $term->toArray();
        $id = $data['id_wepresta_acf_cpt_term'];
        unset($data['id_wepresta_acf_cpt_term']);

        if ($id) {
            $data['date_upd'] = date('Y-m-d H:i:s');
            $this->update($id, $data);
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');
            $data['date_upd'] = date('Y-m-d H:i:s');
            $id = $this->insert($data);
            $term->setId($id);
        }

        $this->saveTranslations($id, $term->getTranslations());
        return $id;
    }

    public function delete(int $id): bool
    {
        return $this->deleteBy([$this->getPrimaryKey() => $id]) > 0;
    }

    public function countPosts(int $termId): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_term WHERE id_wepresta_acf_cpt_term = ' . (int) $termId;
        return (int) \Db::getInstance()->getValue($sql);
    }

    public function slugExists(string $slug, int $taxonomyId, ?int $excludeId = null): bool
    {
        $row = $this->findOneBy(['slug' => $slug, 'id_wepresta_acf_cpt_taxonomy' => $taxonomyId]);
        if (!$row) {
            return false;
        }
        if ($excludeId && (int) $row['id_wepresta_acf_cpt_term'] === $excludeId) {
            return false;
        }
        return true;
    }

    private function loadChildren(CptTerm $term, ?int $langId = null): void
    {
        if ($term->getId() === null) {
            return;
        }
        // Always load all translations
        $children = $this->findChildren($term->getId(), null);
        $term->setChildren($children);
        foreach ($children as $child) {
            $this->loadChildren($child, null);
        }
    }

    private function getTranslations(int $id, ?int $langId = null): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_term_lang 
                WHERE id_wepresta_acf_cpt_term = ' . (int) $id;

        if ($langId !== null) {
            $sql .= ' AND id_lang = ' . (int) $langId;
            $row = \Db::getInstance()->getRow($sql);
            return $row ? [$langId => $row] : [];
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
            if (empty($trans['name'])) {
                continue;
            }
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_term_lang 
                    (id_wepresta_acf_cpt_term, id_lang, name, description) 
                    VALUES (' . (int) $id . ', ' . (int) $langId . ', "' . pSQL($trans['name']) . '", "' . pSQL($trans['description'] ?? '') . '")
                    ON DUPLICATE KEY UPDATE name = "' . pSQL($trans['name']) . '", description = "' . pSQL($trans['description'] ?? '') . '"';
            \Db::getInstance()->execute($sql);
        }
    }
}
