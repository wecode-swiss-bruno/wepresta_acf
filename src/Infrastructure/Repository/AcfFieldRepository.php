<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Repository;

use DbQuery;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

/**
 * Repository for ACF Fields extending WEDEV Core AbstractRepository.
 */
final class AcfFieldRepository extends AbstractRepository implements AcfFieldRepositoryInterface
{
    private const GROUP_FK = 'id_wepresta_acf_group';

    protected function getTableName(): string
    {
        return 'wepresta_acf_field';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_field';
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findBySlugAndGroup(string $slug, int $groupId): ?array
    {
        return $this->findOneBy([
            'slug' => $slug,
            self::GROUP_FK => $groupId,
        ]);
    }

    public function findByGroup(int $groupId): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from($this->getTableName())
            ->where(self::GROUP_FK . ' = ' . (int) $groupId)
            ->where('id_parent IS NULL')
            ->where('active = 1')
            ->orderBy('position ASC');

        return $this->db->executeS($sql) ?: [];
    }

    public function findAllByGroup(int $groupId): array
    {
        return $this->findBy(
            [self::GROUP_FK => $groupId],
            'position ASC'
        );
    }

    public function findByParent(int $parentId): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from($this->getTableName())
            ->where('id_parent = ' . (int) $parentId)
            ->where('active = 1')
            ->orderBy('position ASC');

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * Create a new field with mapped data.
     */
    public function create(array $data): int
    {
        $mapped = $this->mapDataForInsert($data);

        // Handle id_parent as NULL when empty (FK constraint)
        if (empty($data['idParent'])) {
            // Don't include id_parent at all - let it be NULL
        } else {
            $mapped['id_parent'] = (int) $data['idParent'];
        }

        return $this->insert($mapped);
    }

    /**
     * Update a field with mapped data.
     */
    public function update(int $id, array $data): bool
    {
        $mapped = $this->mapDataForUpdate($data);

        return parent::update($id, $mapped);
    }

    public function deleteByGroup(int $groupId): bool
    {
        return $this->deleteBy([self::GROUP_FK => $groupId]) >= 0;
    }

    public function slugExistsInGroup(string $slug, int $groupId, ?int $excludeId = null): bool
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')
            ->from($this->getTableName())
            ->where("slug = '" . pSQL($slug) . "'")
            ->where(self::GROUP_FK . ' = ' . (int) $groupId);

        if ($excludeId !== null) {
            $sql->where($this->getPrimaryKey() . ' != ' . (int) $excludeId);
        }

        return (int) $this->db->getValue($sql) > 0;
    }

    public function getNextPosition(int $groupId): int
    {
        $sql = new DbQuery();
        $sql->select('MAX(position)')
            ->from($this->getTableName())
            ->where(self::GROUP_FK . ' = ' . (int) $groupId);

        $maxPosition = $this->db->getValue($sql);

        return $maxPosition !== false ? ((int) $maxPosition + 1) : 0;
    }

    public function countByGroup(int $groupId): int
    {
        return $this->count([self::GROUP_FK => $groupId]);
    }

    /**
     * Map input data to database columns for insert.
     */
    private function mapDataForInsert(array $data): array
    {
        $slug = trim($data['slug'] ?? '');
        if (empty($slug) || $slug === '-') {
            // Fallback: generate from title if available
            $title = trim($data['title'] ?? '');
            if (!empty($title)) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $title));
                $slug = substr($slug, 0, 255) ?: 'field';
            } else {
                // Generate unique slug with timestamp to avoid collisions
                $slug = 'field_' . time() . '_' . uniqid('', true);
                $slug = substr($slug, 0, 255);
            }
        }

        return [
            'uuid' => $data['uuid'] ?? '',
            self::GROUP_FK => (int) ($data['idAcfGroup'] ?? $data[self::GROUP_FK] ?? 0),
            'type' => $data['type'] ?? 'text',
            'title' => $data['title'] ?? '',
            'slug' => $slug,
            'instructions' => $data['instructions'] ?? '',
            'config' => json_encode($data['config'] ?? [], JSON_THROW_ON_ERROR),
            'validation' => json_encode($data['validation'] ?? [], JSON_THROW_ON_ERROR),
            'conditions' => json_encode($data['conditions'] ?? [], JSON_THROW_ON_ERROR),
            'wrapper' => json_encode($data['wrapper'] ?? [], JSON_THROW_ON_ERROR),
            'fo_options' => json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR),
            'position' => (int) ($data['position'] ?? 0),
            'translatable' => (int) ($data['translatable'] ?? 0),
            'active' => (int) ($data['active'] ?? 1),
        ];
    }

    /**
     * Map input data to database columns for update.
     */
    private function mapDataForUpdate(array $data): array
    {
        $mapped = [];

        // Title - only update if provided
        if (isset($data['title'])) {
            $mapped['title'] = $data['title'];
        }

        // Slug - only update if provided and valid
        if (isset($data['slug'])) {
            $slug = trim($data['slug']);
            if (empty($slug) || $slug === '-') {
                // Slug provided but empty - generate fallback from title
                $title = trim($data['title'] ?? '');
                $slug = !empty($title) ? strtolower(preg_replace('/[^a-z0-9]+/i', '_', $title)) : 'field';
                $slug = substr($slug, 0, 255) ?: 'field';
            }
            $mapped['slug'] = $slug;
        }

        // Other fields - include defaults if not provided
        $mapped['instructions'] = $data['instructions'] ?? '';
        $mapped['config'] = json_encode($data['config'] ?? [], JSON_THROW_ON_ERROR);
        $mapped['validation'] = json_encode($data['validation'] ?? [], JSON_THROW_ON_ERROR);
        $mapped['conditions'] = json_encode($data['conditions'] ?? [], JSON_THROW_ON_ERROR);
        $mapped['wrapper'] = json_encode($data['wrapper'] ?? [], JSON_THROW_ON_ERROR);
        $mapped['fo_options'] = json_encode($data['foOptions'] ?? [], JSON_THROW_ON_ERROR);
        $mapped['position'] = (int) ($data['position'] ?? 0);
        $mapped['translatable'] = (int) ($data['translatable'] ?? 0);
        $mapped['active'] = (int) ($data['active'] ?? 1);

        return $mapped;
    }
}
