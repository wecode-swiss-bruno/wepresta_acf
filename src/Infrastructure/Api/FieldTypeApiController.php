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


if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use WeprestaAcf\Application\Service\FieldTypeLoader;
use WeprestaAcf\Application\Service\FieldTypeRegistry;

/**
 * FieldType API Controller - Manages field types.
 */
final class FieldTypeApiController extends AbstractApiController
{
    public function __construct(
        private readonly FieldTypeRegistry $registry,
        private readonly FieldTypeLoader $loader
    ) {
    }

    /**
     * List all registered field types.
     */
    public function list(Request $request): JsonResponse
    {
        try {
            // Ensure custom types are loaded
            $this->loader->loadAllCustomTypes();

            $types = $this->loader->getLoadedTypesInfo();
            $paths = $this->loader->getDiscoveryPaths();

            return $this->jsonSuccess([
                'types' => array_values($types),
                'paths' => $paths,
                'total' => \count($types),
            ]);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get a single field type info.
     */
    public function show(string $type, Request $request): JsonResponse
    {
        try {
            $fieldType = $this->registry->getOrNull($type);

            if ($fieldType === null) {
                return $this->jsonNotFound('Field type');
            }

            return $this->jsonSuccess([
                'type' => $type,
                'label' => $fieldType->getLabel(),
                'category' => $fieldType->getCategory(),
                'icon' => $fieldType->getIcon(),
                'supportsTranslation' => $fieldType->supportsTranslation(),
                'defaultConfig' => $fieldType->getDefaultConfig(),
                'configSchema' => $fieldType->getConfigSchema(),
            ]);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Upload a new field type.
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $file = $request->files->get('file');

            if ($file === null) {
                return $this->jsonError('No file uploaded', Response::HTTP_BAD_REQUEST);
            }

            $filename = $file->getClientOriginalName();
            $tmpPath = $file->getPathname();

            // Validate file extension
            if ($file->getClientOriginalExtension() !== 'php') {
                return $this->jsonError('Only PHP files are allowed', Response::HTTP_BAD_REQUEST);
            }

            $result = $this->loader->uploadFieldType($tmpPath, $filename);

            return $this->json(
                ['success' => $result['success'], 'data' => $result],
                $result['success'] ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Delete a custom field type.
     */
    public function remove(string $type, Request $request): JsonResponse
    {
        try {
            $result = $this->loader->deleteFieldType($type);

            return $this->json(
                ['success' => $result['success'], 'data' => $result],
                $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get discovery paths info.
     */
    public function paths(Request $request): JsonResponse
    {
        try {
            $paths = $this->loader->getDiscoveryPaths();

            return $this->jsonSuccess($paths);
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
