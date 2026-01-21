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

use InvalidArgumentException;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

/**
 * Base controller for all API endpoints.
 * Provides common utilities for JSON handling and response formatting.
 */
abstract class AbstractApiController extends FrameworkBundleAdminController
{
    protected ConfigurationAdapter $config;
    protected ContextAdapter $context;

    public function __construct(ConfigurationAdapter $config, ContextAdapter $context)
    {
        $this->config = $config;
        $this->context = $context;

        // Note: parent::__construct() is NOT called for PS8/PS9 compatibility
    }
    /**
     * Parse JSON payload from request body.
     *
     * @throws InvalidArgumentException If JSON is invalid
     *
     * @return array<string, mixed>
     */
    protected function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON payload: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Create a success JSON response.
     *
     * @param mixed $data Response data
     * @param string|null $message Optional success message
     */
    protected function jsonSuccess(mixed $data = null, ?string $message = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->json($response, $status);
    }

    /**
     * Create an error JSON response.
     *
     * @param array<string, mixed>|null $errors Optional structured errors
     */
    protected function jsonError(
        string $message,
        int $status = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $this->json($response, $status);
    }

    /**
     * Create a validation error response.
     *
     * @param array<string, string> $errors Field-level validation errors
     */
    protected function jsonValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->jsonError($message, Response::HTTP_BAD_REQUEST, $errors);
    }

    /**
     * Create a not found error response.
     */
    protected function jsonNotFound(string $resource = 'Resource', int $id = 0): JsonResponse
    {
        $message = $id > 0 ? "{$resource} with ID {$id} not found" : "{$resource} not found";

        return $this->jsonError($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Generate a UUID v4.
     */
    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40); // Version 4
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80); // Variant RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Decode JSON string to array.
     *
     * @return array<string, mixed>
     */
    protected function decodeJson(string $json): array
    {
        return json_decode($json, true) ?: [];
    }
}
