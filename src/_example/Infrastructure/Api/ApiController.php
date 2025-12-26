<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Infrastructure\Api;

use WeprestaAcf\Example\Application\Service\WeprestaAcfService;
use WeprestaAcf\Example\Infrastructure\Adapter\ConfigurationAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur API REST
 *
 * Fournit des endpoints JSON pour les intégrations externes
 *
 * @Route("/api/wepresta_acf", name="wepresta_acf_api_")
 */
class ApiController
{
    public function __construct(
        private readonly WeprestaAcfService $service,
        private readonly ConfigurationAdapter $config
    ) {
    }

    /**
     * Liste tous les éléments
     *
     * @Route("/items", name="items_list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        if (!$this->isApiEnabled()) {
            return $this->errorResponse('API is disabled', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $items = $this->service->getActiveItems();

            return $this->successResponse([
                'items' => array_map(fn($item) => $item->toArray(), $items),
                'total' => count($items),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Récupère un élément par son ID
     *
     * @Route("/items/{id}", name="items_get", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function get(int $id): JsonResponse
    {
        if (!$this->isApiEnabled()) {
            return $this->errorResponse('API is disabled', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $item = $this->service->getItem($id);

            if (!$item) {
                return $this->errorResponse('Item not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse([
                'item' => $item->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Crée un nouvel élément
     *
     * @Route("/items", name="items_create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->isApiEnabled()) {
            return $this->errorResponse('API is disabled', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $data = $this->parseJsonRequest($request);

            if (empty($data['name'])) {
                return $this->errorResponse('Name is required', Response::HTTP_BAD_REQUEST);
            }

            $item = $this->service->createItem(
                $data['name'],
                $data['description'] ?? ''
            );

            return $this->successResponse([
                'item' => $item->toArray(),
                'message' => 'Item created successfully',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Met à jour un élément
     *
     * @Route("/items/{id}", name="items_update", methods={"PUT", "PATCH"})
     */
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isApiEnabled()) {
            return $this->errorResponse('API is disabled', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $data = $this->parseJsonRequest($request);

            $item = $this->service->updateItem($id, $data);

            if (!$item) {
                return $this->errorResponse('Item not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse([
                'item' => $item->toArray(),
                'message' => 'Item updated successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Supprime un élément
     *
     * @Route("/items/{id}", name="items_delete", methods={"DELETE"})
     */
    public function delete(int $id): JsonResponse
    {
        if (!$this->isApiEnabled()) {
            return $this->errorResponse('API is disabled', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $deleted = $this->service->deleteItem($id);

            if (!$deleted) {
                return $this->errorResponse('Item not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse([
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Bascule l'état actif d'un élément
     *
     * @Route("/items/{id}/toggle", name="items_toggle", methods={"POST"})
     */
    public function toggle(int $id): JsonResponse
    {
        if (!$this->isApiEnabled()) {
            return $this->errorResponse('API is disabled', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $item = $this->service->toggleItem($id);

            if (!$item) {
                return $this->errorResponse('Item not found', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse([
                'item' => $item->toArray(),
                'message' => 'Item toggled successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Vérifie le statut de l'API
     *
     * @Route("/health", name="health", methods={"GET"})
     */
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'module' => 'wepresta_acf',
            'version' => '1.0.0',
            'api_enabled' => $this->isApiEnabled(),
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function isApiEnabled(): bool
    {
        return $this->config->getBool('WEPRESTA_ACF_API_ENABLED');
    }

    private function parseJsonRequest(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return $request->request->all();
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return $data ?? [];
    }

    private function successResponse(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(
            array_merge(['success' => true], $data),
            $status,
            $this->getDefaultHeaders()
        );
    }

    private function errorResponse(string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return new JsonResponse(
            [
                'success' => false,
                'error' => $message,
            ],
            $status,
            $this->getDefaultHeaders()
        );
    }

    private function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Module' => 'wepresta_acf',
        ];
    }
}

