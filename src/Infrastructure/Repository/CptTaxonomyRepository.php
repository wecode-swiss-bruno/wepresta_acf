<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use WeprestaAcf\Domain\Entity\CptTaxonomy;
use WeprestaAcf\Domain\Repository\CptTaxonomyRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTaxonomyRepository extends AbstractRepository implements CptTaxonomyRepositoryInterface
{
    protected function getTableName(): string
    {
        return 'wepresta_acf_cpt_taxonomy';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_cpt_taxonomy';
    }

    public function find(int $id, ?int $langId = null): ?CptTaxonomy
    {
        $row = $this->findOneBy([$this->getPrimaryKey() => $id]);
        if (!$row) {
            return null;
        }
        $taxonomy = new CptTaxonomy($row);
        if ($langId !== null) {
            $taxonomy->setTranslations($this->getTranslations($id, $langId));
        }
        return $taxonomy;
    }

    public function findBySlug(string $slug, ?int $langId = null): ?CptTaxonomy
    {
        $row = $this->findOneBy(['slug' => $slug]);
        if (!$row) {
            return null;
        }
        $taxonomy = new CptTaxonomy($row);
        if ($langId !== null) {
            $taxonomy->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_taxonomy'], $langId));
        }
        return $taxonomy;
    }

    public function findActive(?int $langId = null): array
    {
        $rows = $this->findBy(['active' => 1]);
        return array_map(function ($row) use ($langId) {
            $taxonomy = new CptTaxonomy($row);
            if ($langId !== null) {
                $taxonomy->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_taxonomy'], $langId));
            }
            return $taxonomy;
        }, $rows);
    }

    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $rows = $this->findBy([], null, $limit);
        return array_map(function ($row) {
            $taxonomy = new CptTaxonomy($row);
            // Load all translations for each taxonomy
            $taxonomy->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_taxonomy']));
            return $taxonomy;
        }, $rows);
    }

    public function findByType(int $typeId, ?int $langId = null): array
    {
        $sql = 'SELECT t.* FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_taxonomy t
                INNER JOIN ' . _DB_PREFIX_ . 'wepresta_acf_cpt_type_taxonomy tt ON t.id_wepresta_acf_cpt_taxonomy = tt.id_wepresta_acf_cpt_taxonomy
                WHERE tt.id_wepresta_acf_cpt_type = ' . (int) $typeId;
        $rows = \Db::getInstance()->executeS($sql) ?: [];
        return array_map(function ($row) use ($langId) {
            $taxonomy = new CptTaxonomy($row);
            if ($langId !== null) {
                $taxonomy->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_taxonomy'], $langId));
            }
            return $taxonomy;
        }, $rows);
    }

    public function findWithTerms(int $id, ?int $langId = null): ?CptTaxonomy
    {
        $row = $this->findOneBy([$this->getPrimaryKey() => $id]);
        if (!$row) {
            return null;
        }
        $taxonomy = new CptTaxonomy($row);
        
        // Always load all translations for editor
        $taxonomy->setTranslations($this->getTranslations($id));
        
        // Load terms with translations
        $terms = $this->getTermsWithTranslations($id);
        $taxonomy->setTerms($terms);
        
        return $taxonomy;
    }

    public function save(CptTaxonomy $taxonomy): int
    {
        $data = $taxonomy->toArray();
        $id = $data['id_wepresta_acf_cpt_taxonomy'];
        unset($data['id_wepresta_acf_cpt_taxonomy']);

        if ($id) {
            $data['date_upd'] = date('Y-m-d H:i:s');
            $this->update($id, $data);
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');
            $data['date_upd'] = date('Y-m-d H:i:s');
            $id = $this->insert($data);
            $taxonomy->setId($id);
        }

        $this->saveTranslations($id, $taxonomy->getTranslations());
        return $id;
    }

    public function delete(int $id): bool
    {
        return $this->deleteBy([$this->getPrimaryKey() => $id]) > 0;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $row = $this->findOneBy(['slug' => $slug]);
        if (!$row) {
            return false;
        }
        if ($excludeId && (int) $row['id_wepresta_acf_cpt_taxonomy'] === $excludeId) {
            return false;
        }
        return true;
    }

    private function getTranslations(int $id, ?int $langId = null): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_taxonomy_lang 
                WHERE id_wepresta_acf_cpt_taxonomy = ' . (int) $id;

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
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_taxonomy_lang 
                    (id_wepresta_acf_cpt_taxonomy, id_lang, name, description) 
                    VALUES (' . (int) $id . ', ' . (int) $langId . ', "' . pSQL($trans['name']) . '", "' . pSQL($trans['description'] ?? '') . '")
                    ON DUPLICATE KEY UPDATE name = "' . pSQL($trans['name']) . '", description = "' . pSQL($trans['description'] ?? '') . '"';
            \Db::getInstance()->execute($sql);
        }
    }

    private function getTerms(int $taxonomyId): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_term 
                WHERE id_wepresta_acf_cpt_taxonomy = ' . (int) $taxonomyId . ' ORDER BY position ASC';
        return \Db::getInstance()->executeS($sql) ?: [];
    }

    private function getTermsWithTranslations(int $taxonomyId): array
    {
        $terms = $this->getTerms($taxonomyId);
        return array_map(function ($row) {
            $term = new \WeprestaAcf\Domain\Entity\CptTerm($row);
            // Load all translations
            $term->setTranslations($this->getTermTranslations((int) $row['id_wepresta_acf_cpt_term']));
            return $term;
        }, $terms);
    }

    private function getTermTranslations(int $termId): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_term_lang 
                WHERE id_wepresta_acf_cpt_term = ' . (int) $termId;
        $rows = \Db::getInstance()->executeS($sql);
        $translations = [];
        if ($rows) {
            foreach ($rows as $row) {
                $translations[$row['id_lang']] = $row;
            }
        }
        return $translations;
    }
}
