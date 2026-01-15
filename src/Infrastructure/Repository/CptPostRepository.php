<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use WeprestaAcf\Domain\Entity\CptPost;
use WeprestaAcf\Domain\Repository\CptPostRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptPostRepository extends AbstractRepository implements CptPostRepositoryInterface
{
    protected function getTableName(): string
    {
        return 'wepresta_acf_cpt_post';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_cpt_post';
    }

    public function find(int $id, ?int $langId = null, ?int $shopId = null): ?CptPost
    {
        $row = $this->findOneBy([$this->getPrimaryKey() => $id]);
        if (!$row) {
            return null;
        }
        return $this->hydratePost($row, $langId);
    }

    public function findBySlug(string $slug, int $typeId, ?int $langId = null, ?int $shopId = null): ?CptPost
    {
        $row = $this->findOneBy(['slug' => $slug, 'id_wepresta_acf_cpt_type' => $typeId]);
        if (!$row) {
            return null;
        }
        return $this->hydratePost($row, $langId);
    }

    public function findByType(int $typeId, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array
    {
        $rows = $this->findBy(['id_wepresta_acf_cpt_type' => $typeId], 'date_upd DESC', $limit, $offset);
        return array_map(fn($row) => $this->hydratePost($row, $langId), $rows);
    }

    public function findPublishedByType(int $typeId, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array
    {
        $rows = $this->findBy(['id_wepresta_acf_cpt_type' => $typeId, 'status' => CptPost::STATUS_PUBLISHED], 'date_upd DESC', $limit, $offset);
        return array_map(fn($row) => $this->hydratePost($row, $langId), $rows);
    }

    public function findByTerm(int $termId, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT p.* FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post p
                INNER JOIN ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_term pt ON p.id_wepresta_acf_cpt_post = pt.id_wepresta_acf_cpt_post
                WHERE pt.id_wepresta_acf_cpt_term = ' . (int) $termId . ' ORDER BY p.date_upd DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $rows = \Db::getInstance()->executeS($sql) ?: [];
        return array_map(fn($row) => $this->hydratePost($row, $langId), $rows);
    }

    public function findByTerms(array $termIds, ?int $langId = null, ?int $shopId = null, int $limit = 100, int $offset = 0): array
    {
        if (empty($termIds)) {
            return [];
        }
        $termIdsStr = implode(',', array_map('intval', $termIds));
        $sql = 'SELECT p.* FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post p
                WHERE p.id_wepresta_acf_cpt_post IN (
                    SELECT pt.id_wepresta_acf_cpt_post FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_term pt
                    WHERE pt.id_wepresta_acf_cpt_term IN (' . $termIdsStr . ')
                    GROUP BY pt.id_wepresta_acf_cpt_post
                    HAVING COUNT(DISTINCT pt.id_wepresta_acf_cpt_term) = ' . count($termIds) . '
                ) ORDER BY p.date_upd DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $rows = \Db::getInstance()->executeS($sql) ?: [];
        return array_map(fn($row) => $this->hydratePost($row, $langId), $rows);
    }

    public function countByType(int $typeId, ?int $shopId = null, ?string $status = null): int
    {
        $criteria = ['id_wepresta_acf_cpt_type' => $typeId];
        if ($status !== null) {
            $criteria['status'] = $status;
        }
        return $this->count($criteria);
    }

    public function countByTerm(int $termId, ?int $shopId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_term WHERE id_wepresta_acf_cpt_term = ' . (int) $termId;
        return (int) \Db::getInstance()->getValue($sql);
    }

    public function save(CptPost $post, ?int $shopId = null): int
    {
        $data = $post->toArray();
        $id = $data['id_wepresta_acf_cpt_post'];
        unset($data['id_wepresta_acf_cpt_post']);

        if ($id) {
            $data['date_upd'] = date('Y-m-d H:i:s');
            $this->update($id, $data);
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');
            $data['date_upd'] = date('Y-m-d H:i:s');
            $id = $this->insert($data);
            $post->setId($id);
        }

        $this->saveTranslations($id, $post->getTranslations());

        if ($shopId !== null) {
            $this->attachToShop($id, $shopId);
        }

        return $id;
    }

    public function delete(int $id): bool
    {
        return $this->deleteBy([$this->getPrimaryKey() => $id]) > 0;
    }

    public function attachTerm(int $postId, int $termId): bool
    {
        return $this->attachTo('wepresta_acf_cpt_post_term', 'id_wepresta_acf_cpt_term', $postId, $termId);
    }

    public function detachTerm(int $postId, int $termId): bool
    {
        return $this->detachFrom('wepresta_acf_cpt_post_term', 'id_wepresta_acf_cpt_term', $postId, $termId);
    }

    public function syncTerms(int $postId, array $termIds): void
    {
        $this->syncAttached('wepresta_acf_cpt_post_term', 'id_wepresta_acf_cpt_term', $postId, $termIds);
    }

    public function findRelated(int $relationId, int $sourcePostId): array
    {
        $sql = 'SELECT p.* FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post p
                INNER JOIN ' . _DB_PREFIX_ . 'wepresta_acf_cpt_relation_data rd ON p.id_wepresta_acf_cpt_post = rd.id_cpt_post_target
                WHERE rd.id_wepresta_acf_cpt_relation = ' . (int) $relationId . ' AND rd.id_cpt_post_source = ' . (int) $sourcePostId . '
                ORDER BY rd.position ASC';
        $rows = \Db::getInstance()->executeS($sql) ?: [];
        return array_map(fn($row) => $this->hydratePost($row), $rows);
    }

    public function attachRelated(int $relationId, int $sourcePostId, int $targetPostId, int $position = 0): bool
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_relation_data 
                (id_wepresta_acf_cpt_relation, id_cpt_post_source, id_cpt_post_target, position) 
                VALUES (' . (int) $relationId . ', ' . (int) $sourcePostId . ', ' . (int) $targetPostId . ', ' . (int) $position . ')
                ON DUPLICATE KEY UPDATE position = ' . (int) $position;
        return \Db::getInstance()->execute($sql);
    }

    public function syncRelated(int $relationId, int $sourcePostId, array $targetPostIds): void
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_relation_data 
                WHERE id_wepresta_acf_cpt_relation = ' . (int) $relationId . ' AND id_cpt_post_source = ' . (int) $sourcePostId;
        \Db::getInstance()->execute($sql);

        foreach ($targetPostIds as $position => $targetPostId) {
            $this->attachRelated($relationId, $sourcePostId, $targetPostId, $position);
        }
    }

    public function slugExists(string $slug, int $typeId, ?int $excludeId = null): bool
    {
        $row = $this->findOneBy(['slug' => $slug, 'id_wepresta_acf_cpt_type' => $typeId]);
        if (!$row) {
            return false;
        }
        if ($excludeId && (int) $row['id_wepresta_acf_cpt_post'] === $excludeId) {
            return false;
        }
        return true;
    }

    private function hydratePost(array $row, ?int $langId = null): CptPost
    {
        $post = new CptPost($row);
        $post->setTranslations($this->getTranslations((int) $row['id_wepresta_acf_cpt_post'], $langId));
        $post->setTerms($this->getAttachedTerms((int) $row['id_wepresta_acf_cpt_post']));
        return $post;
    }

    private function getTranslations(int $id, ?int $langId): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_lang 
                WHERE id_wepresta_acf_cpt_post = ' . (int) $id;

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
            if (empty($trans['title'])) {
                continue;
            }
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_lang 
                    (id_wepresta_acf_cpt_post, id_lang, title, seo_title, seo_description) 
                    VALUES (' . (int) $id . ', ' . (int) $langId . ', "' . pSQL($trans['title']) . '", "' . pSQL($trans['seo_title'] ?? '') . '", "' . pSQL($trans['seo_description'] ?? '') . '")
                    ON DUPLICATE KEY UPDATE title = "' . pSQL($trans['title']) . '", seo_title = "' . pSQL($trans['seo_title'] ?? '') . '", seo_description = "' . pSQL($trans['seo_description'] ?? '') . '"';
            \Db::getInstance()->execute($sql);
        }
    }

    private function getAttachedTerms(int $postId): array
    {
        return $this->getAttachedIds('wepresta_acf_cpt_post_term', 'id_wepresta_acf_cpt_term', $postId);
    }

    private function attachToShop(int $postId, int $shopId): void
    {
        $sql = 'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'wepresta_acf_cpt_post_shop 
                (id_wepresta_acf_cpt_post, id_shop) VALUES (' . (int) $postId . ', ' . (int) $shopId . ')';
        \Db::getInstance()->execute($sql);
    }
}
