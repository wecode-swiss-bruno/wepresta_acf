<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
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
