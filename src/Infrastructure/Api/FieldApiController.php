<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Infrastructure\Api\Request\CreateFieldRequest;
use WeprestaAcf\Infrastructure\Api\Request\UpdateFieldRequest;
use WeprestaAcf\Infrastructure\Api\Service\FieldMutationService;
use WeprestaAcf\Infrastructure\Api\Transformer\FieldTransformer;

/**
 * Field API Controller - Handles CRUD operations for fields.
 */
final class FieldApiController extends AbstractApiController
{
    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly FieldTransformer $fieldTransformer,
        private readonly FieldMutationService $fieldMutationService
    ) {
    }

    /**
     * Create a new field.
     */
    public function create(int $groupId, Request $request): JsonResponse
    {
        try {
            // Check group exists
            if (! $this->groupRepository->findById($groupId)) {
                return $this->jsonNotFound('Group', $groupId);
            }

            // Parse and validate request
            $data = $this->getJsonPayload($request);
            $createRequest = CreateFieldRequest::fromArray($data, $groupId);

            $errors = $createRequest->validate();

            if (! empty($errors)) {
                return $this->jsonValidationError($errors);
            }

            // Create field
            $result = $this->fieldMutationService->create($createRequest, $this->generateUuid());

            if (! $result['success']) {
                return $this->jsonError($result['error'], Response::HTTP_BAD_REQUEST);
            }

            // Return created field
            $field = $this->fieldRepository->findById($result['fieldId']);
            $response = $this->fieldTransformer->transform($field);

            return $this->jsonSuccess($response->toArray(), null, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Update an existing field.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            // Check field exists
            $field = $this->fieldRepository->findById($id);

            if (! $field) {
                return $this->jsonNotFound('Field', $id);
            }

            // Parse request
            $data = $this->getJsonPayload($request);
            $updateRequest = UpdateFieldRequest::fromArray($data);

            // Update field
            $result = $this->fieldMutationService->update($id, $field, $updateRequest);

            if (! $result['success']) {
                return $this->jsonError($result['error'], Response::HTTP_BAD_REQUEST);
            }

            // Return updated field
            $updatedField = $this->fieldRepository->findById($id);
            $response = $this->fieldTransformer->transform($updatedField);

            return $this->jsonSuccess($response->toArray());
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Delete a field.
     */
    public function delete(int $id): JsonResponse
    {
        try {
            // Check field exists
            if (! $this->fieldRepository->findById($id)) {
                return $this->jsonNotFound('Field', $id);
            }

            // Delete field
            $this->fieldMutationService->delete($id);

            return $this->jsonSuccess(null, 'Field deleted successfully');
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Reorder fields in a group.
     */
    public function reorder(int $groupId, Request $request): JsonResponse
    {
        try {
            // Parse request
            $data = $this->getJsonPayload($request);

            if (empty($data['order']) || ! \is_array($data['order'])) {
                return $this->jsonError('Order array is required', Response::HTTP_BAD_REQUEST);
            }

            // Reorder
            $this->fieldMutationService->reorder($data['order']);

            return $this->jsonSuccess(null, 'Fields reordered successfully');
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
