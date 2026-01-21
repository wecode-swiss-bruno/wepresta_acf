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

namespace WeprestaAcf\Infrastructure\Api\Request;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Save values request DTO.
 */
final class SaveValuesRequest
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly array $values,
        public readonly ?int $shopId = null,
        public readonly ?int $langId = null
    ) {
    }

    /**
     * Create from array (request data).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityType: $data['entityType'] ?? 'product',
            entityId: (int) ($data['productId'] ?? $data['entityId'] ?? 0),
            values: \is_array($data['values'] ?? null) ? $data['values'] : [],
            shopId: isset($data['shopId']) ? (int) $data['shopId'] : null,
            langId: isset($data['langId']) ? (int) $data['langId'] : null
        );
    }

    /**
     * Validate the request.
     *
     * @return array<string, string> Field-level errors
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->entityId <= 0) {
            $errors['entityId'] = 'Entity ID is required and must be positive';
        }

        if (empty($this->values)) {
            $errors['values'] = 'Values are required';
        }

        return $errors;
    }
}
