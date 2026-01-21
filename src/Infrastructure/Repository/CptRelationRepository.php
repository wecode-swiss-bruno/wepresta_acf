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

use WeprestaAcf\Domain\Entity\CptRelation;
use WeprestaAcf\Domain\Repository\CptRelationRepositoryInterface;
use WeprestaAcf\Wedev\Core\Repository\AbstractRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptRelationRepository extends AbstractRepository implements CptRelationRepositoryInterface
{
    protected function getTableName(): string
    {
        return 'wepresta_acf_cpt_relation';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_wepresta_acf_cpt_relation';
    }

    public function find(int $id): ?CptRelation
    {
        $row = $this->findOneBy([$this->getPrimaryKey() => $id]);
        return $row ? new CptRelation($row) : null;
    }

    public function findBySlug(string $slug, int $sourceTypeId, int $targetTypeId): ?CptRelation
    {
        $row = $this->findOneBy(['slug' => $slug, 'id_cpt_type_source' => $sourceTypeId, 'id_cpt_type_target' => $targetTypeId]);
        return $row ? new CptRelation($row) : null;
    }

    public function findBySourceType(int $sourceTypeId): array
    {
        $rows = $this->findBy(['id_cpt_type_source' => $sourceTypeId]);
        return array_map(fn($row) => new CptRelation($row), $rows);
    }

    public function findByTargetType(int $targetTypeId): array
    {
        $rows = $this->findBy(['id_cpt_type_target' => $targetTypeId]);
        return array_map(fn($row) => new CptRelation($row), $rows);
    }

    public function findActive(): array
    {
        $rows = $this->findBy(['active' => 1]);
        return array_map(fn($row) => new CptRelation($row), $rows);
    }

    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $rows = $this->findBy([], null, $limit);
        return array_map(fn($row) => new CptRelation($row), $rows);
    }

    public function save(CptRelation $relation): int
    {
        $data = $relation->toArray();
        $id = $data['id_wepresta_acf_cpt_relation'];
        unset($data['id_wepresta_acf_cpt_relation']);

        if ($id) {
            $data['date_upd'] = date('Y-m-d H:i:s');
            $this->update($id, $data);
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');
            $data['date_upd'] = date('Y-m-d H:i:s');
            $id = $this->insert($data);
            $relation->setId($id);
        }

        return $id;
    }

    public function delete(int $id): bool
    {
        return $this->deleteBy([$this->getPrimaryKey() => $id]) > 0;
    }

    public function exists(string $slug, int $sourceTypeId, int $targetTypeId, ?int $excludeId = null): bool
    {
        $row = $this->findOneBy(['slug' => $slug, 'id_cpt_type_source' => $sourceTypeId, 'id_cpt_type_target' => $targetTypeId]);
        if (!$row) {
            return false;
        }
        if ($excludeId && (int) $row['id_wepresta_acf_cpt_relation'] === $excludeId) {
            return false;
        }
        return true;
    }
}
