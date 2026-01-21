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

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Application\Service\ValueProvider;
use WeprestaAcf\Infrastructure\Api\Request\SaveValuesRequest;

/**
 * Value API Controller - Handles field values save/retrieval.
 */
final class ValueApiController extends AbstractApiController
{
    public function __construct(
        private readonly ValueHandler $valueHandler,
        private readonly ValueProvider $valueProvider
    ) {
    }

    /**
     * Save field values for an entity.
     */
    public function save(Request $request): JsonResponse
    {
        try {
            // Parse and validate request
            $data = $this->getJsonPayload($request);
            $saveRequest = SaveValuesRequest::fromArray($data);

            $errors = $saveRequest->validate();

            if (! empty($errors)) {
                return $this->jsonValidationError($errors);
            }

            // Validate field values
            $fieldErrors = $this->valueHandler->validateProductFieldValues($saveRequest->values);

            if (! empty($fieldErrors)) {
                return $this->jsonValidationError($fieldErrors, 'Field validation failed');
            }

            // Save values
            $this->valueHandler->saveEntityFieldValues(
                $saveRequest->entityType,
                $saveRequest->entityId,
                $saveRequest->values,
                $saveRequest->shopId,
                $saveRequest->langId
            );

            return $this->jsonSuccess([
                'entityType' => $saveRequest->entityType,
                'entityId' => $saveRequest->entityId,
            ], 'Values saved successfully');
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Get field values for a product.
     */
    public function show(int $productId, Request $request): JsonResponse
    {
        try {
            $shopId = $request->query->has('shopId') ? (int) $request->query->get('shopId') : null;
            $langId = $request->query->has('langId') ? (int) $request->query->get('langId') : null;
            $withMeta = $request->query->get('withMeta', '0') === '1';

            $values = $withMeta
                ? $this->valueProvider->getProductFieldValuesWithMeta($productId, $shopId, $langId)
                : $this->valueProvider->getProductFieldValues($productId, $shopId, $langId);

            return $this->jsonSuccess($values);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
