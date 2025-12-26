<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use Db;
use DbQuery;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;

final class AcfFieldRepository implements AcfFieldRepositoryInterface
{
    private const TABLE = 'wepresta_acf_field';
    private const PK = 'id_wepresta_acf_field';
    private const GROUP_FK = 'id_wepresta_acf_group';

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

    public function findBySlugAndGroup(string $slug, int $groupId): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)
            ->where("slug = '" . pSQL($slug) . "'")
            ->where(self::GROUP_FK . ' = ' . (int) $groupId);
        $result = Db::getInstance()->getRow($sql);
        return $result ?: null;
    }

    public function findByGroup(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)
            ->where(self::GROUP_FK . ' = ' . (int) $groupId)
            ->where('id_parent IS NULL')
            ->where('active = 1')
            ->orderBy('position ASC');
        $result = Db::getInstance()->executeS($sql);
        return $result ?: [];
    }

    public function findAllByGroup(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)
            ->where(self::GROUP_FK . ' = ' . (int) $groupId)
            ->orderBy('position ASC');
        $result = Db::getInstance()->executeS($sql);
        return $result ?: [];
    }

    public function findByParent(int $parentId): array
    {
        $sql = new DbQuery();
        $sql->select('*')->from(self::TABLE)
            ->where('id_parent = ' . (int) $parentId)
            ->where('active = 1')
            ->orderBy('position ASC');
        $result = Db::getInstance()->executeS($sql);
        return $result ?: [];
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'uuid' => pSQL($data['uuid'] ?? ''),
            self::GROUP_FK => (int) ($data['idAcfGroup'] ?? $data[self::GROUP_FK] ?? 0),
            'type' => pSQL($data['type'] ?? 'text'),
            'title' => pSQL($data['title'] ?? ''),
            'slug' => pSQL($data['slug'] ?? ''),
            'instructions' => pSQL($data['instructions'] ?? ''),
            'config' => pSQL(json_encode($data['config'] ?? [], JSON_THROW_ON_ERROR)),
            'validation' => pSQL(json_encode($data['validation'] ?? [], JSON_THROW_ON_ERROR)),
            'conditions' => pSQL(json_encode($data['conditions'] ?? [], JSON_THROW_ON_ERROR)),
            'wrapper' => pSQL(json_encode($data['wrapper'] ?? [], JSON_THROW_ON_ERROR)),
            'fo_options' => pSQL(json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR)),
            'position' => (int) ($data['position'] ?? 0),
            'translatable' => (int) ($data['translatable'] ?? 0),
            'active' => (int) ($data['active'] ?? 1),
            'date_add' => $now,
            'date_upd' => $now,
        ];
        // Only include id_parent if it has a valid value (FK constraint)
        if (!empty($data['idParent'])) {
            $insert['id_parent'] = (int) $data['idParent'];
        }
        Db::getInstance()->insert(self::TABLE, $insert);
        return (int) Db::getInstance()->Insert_ID();
    }

    public function update(int $id, array $data): bool
    {
        $update = [
            'title' => pSQL($data['title'] ?? ''),
            'slug' => pSQL($data['slug'] ?? ''),
            'instructions' => pSQL($data['instructions'] ?? ''),
            'config' => pSQL(json_encode($data['config'] ?? [], JSON_THROW_ON_ERROR)),
            'validation' => pSQL(json_encode($data['validation'] ?? [], JSON_THROW_ON_ERROR)),
            'conditions' => pSQL(json_encode($data['conditions'] ?? [], JSON_THROW_ON_ERROR)),
            'wrapper' => pSQL(json_encode($data['wrapper'] ?? [], JSON_THROW_ON_ERROR)),
            'fo_options' => pSQL(json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR)),
            'position' => (int) ($data['position'] ?? 0),
            'translatable' => (int) ($data['translatable'] ?? 0),
            'active' => (int) ($data['active'] ?? 1),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        return Db::getInstance()->update(self::TABLE, $update, self::PK . ' = ' . (int) $id);
    }

    public function delete(int $id): bool
    {
        return Db::getInstance()->delete(self::TABLE, self::PK . ' = ' . (int) $id);
    }

    public function deleteByGroup(int $groupId): bool
    {
        return Db::getInstance()->delete(self::TABLE, self::GROUP_FK . ' = ' . (int) $groupId);
    }

    public function slugExistsInGroup(string $slug, int $groupId, ?int $excludeId = null): bool
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')->from(self::TABLE)
            ->where("slug = '" . pSQL($slug) . "'")
            ->where(self::GROUP_FK . ' = ' . (int) $groupId);
        if ($excludeId !== null) {
            $sql->where(self::PK . ' != ' . (int) $excludeId);
        }
        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    public function getNextPosition(int $groupId): int
    {
        $sql = new DbQuery();
        $sql->select('MAX(position)')->from(self::TABLE)->where(self::GROUP_FK . ' = ' . (int) $groupId);
        $maxPosition = Db::getInstance()->getValue($sql);
        return $maxPosition !== false ? ((int) $maxPosition + 1) : 0;
    }

    public function countByGroup(int $groupId): int
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')->from(self::TABLE)->where(self::GROUP_FK . ' = ' . (int) $groupId);
        return (int) Db::getInstance()->getValue($sql);
    }
}

