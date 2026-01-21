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

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Entity\CptType;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTypeService
{
    private CptTypeRepositoryInterface $repository;
    private ContextAdapter $context;

    public function __construct(CptTypeRepositoryInterface $repository, ContextAdapter $context)
    {
        $this->repository = $repository;
        $this->context = $context;
    }

    public function getAllTypes(): array
    {
        return $this->repository->findAll($this->context->getLangId(), $this->context->getShopId());
    }

    public function getActiveTypes(): array
    {
        return $this->repository->findActive($this->context->getLangId(), $this->context->getShopId());
    }

    public function getTypeById(int $id): ?CptType
    {
        return $this->repository->find($id, $this->context->getLangId(), $this->context->getShopId());
    }

    public function getTypeBySlug(string $slug): ?CptType
    {
        return $this->repository->findBySlug($slug, $this->context->getLangId(), $this->context->getShopId());
    }

    public function getTypeWithGroups(int $id): ?CptType
    {
        return $this->repository->findWithGroups($id, $this->context->getLangId(), $this->context->getShopId());
    }

    public function createType(array $data): int
    {
        $type = new CptType($data);
        return $this->repository->save($type, $this->context->getShopId());
    }

    public function updateType(int $id, array $data): bool
    {
        $type = $this->repository->find($id);
        if (!$type) {
            return false;
        }
        if (isset($data['slug']))
            $type->setSlug($data['slug']);
        if (isset($data['name']))
            $type->setName($data['name']);
        if (isset($data['url_prefix']))
            $type->setUrlPrefix($data['url_prefix']);
        if (isset($data['has_archive']))
            $type->setHasArchive((bool) $data['has_archive']);
        $this->repository->save($type);
        return true;
    }

    public function deleteType(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->repository->slugExists($slug, $excludeId);
    }

    public function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = \Tools::str2url($name);
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            ++$counter;
        }
        return $slug;
    }

    public function syncAcfGroups(int $typeId, array $groupIds): void
    {
        $this->repository->syncGroups($typeId, $groupIds);
    }

    public function syncTaxonomies(int $typeId, array $taxonomyIds): void
    {
        $this->repository->syncTaxonomies($typeId, $taxonomyIds);
    }
}
