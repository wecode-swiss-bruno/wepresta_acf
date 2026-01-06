<?php
/**
 * FieldTypeApiController - API endpoints for field type management
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeprestaAcf\Application\Service\FieldTypeLoader;
use WeprestaAcf\Application\Service\FieldTypeRegistry;

class FieldTypeApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $registry,
        private readonly FieldTypeLoader $loader
    ) {
    }

    /**
     * List all registered field types.
     */
    #[AdminSecurity("is_granted('read', 'AdminWeprestaAcfConfiguration')", redirectRoute: 'admin_dashboard')]
    public function list(Request $request): JsonResponse
    {
        try {
            // Ensure custom types are loaded
            $this->loader->loadAllCustomTypes();

            $types = $this->loader->getLoadedTypesInfo();
            $paths = $this->loader->getDiscoveryPaths();

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'types' => array_values($types),
                    'paths' => $paths,
                    'total' => count($types),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single field type info.
     */
    #[AdminSecurity("is_granted('read', 'AdminWeprestaAcfConfiguration')", redirectRoute: 'admin_dashboard')]
    public function show(string $type, Request $request): JsonResponse
    {
        try {
            $fieldType = $this->registry->getOrNull($type);
            if ($fieldType === null) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Field type not found',
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'type' => $type,
                    'label' => $fieldType->getLabel(),
                    'category' => $fieldType->getCategory(),
                    'icon' => $fieldType->getIcon(),
                    'supportsTranslation' => $fieldType->supportsTranslation(),
                    'defaultConfig' => $fieldType->getDefaultConfig(),
                    'configSchema' => $fieldType->getConfigSchema(),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a new field type.
     */
    #[AdminSecurity("is_granted('create', 'AdminWeprestaAcfConfiguration')", redirectRoute: 'admin_dashboard')]
    public function upload(Request $request): JsonResponse
    {
        try {
            $file = $request->files->get('file');
            if ($file === null) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No file uploaded',
                ], 400);
            }

            $filename = $file->getClientOriginalName();
            $tmpPath = $file->getPathname();

            // Validate file extension
            if ($file->getClientOriginalExtension() !== 'php') {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Only PHP files are allowed',
                ], 400);
            }

            $result = $this->loader->uploadFieldType($tmpPath, $filename);

            return new JsonResponse([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 201 : 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a custom field type.
     */
    #[AdminSecurity("is_granted('delete', 'AdminWeprestaAcfConfiguration')", redirectRoute: 'admin_dashboard')]
    public function remove(string $type, Request $request): JsonResponse
    {
        try {
            $result = $this->loader->deleteFieldType($type);

            return new JsonResponse([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get discovery paths info.
     */
    #[AdminSecurity("is_granted('read', 'AdminWeprestaAcfConfiguration')", redirectRoute: 'admin_dashboard')]
    public function paths(Request $request): JsonResponse
    {
        try {
            $paths = $this->loader->getDiscoveryPaths();

            return new JsonResponse([
                'success' => true,
                'data' => $paths,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

