<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeprestaAcf\Application\Config\FrontHooksRegistry;

/**
 * API Controller for front-office hooks management.
 * Provides endpoints to retrieve available display hooks by entity type.
 */
class FrontHooksController extends FrameworkBundleAdminController
{
    /**
     * Get all available front hooks for a specific entity type.
     * 
     * @param Request $request
     * @param string $entityType Entity type (product, category, etc.)
     * @return JsonResponse
     */
    public function getHooksForEntity(Request $request, string $entityType): JsonResponse
    {
        try {
            $hooks = FrontHooksRegistry::getHooksForEntity($entityType);
            
            if (empty($hooks)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => "No hooks available for entity type: {$entityType}",
                    'data' => [],
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $hooks,
                'defaultHook' => FrontHooksRegistry::getDefaultHook($entityType),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error loading hooks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all available front hooks (all entities).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllHooks(Request $request): JsonResponse
    {
        try {
            $data = [
                'product' => FrontHooksRegistry::getProductHooks(),
                'category' => FrontHooksRegistry::getCategoryHooks(),
            ];

            return new JsonResponse([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error loading hooks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate if a hook is valid for an entity type.
     * 
     * @param Request $request
     * @param string $entityType
     * @param string $hookName
     * @return JsonResponse
     */
    public function validateHook(Request $request, string $entityType, string $hookName): JsonResponse
    {
        try {
            $isValid = FrontHooksRegistry::isValidHook($entityType, $hookName);

            return new JsonResponse([
                'success' => true,
                'valid' => $isValid,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error validating hook: ' . $e->getMessage(),
            ], 500);
        }
    }
}

