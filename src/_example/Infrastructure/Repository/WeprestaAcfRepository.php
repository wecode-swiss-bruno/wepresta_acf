<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Infrastructure\Repository;

use Db;
use DbQuery;
use WeprestaAcf\Example\Domain\Entity\WeprestaAcfEntity;
use WeprestaAcf\Example\Domain\Repository\WeprestaAcfRepositoryInterface;

/**
 * Implémentation du repository avec PrestaShop Db
 */
final class WeprestaAcfRepository implements WeprestaAcfRepositoryInterface
{
    private const TABLE = 'wepresta_acf';
    private string $table;

    public function __construct(
        private readonly Db $db
    ) {
        $this->table = _DB_PREFIX_ . self::TABLE;
    }

    public function find(int $id): ?WeprestaAcfEntity
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::TABLE)
            ->where('id_wepresta_acf = ' . (int) $id);

        $row = $this->db->getRow($query);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findAll(): array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::TABLE)
            ->orderBy('position ASC, id_wepresta_acf ASC');

        $rows = $this->db->executeS($query);

        return $this->hydrateAll($rows ?: []);
    }

    public function findActive(): array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::TABLE)
            ->where('active = 1')
            ->orderBy('position ASC');

        $rows = $this->db->executeS($query);

        return $this->hydrateAll($rows ?: []);
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null): array
    {
        $query = new DbQuery();
        $query->select('*')->from(self::TABLE);

        foreach ($criteria as $field => $value) {
            if (is_bool($value)) {
                $query->where(pSQL($field) . ' = ' . ($value ? '1' : '0'));
            } elseif (is_int($value)) {
                $query->where(pSQL($field) . ' = ' . $value);
            } elseif (is_null($value)) {
                $query->where(pSQL($field) . ' IS NULL');
            } else {
                $query->where(pSQL($field) . ' = "' . pSQL((string) $value) . '"');
            }
        }

        if ($orderBy) {
            $orders = [];
            foreach ($orderBy as $field => $direction) {
                $orders[] = pSQL($field) . ' ' . (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
            }
            $query->orderBy(implode(', ', $orders));
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $this->db->executeS($query);

        return $this->hydrateAll($rows ?: []);
    }

    public function save(WeprestaAcfEntity $entity): void
    {
        $data = [
            'name' => pSQL($entity->getName()),
            'description' => pSQL($entity->getDescription()),
            'active' => $entity->isActive() ? 1 : 0,
            'position' => $entity->getPosition(),
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if ($entity->getId()) {
            $this->db->update(
                self::TABLE,
                $data,
                'id_wepresta_acf = ' . (int) $entity->getId()
            );
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');

            $this->db->insert(self::TABLE, $data);
            $entity->setId((int) $this->db->Insert_ID());
        }
    }

    public function delete(WeprestaAcfEntity $entity): void
    {
        if (!$entity->getId()) {
            return;
        }

        $this->db->delete(
            self::TABLE,
            'id_wepresta_acf = ' . (int) $entity->getId()
        );
    }

    public function count(): int
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from(self::TABLE);

        return (int) $this->db->getValue($query);
    }

    /**
     * Hydrate une ligne en entité
     */
    private function hydrate(array $row): WeprestaAcfEntity
    {
        return WeprestaAcfEntity::fromArray([
            'id' => (int) $row['id_wepresta_acf'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'active' => (bool) $row['active'],
            'position' => (int) $row['position'],
            'created_at' => $row['date_add'] ?? null,
            'updated_at' => $row['date_upd'] ?? null,
        ]);
    }

    /**
     * Hydrate plusieurs lignes
     *
     * @return WeprestaAcfEntity[]
     */
    private function hydrateAll(array $rows): array
    {
        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }
}

