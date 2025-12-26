<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use Db;
use DbQuery;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

final class AcfGroupRepository implements AcfGroupRepositoryInterface
{
    private const TABLE = 'wepresta_acf_group';
    private const TABLE_SHOP = 'wepresta_acf_group_shop';
    private const PK = 'id_wepresta_acf_group';

    public function findById(int $id): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)->where(self::PK . ' = ' . (int) $id);
        $result = Db::getInstance()->getRow($sql);
        return $result ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)->where("slug = '" . pSQL($slug) . "'");
        $result = Db::getInstance()->getRow($sql);
        return $result ?: null;
    }

    public function findActiveGroups(?int $shopId = null): array
    {
        $sql = new DbQuery();
        $sql->select('g.*')->from(self::TABLE, 'g')->where('g.active = 1')->orderBy('g.priority ASC');
        if ($shopId !== null) {
            $sql->innerJoin(self::TABLE_SHOP, 'gs', 'g.' . self::PK . ' = gs.' . self::PK . ' AND gs.id_shop = ' . (int) $shopId);
        }
        $result = Db::getInstance()->executeS($sql);
        return $result ?: [];
    }

    public function findAll(): array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)->orderBy('priority ASC');
        $result = Db::getInstance()->executeS($sql);
        return $result ?: [];
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'uuid' => pSQL($data['uuid'] ?? ''),
            'title' => pSQL($data['title'] ?? ''),
            'slug' => pSQL($data['slug'] ?? ''),
            'description' => pSQL($data['description'] ?? ''),
            'location_rules' => pSQL(json_encode($data['locationRules'] ?? [], JSON_THROW_ON_ERROR)),
            'placement_tab' => pSQL($data['placementTab'] ?? 'description'),
            'placement_position' => pSQL($data['placementPosition'] ?? ''),
            'priority' => (int) ($data['priority'] ?? 10),
            'bo_options' => pSQL(json_encode($data['boOptions'] ?? [], JSON_THROW_ON_ERROR)),
            'fo_options' => pSQL(json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR)),
            'active' => (int) ($data['active'] ?? 1),
            'date_add' => $now,
            'date_upd' => $now,
        ];
        Db::getInstance()->insert(self::TABLE, $insert);
        return (int) Db::getInstance()->Insert_ID();
    }

    public function update(int $id, array $data): bool
    {
        $update = [
            'title' => pSQL($data['title'] ?? ''),
            'slug' => pSQL($data['slug'] ?? ''),
            'description' => pSQL($data['description'] ?? ''),
            'location_rules' => pSQL(json_encode($data['locationRules'] ?? [], JSON_THROW_ON_ERROR)),
            'placement_tab' => pSQL($data['placementTab'] ?? 'description'),
            'placement_position' => pSQL($data['placementPosition'] ?? ''),
            'priority' => (int) ($data['priority'] ?? 10),
            'bo_options' => pSQL(json_encode($data['boOptions'] ?? [], JSON_THROW_ON_ERROR)),
            'fo_options' => pSQL(json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR)),
            'active' => (int) ($data['active'] ?? 1),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        return Db::getInstance()->update(self::TABLE, $update, self::PK . ' = ' . (int) $id);
    }

    public function delete(int $id): bool
    {
        return Db::getInstance()->delete(self::TABLE, self::PK . ' = ' . (int) $id);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')->from(self::TABLE)->where("slug = '" . pSQL($slug) . "'");
        if ($excludeId !== null) {
            $sql->where(self::PK . ' != ' . (int) $excludeId);
        }
        return (int) Db::getInstance()->getValue($sql) > 0;
    }
}

