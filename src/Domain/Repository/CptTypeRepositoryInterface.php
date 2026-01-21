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

namespace WeprestaAcf\Domain\Repository;

use WeprestaAcf\Domain\Entity\CptType;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CptTypeRepositoryInterface
{
    public function find(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findBySlug(string $slug, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findActive(?int $langId = null, ?int $shopId = null): array;
    public function findAll(?int $limit = null, ?int $offset = null): array;
    public function findWithGroups(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findWithTaxonomies(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function findFull(int $id, ?int $langId = null, ?int $shopId = null): ?CptType;
    public function save(CptType $type, ?int $shopId = null): int;
    public function delete(int $id): bool;
    public function attachGroup(int $typeId, int $groupId, int $position = 0): bool;
    public function detachGroup(int $typeId, int $groupId): bool;
    public function syncGroups(int $typeId, array $groupIds): void;
    public function attachTaxonomy(int $typeId, int $taxonomyId): bool;
    public function detachTaxonomy(int $typeId, int $taxonomyId): bool;
    public function syncTaxonomies(int $typeId, array $taxonomyIds): void;
    public function slugExists(string $slug, ?int $excludeId = null): bool;
}
