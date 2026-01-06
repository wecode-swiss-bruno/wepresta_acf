<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Application\Service\ValueProvider;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValueApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly ValueHandler $valueHandler,
        private readonly ValueProvider $valueProvider,
    ) {}

    public function save(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonPayload($request);

            // Support both legacy (productId) and generic (entityType/entityId) formats
            $entityId = $data['productId'] ?? $data['entityId'] ?? null;
            $entityType = $data['entityType'] ?? 'product';

            if (empty($entityId) || !isset($data['values'])) {
                return $this->jsonError('entityId (or productId) and values are required', Response::HTTP_BAD_REQUEST);
            }

            $errors = $this->valueHandler->validateProductFieldValues($data['values']);
            if (!empty($errors)) {
                return $this->json(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->valueHandler->saveProductFieldValues(
                (int) $entityId,
                $data['values'],
                $data['shopId'] ?? null,
                $data['langId'] ?? null
            );

            return $this->json(['success' => true, 'message' => 'Values saved successfully']);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    public function show(int $productId, Request $request): JsonResponse
    {
        try {
            $shopId = $request->query->has('shopId') ? (int) $request->query->get('shopId') : null;
            $langId = $request->query->has('langId') ? (int) $request->query->get('langId') : null;
            $withMeta = $request->query->get('withMeta', '0') === '1';

            $values = $withMeta
                ? $this->valueProvider->getProductFieldValuesWithMeta($productId, $shopId, $langId)
                : $this->valueProvider->getProductFieldValues($productId, $shopId, $langId);

            return $this->json(['success' => true, 'data' => $values]);
        } catch (\Exception $e) { return $this->jsonError($e->getMessage()); }
    }

    /** @return array<string, mixed> */
    private function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if (empty($content)) { return []; }
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) { throw new \InvalidArgumentException('Invalid JSON payload'); }
        return $data;
    }

    private function jsonError(string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return $this->json(['success' => false, 'error' => $message], $status);
    }
}

