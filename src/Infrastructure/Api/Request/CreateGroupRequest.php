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
 * Create group request DTO.
 */
final class CreateGroupRequest
{
    /**
     * @param array<int, array<string, mixed>> $locationRules
     * @param array<string, mixed> $boOptions
     * @param array<string, mixed> $foOptions
     * @param array<int, array<string, mixed>>|null $translations
     */
    public function __construct(
        public readonly string $title,
        public readonly string $slug = '',
        public readonly ?string $description = null,
        public readonly array $locationRules = [],
        public readonly string $placementTab = 'modules',
        public readonly ?string $placementPosition = null,
        public readonly int $priority = 10,
        public readonly array $boOptions = [],
        public readonly array $foOptions = [],
        public readonly bool $active = true,
        public readonly ?array $translations = null
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
            title: $data['title'] ?? '',
            slug: $data['slug'] ?? '',
            description: $data['description'] ?? null,
            locationRules: \is_array($data['locationRules'] ?? null) ? $data['locationRules'] : [],
            placementTab: $data['placementTab'] ?? 'modules',
            placementPosition: $data['placementPosition'] ?? null,
            priority: isset($data['priority']) ? (int) $data['priority'] : 10,
            boOptions: \is_array($data['boOptions'] ?? null) ? $data['boOptions'] : [],
            foOptions: \is_array($data['foOptions'] ?? null) ? $data['foOptions'] : [],
            active: (bool) ($data['active'] ?? true),
            translations: \is_array($data['translations'] ?? null) ? $data['translations'] : null
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

        if (empty($this->title)) {
            $errors['title'] = 'Title is required';
        }

        return $errors;
    }
}
