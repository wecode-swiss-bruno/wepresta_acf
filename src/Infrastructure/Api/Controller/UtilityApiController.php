<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Controller;

use Context;
use Db;
use Exception;
use InvalidArgumentException;
use Module;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\AcfServiceContainer;
use WeprestaAcf\Application\Service\FieldTypeRegistry;
use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Infrastructure\Api\AbstractApiController;

/**
 * Utility API Controller - Various utility endpoints.
 */
final class UtilityApiController extends AbstractApiController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly SlugGenerator $slugGenerator,
        private readonly AcfFieldRepositoryInterface $fieldRepository
    ) {
    }

    /**
     * Get all field types.
     */
    public function fieldTypes(): JsonResponse
    {
        try {
            return $this->jsonSuccess($this->fieldTypeRegistry->toArray());
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get field types grouped by category.
     */
    public function fieldTypesGrouped(): JsonResponse
    {
        try {
            $grouped = $this->fieldTypeRegistry->getAllGroupedByCategory();
            $result = [];

            foreach ($grouped as $category => $types) {
                $result[$category] = [];

                foreach ($types as $type => $fieldType) {
                    $result[$category][$type] = [
                        'type' => $type,
                        'label' => $fieldType->getLabel(),
                        'icon' => $fieldType->getIcon(),
                        'supportsTranslation' => $fieldType->supportsTranslation(),
                    ];
                }
            }

            return $this->jsonSuccess($result);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Generate a slug from text.
     */
    public function slugify(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);
            $text = $data['text'] ?? '';

            if (empty($text)) {
                return $this->jsonError('Text is required', Response::HTTP_BAD_REQUEST);
            }

            $slug = $this->slugGenerator->generate($text);

            return $this->jsonSuccess(['slug' => $slug]);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Force upgrade the module.
     */
    public function forceUpgrade(): JsonResponse
    {
        try {
            $module = Module::getInstanceByName('wepresta_acf');

            if (! $module) {
                return $this->jsonNotFound('Module');
            }

            // Get current version from database
            $currentVersion = $module->version ?? '1.0.0';
            $targetVersion = $module::VERSION;

            if ($currentVersion === $targetVersion) {
                return $this->jsonSuccess([
                    'skipped' => true,
                    'message' => "Module is already at version {$targetVersion}",
                    'current_version' => $currentVersion,
                    'target_version' => $targetVersion,
                ]);
            }

            // Force upgrade by resetting version in database
            $db = Db::getInstance();
            $db->execute('UPDATE `' . _DB_PREFIX_ . 'module` SET `version` = "1.0.0" WHERE `name` = "wepresta_acf"');

            // Trigger upgrade
            $result = $module->runUpgradeModule();

            if ($result) {
                return $this->jsonSuccess([
                    'message' => 'Upgrade completed successfully',
                    'previous_version' => $currentVersion,
                    'new_version' => $module->version ?? $targetVersion,
                ]);
            }

            $errors = ! empty($module->_errors) ? $module->_errors : ['Unknown upgrade error'];

            return $this->jsonError(implode(', ', $errors));
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Upload file for global values (entity_id = 0).
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            // Get uploaded file
            $uploadedFile = $request->files->get('file');

            if (! $uploadedFile) {
                return $this->jsonError('No file uploaded', Response::HTTP_BAD_REQUEST);
            }

            // Get parameters
            $fieldSlug = $request->request->get('field_slug');
            $entityType = $request->request->get('entity_type', 'global');
            $entityId = (int) $request->request->get('entity_id', 0);

            if (! $fieldSlug) {
                return $this->jsonError('field_slug is required', Response::HTTP_BAD_REQUEST);
            }

            // Get field info
            $field = $this->fieldRepository->findBySlug($fieldSlug);

            if (! $field) {
                return $this->jsonNotFound('Field');
            }

            $fieldId = (int) $field['id_wepresta_acf_field'];
            $fieldType = $field['type'];
            $fieldConfig = $this->decodeJson($field['config'] ?? '{}');

            // Get shop ID
            $shopId = (int) Context::getContext()->shop->id;

            // Determine file type directory
            $fileTypeDir = match ($fieldType) {
                'image' => 'images',
                'video' => 'videos',
                'file' => 'files',
                default => 'files',
            };

            // Get upload service
            $uploadService = AcfServiceContainer::getFileUploadService();

            // Prepare file array (Symfony UploadedFile to PHP array)
            $fileArray = [
                'name' => $uploadedFile->getClientOriginalName(),
                'type' => $uploadedFile->getMimeType(),
                'tmp_name' => $uploadedFile->getPathname(),
                'error' => $uploadedFile->getError(),
                'size' => $uploadedFile->getSize(),
            ];

            // Get allowed MIME types from field config
            $allowedMimes = [];

            if ($fieldType === 'file' && isset($fieldConfig['allowedMimes'])) {
                $allowedMimes = $fieldConfig['allowedMimes'];
            } elseif ($fieldType === 'image') {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            } elseif ($fieldType === 'video') {
                $allowedMimes = ['video/mp4', 'video/webm', 'video/ogg'];
            }

            // Max file size from config
            $maxSizeMB = $fieldConfig['maxSizeMB'] ?? 10;
            $maxFileSize = $maxSizeMB * 1024 * 1024;

            // Upload file
            $useFixedPath = $fieldConfig['useFixedPath'] ?? false;
            $result = $uploadService->upload(
                $fileArray,
                $fieldId,
                $entityId,
                $shopId,
                $fileTypeDir,
                $allowedMimes,
                $useFixedPath,
                $maxFileSize,
                true // delete existing
            );

            return $this->jsonSuccess($result);
        } catch (InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
