<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use Exception;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Application\Service\ValueProvider;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldValueRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Infrastructure\Api\Request\CreateGroupRequest;
use WeprestaAcf\Infrastructure\Api\Request\UpdateGroupRequest;
use WeprestaAcf\Infrastructure\Api\Service\GroupMutationService;
use WeprestaAcf\Infrastructure\Api\Transformer\GroupTransformer;

/**
 * Group API Controller - Handles CRUD operations for groups.
 */
final class GroupApiController extends AbstractApiController
{
    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfFieldValueRepositoryInterface $valueRepository,
        private readonly GroupTransformer $groupTransformer,
        private readonly GroupMutationService $groupMutationService,
        private readonly ValueProvider $valueProvider,
        private readonly ValueHandler $valueHandler
    ) {
    }

    /**
     * List all groups.
     */
    public function list(): JsonResponse
    {
        try {
            $groups = $this->groupRepository->findAll();
            $responses = $this->groupTransformer->transformMany($groups, false);

            return $this->jsonSuccess(array_map(fn($r) => $r->toArray(), $responses));
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Show a single group with fields.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                return $this->jsonNotFound('Group', $id);
            }

            $response = $this->groupTransformer->transform($group, true);

            return $this->jsonSuccess($response->toArray());
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Create a new group.
     */
    public function create(Request $request): JsonResponse
    {
        try {
            // Parse and validate request
            $data = $this->getJsonPayload($request);
            $createRequest = CreateGroupRequest::fromArray($data);

            $errors = $createRequest->validate();

            if (!empty($errors)) {
                return $this->jsonValidationError($errors);
            }

            // Create group
            $result = $this->groupMutationService->create($createRequest, $this->generateUuid());

            if (!$result['success']) {
                return $this->jsonError($result['error'], Response::HTTP_BAD_REQUEST);
            }

            // Return created group
            $group = $this->groupRepository->findById($result['groupId']);
            $response = $this->groupTransformer->transform($group, false);

            return $this->jsonSuccess($response->toArray(), null, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Update an existing group.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            // Check group exists
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                return $this->jsonNotFound('Group', $id);
            }

            // Parse request
            $data = $this->getJsonPayload($request);
            $updateRequest = UpdateGroupRequest::fromArray($data);

            // Update group
            $result = $this->groupMutationService->update($id, $group, $updateRequest);

            if (!$result['success']) {
                return $this->jsonError($result['error'], Response::HTTP_BAD_REQUEST);
            }

            // Return updated group
            $updatedGroup = $this->groupRepository->findById($id);
            $response = $this->groupTransformer->transform($updatedGroup, true);

            return $this->jsonSuccess($response->toArray());
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Delete a group.
     */
    public function delete(int $id): JsonResponse
    {
        try {
            // Check group exists
            if (!$this->groupRepository->findById($id)) {
                return $this->jsonNotFound('Group', $id);
            }

            // Delete group
            $this->groupMutationService->delete($id);

            return $this->jsonSuccess(null, 'Group deleted successfully');
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Duplicate a group with all its fields.
     */
    public function duplicate(int $id): JsonResponse
    {
        try {
            // Check group exists
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                return $this->jsonNotFound('Group', $id);
            }

            // Duplicate group
            $result = $this->groupMutationService->duplicate($group, $this->generateUuid());

            if (!$result['success']) {
                return $this->jsonError($result['error'], Response::HTTP_BAD_REQUEST);
            }

            // Return duplicated group
            $newGroup = $this->groupRepository->findById($result['groupId']);
            $response = $this->groupTransformer->transform($newGroup, true);

            return $this->jsonSuccess($response->toArray(), null, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get global values for a group (entity_id = 0).
     */
    public function getGlobalValues(int $id): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                return $this->jsonNotFound('Group', $id);
            }

            // Extract first entity_type from location_rules
            $locationRules = $this->decodeJson($group['location_rules'] ?? '[]');

            if (empty($locationRules) || !isset($locationRules[0]['=='][1])) {
                return $this->jsonError('No entity type defined for this group', Response::HTTP_BAD_REQUEST);
            }

            $primaryEntityType = $locationRules[0]['=='][1];
            $shopId = (int) Context::getContext()->shop->id;

            // Get values with entity_id = 0 (global)
            $values = $this->valueProvider->getEntityFieldValuesAllLanguages($primaryEntityType, 0, $shopId);

            // Ensure values is always an object in JSON, not an array
            $jsonValues = empty($values) ? new stdClass() : $values;

            return $this->jsonSuccess([
                'entityType' => $primaryEntityType,
                'values' => $jsonValues,
            ]);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Save global values for a group (entity_id = 0).
     */
    public function saveGlobalValues(int $id, Request $request): JsonResponse
    {
        try {
            $group = $this->groupRepository->findById($id);

            if (!$group) {
                return $this->jsonNotFound('Group', $id);
            }

            // Check if group is configured for global scope
            $foOptions = $this->decodeJson($group['fo_options'] ?? '{}');

            if (($foOptions['valueScope'] ?? 'entity') !== 'global') {
                return $this->jsonError('This group is not configured for global values', Response::HTTP_BAD_REQUEST);
            }

            // Extract ALL entity types from location_rules
            $locationRules = $this->decodeJson($group['location_rules'] ?? '[]');

            if (empty($locationRules)) {
                return $this->jsonError('No entity type defined for this group', Response::HTTP_BAD_REQUEST);
            }

            $entityTypes = [];

            foreach ($locationRules as $rule) {
                if (isset($rule['==']) && isset($rule['=='][1])) {
                    $entityType = $rule['=='][1];

                    if (!\in_array($entityType, $entityTypes, true)) {
                        $entityTypes[] = $entityType;
                    }
                }
            }

            if (empty($entityTypes)) {
                return $this->jsonError('No entity type defined for this group', Response::HTTP_BAD_REQUEST);
            }

            // Use first entity_type as primary
            $primaryEntityType = $entityTypes[0];
            $shopId = (int) Context::getContext()->shop->id;

            // Get values from request
            $data = $this->getJsonPayload($request);
            $values = $data['values'] ?? [];

            // Get all fields in this group to clean up old global values
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findAllByGroup($groupId);

            // Delete all global values for fields being saved
            foreach ($fields as $field) {
                $fieldId = (int) $field['id_wepresta_acf_field'];
                $fieldSlug = $field['slug'];

                if (!isset($values[$fieldSlug])) {
                    continue;
                }

                // Delete all global values for this field across all entity_types
                foreach ($entityTypes as $entityType) {
                    $this->valueRepository->deleteByFieldAndEntity($fieldId, $entityType, 0, $shopId);
                }
            }

            // Save with primary entity_type and entity_id = 0 (global)
            $this->valueHandler->saveEntityFieldValues($primaryEntityType, 0, $values, $shopId);

            return $this->jsonSuccess(null, 'Global values saved successfully');
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Bulk toggle active status for multiple groups.
     */
    public function bulkToggleActive(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);
            $groupIds = $data['groupIds'] ?? [];
            $active = (bool) ($data['active'] ?? false);

            if (empty($groupIds) || !\is_array($groupIds)) {
                return $this->jsonError('No group IDs provided', Response::HTTP_BAD_REQUEST);
            }

            $updatedCount = 0;

            foreach ($groupIds as $groupId) {
                $groupId = (int) $groupId;

                if ($groupId <= 0) {
                    continue;
                }

                try {
                    $this->groupRepository->update($groupId, ['active' => $active]);
                    ++$updatedCount;
                } catch (Exception $e) {
                    // Continue with other groups even if one fails
                    $this->logError("Failed to update group {$groupId}: " . $e->getMessage());
                }
            }

            return $this->jsonSuccess([
                'updated' => $updatedCount,
                'total' => \count($groupIds),
            ], "Successfully updated {$updatedCount} groups");
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Bulk delete multiple groups.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);
            $groupIds = $data['groupIds'] ?? [];

            if (empty($groupIds) || !\is_array($groupIds)) {
                return $this->jsonError('No group IDs provided', Response::HTTP_BAD_REQUEST);
            }

            $deletedCount = 0;

            foreach ($groupIds as $groupId) {
                $groupId = (int) $groupId;

                if ($groupId <= 0) {
                    continue;
                }

                try {
                    $this->groupRepository->delete($groupId);
                    ++$deletedCount;
                } catch (Exception $e) {
                    // Continue with other groups even if one fails
                    $this->logError("Failed to delete group {$groupId}: " . $e->getMessage());
                }
            }

            return $this->jsonSuccess([
                'deleted' => $deletedCount,
                'total' => \count($groupIds),
            ], "Successfully deleted {$deletedCount} groups");
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
