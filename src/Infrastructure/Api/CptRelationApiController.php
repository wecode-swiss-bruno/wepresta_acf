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

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\CptRelationRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptRelationApiController extends AbstractApiController
{
    private CptRelationRepositoryInterface $repository;
    private CptTypeRepositoryInterface $typeRepository;

    public function __construct(CptRelationRepositoryInterface $repository, CptTypeRepositoryInterface $typeRepository, ConfigurationAdapter $config, ContextAdapter $context)
    {
        parent::__construct($config, $context);
        $this->repository = $repository;
        $this->typeRepository = $typeRepository;
    }

    public function listBySourceType(int $sourceTypeId, Request $request): JsonResponse
    {
        try {
            // Find relations where this type is the source
            // e.g., Book (Source) -> Author (Target)
            $relations = $this->repository->findBySourceType($sourceTypeId);

            $data = array_map(function ($relation) {
                // Enrich with target type info
                $targetType = $this->typeRepository->find($relation->getTargetTypeId());

                return [
                    'id' => $relation->getId(),
                    'slug' => $relation->getSlug(),
                    'name' => $relation->getName(),
                    'target_type_id' => $relation->getTargetTypeId(),
                    'target_type_slug' => $targetType ? $targetType->getSlug() : null,
                    'target_type_name' => $targetType ? $targetType->getName() : null,
                    'config' => $relation->getConfig(),
                ];
            }, $relations);

            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
