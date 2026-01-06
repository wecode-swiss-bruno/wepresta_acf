<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use DbQuery;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

/**
 * Repository for ACF Groups extending WEDEV Core AbstractRepository.
 */
final class AcfGroupRepository extends AbstractRepository implements AcfGroupRepositoryInterface
{
    private const TABLE_SHOP = 'wepresta_acf_group_shop';

    protected function getTableName(): string
    {
        return 'wepresta_acf_group';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_group';
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findActiveGroups(?int $shopId = null): array
    {
        $sql = new DbQuery();
        $sql->select('g.*')
            ->from($this->getTableName(), 'g')
            ->where('g.active = 1')
            ->orderBy('g.priority ASC');

        if ($shopId !== null) {
            $sql->innerJoin(
                self::TABLE_SHOP,
                'gs',
                'g.' . $this->getPrimaryKey() . ' = gs.' . $this->getPrimaryKey() . ' AND gs.id_shop = ' . (int) $shopId
            );
        }

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * @return array[]
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from($this->getTableName())
            ->orderBy('priority ASC');

        if ($limit !== null) {
            $sql->limit($limit, $offset ?? 0);
        }

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * Create a new group with mapped data.
     */
    public function create(array $data): int
    {
        $mapped = $this->mapDataForInsert($data);

        return $this->insert($mapped);
    }

    /**
     * Update a group with mapped data.
     */
    public function update(int $id, array $data): bool
    {
        $mapped = $this->mapDataForUpdate($data);

        return parent::update($id, $mapped);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')
            ->from($this->getTableName())
            ->where("slug = '" . pSQL($slug) . "'");

        if ($excludeId !== null) {
            $sql->where($this->getPrimaryKey() . ' != ' . (int) $excludeId);
        }

        return (int) $this->db->getValue($sql) > 0;
    }

    /**
     * Associate a group with a shop.
     */
    public function addShopAssociation(int $groupId, int $shopId): bool
    {
        return $this->db->insert(self::TABLE_SHOP, [
            $this->getPrimaryKey() => (int) $groupId,
            'id_shop' => (int) $shopId,
        ], false, true, \Db::ON_DUPLICATE_KEY);
    }

    /**
     * Associate a group with all active shops.
     */
    public function addAllShopAssociations(int $groupId): void
    {
        $shops = \Shop::getShops(true);
        foreach ($shops as $shop) {
            $this->addShopAssociation($groupId, (int) $shop['id_shop']);
        }
    }

    /**
     * Map input data to database columns for insert.
     */
    private function mapDataForInsert(array $data): array
    {
        return [
            'uuid' => $data['uuid'] ?? '',
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? '',
            'description' => $data['description'] ?? '',
            'location_rules' => json_encode($data['locationRules'] ?? [], JSON_THROW_ON_ERROR),
            'placement_tab' => $data['placementTab'] ?? 'description',
            'placement_position' => $data['placementPosition'] ?? '',
            'priority' => (int) ($data['priority'] ?? 10),
            'bo_options' => json_encode($data['boOptions'] ?? [], JSON_THROW_ON_ERROR),
            'fo_options' => json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR),
            'active' => (int) ($data['active'] ?? 1),
        ];
    }

    /**
     * Map input data to database columns for update.
     */
    private function mapDataForUpdate(array $data): array
    {
        return [
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? '',
            'description' => $data['description'] ?? '',
            'location_rules' => json_encode($data['locationRules'] ?? [], JSON_THROW_ON_ERROR),
            'placement_tab' => $data['placementTab'] ?? 'description',
            'placement_position' => $data['placementPosition'] ?? '',
            'priority' => (int) ($data['priority'] ?? 10),
            'bo_options' => json_encode($data['boOptions'] ?? [], JSON_THROW_ON_ERROR),
            'fo_options' => json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR),
            'active' => (int) ($data['active'] ?? 1),
        ];
    }

    /**
     * Save group translations (multilingual metadata: title, description)
     *
     * @param int $groupId
     * @param array $translations Format: ['en' => ['title' => ..., 'description' => ...], 'fr' => [...]]
     *
     * @return bool
     */
    public function saveGroupTranslations(int $groupId, array $translations): bool
    {
        foreach ($translations as $langIdOrCode => $data) {
            // Support both lang ID and lang code
            $langId = is_numeric($langIdOrCode) ? (int) $langIdOrCode : $this->getLangIdByCode((string) $langIdOrCode);

            if ($langId <= 0) {
                continue;
            }

            $this->db->insert(
                'wepresta_acf_group_lang',
                [
                    'id_wepresta_acf_group' => $groupId,
                    'id_lang' => $langId,
                    'title' => pSQL($data['title'] ?? ''),
                    'description' => pSQL($data['description'] ?? ''),
                ],
                false,
                true,
                \Db::REPLACE
            );
        }

        return true;
    }

    /**
     * Get group translations for all languages
     *
     * @param int $groupId
     *
     * @return array Format: ['en' => ['title' => ..., 'description' => ...], 'fr' => [...]]
     */
    public function getGroupTranslations(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('gl.*, l.iso_code')
            ->from('wepresta_acf_group_lang', 'gl')
            ->leftJoin('lang', 'l', 'l.id_lang = gl.id_lang')
            ->where('gl.id_wepresta_acf_group = ' . (int) $groupId);

        $results = $this->db->executeS($sql);

        if (!$results) {
            return [];
        }

        $translations = [];
        foreach ($results as $row) {
            $translations[$row['iso_code']] = [
                'title' => $row['title'],
                'description' => $row['description'],
            ];
        }

        return $translations;
    }

    /**
     * Helper: get lang ID by ISO code
     */
    private function getLangIdByCode(string $code): int
    {
        $sql = new DbQuery();
        $sql->select('id_lang')
            ->from('lang')
            ->where("iso_code = '" . pSQL($code) . "'");

        return (int) $this->db->getValue($sql);
    }
}
