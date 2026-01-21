<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use DbQuery;
use Shop;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

/**
 * Repository for ACF Groups extending WEDEV Core AbstractRepository.
 */
final class AcfGroupRepository extends AbstractRepository implements AcfGroupRepositoryInterface
{
    // =========================================================================
    // Constants
    // =========================================================================

    private const TABLE_SHOP = 'wepresta_acf_group_shop';

    private const TABLE_LANG = 'wepresta_acf_group_lang';

    /** Field configuration: camelCase => [snake_case, transformer, default] */
    private const FIELD_MAP = [
        'uuid' => ['uuid', null, ''],
        'title' => ['title', null, ''],
        'slug' => ['slug', null, ''],
        'description' => ['description', null, ''],
        'locationRules' => ['location_rules', 'json', []],
        'placementTab' => ['placement_tab', null, 'description'],
        'placementPosition' => ['placement_position', null, ''],
        'priority' => ['priority', 'int', 10],
        'boOptions' => ['bo_options', 'json', []],
        'foOptions' => ['fo_options', 'json', []],
        'active' => ['active', 'int', 1],
    ];

    // =========================================================================
    // CRUD Operations
    // =========================================================================

    public function create(array $data): int
    {
        return $this->insert($this->mapDataForInsert($data));
    }

    public function update(int $id, array $data): bool
    {
        return parent::update($id, $this->mapDataForUpdate($data));
    }

    // =========================================================================
    // Query Methods
    // =========================================================================

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

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = new DbQuery();
        $sql->select('1')
            ->from($this->getTableName())
            ->where("slug = '" . pSQL($slug) . "'");

        if ($excludeId !== null) {
            $sql->where($this->getPrimaryKey() . ' != ' . (int) $excludeId);
        }

        return (bool) $this->db->getValue($sql);
    }

    // =========================================================================
    // Shop Associations
    // =========================================================================

    public function getShopIds(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('id_shop')
            ->from(self::TABLE_SHOP)
            ->where($this->getPrimaryKey() . ' = ' . (int) $groupId);

        $results = $this->db->executeS($sql);

        return $results
            ? array_column($results, 'id_shop')
            : [];
    }

    public function addShopAssociation(int $groupId, int $shopId): bool
    {
        return $this->db->insert(self::TABLE_SHOP, [
            $this->getPrimaryKey() => $groupId,
            'id_shop' => $shopId,
        ], false, true, Db::ON_DUPLICATE_KEY);
    }

    public function removeAllShopAssociations(int $groupId): bool
    {
        return $this->db->delete(
            self::TABLE_SHOP,
            $this->getPrimaryKey() . ' = ' . (int) $groupId
        );
    }

    public function addAllShopAssociations(int $groupId): void
    {
        foreach (Shop::getShops(true) as $shop) {
            $this->addShopAssociation($groupId, (int) $shop['id_shop']);
        }
    }

    // =========================================================================
    // Translations
    // =========================================================================

    public function saveGroupTranslations(int $groupId, array $translations): bool
    {
        foreach ($translations as $langIdOrCode => $data) {
            $langId = is_numeric($langIdOrCode)
                ? (int) $langIdOrCode
                : $this->getLangIdByCode((string) $langIdOrCode);

            if ($langId <= 0) {
                continue;
            }

            $this->db->insert(
                self::TABLE_LANG,
                [
                    $this->getPrimaryKey() => $groupId,
                    'id_lang' => $langId,
                    'title' => pSQL($data['title'] ?? ''),
                    'description' => pSQL($data['description'] ?? ''),
                ],
                false,
                true,
                Db::REPLACE
            );
        }

        return true;
    }

    public function getGroupTranslations(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('gl.title, gl.description, l.iso_code')
            ->from(self::TABLE_LANG, 'gl')
            ->leftJoin('lang', 'l', 'l.id_lang = gl.id_lang')
            ->where('gl.' . $this->getPrimaryKey() . ' = ' . (int) $groupId);

        $results = $this->db->executeS($sql);

        if (! $results) {
            return [];
        }

        return array_combine(
            array_column($results, 'iso_code'),
            array_map(static fn (array $row): array => [
                'title' => $row['title'],
                'description' => $row['description'],
            ], $results)
        );
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    protected function getTableName(): string
    {
        return 'wepresta_acf_group';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_group';
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function mapDataForInsert(array $data): array
    {
        return $this->mapData($data, array_keys(self::FIELD_MAP));
    }

    private function mapDataForUpdate(array $data): array
    {
        $fields = array_diff(array_keys(self::FIELD_MAP), ['uuid', 'slug']);
        $mapped = $this->mapData($data, $fields);

        if (isset($data['slug'])) {
            $mapped['slug'] = $data['slug'];
        }

        return $mapped;
    }

    private function mapData(array $data, array $fields): array
    {
        $mapped = [];

        foreach ($fields as $source) {
            [$target, $transformer, $default] = self::FIELD_MAP[$source];
            $value = $data[$source] ?? $default;

            $mapped[$target] = match ($transformer) {
                'json' => json_encode($value, JSON_THROW_ON_ERROR),
                'int' => (int) $value,
                default => $value,
            };
        }

        return $mapped;
    }

    private function getLangIdByCode(string $code): int
    {
        $sql = new DbQuery();
        $sql->select('id_lang')
            ->from('lang')
            ->where("iso_code = '" . pSQL($code) . "'");

        return (int) $this->db->getValue($sql);
    }
}
