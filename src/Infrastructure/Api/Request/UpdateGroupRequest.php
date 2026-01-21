<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Request;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Update group request DTO.
 */
final class UpdateGroupRequest
{
    /**
     * @param array<int, array<string, mixed>>|null $locationRules
     * @param array<string, mixed>|null $boOptions
     * @param array<string, mixed>|null $foOptions
     * @param array<int, array<string, mixed>>|null $translations
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $slug = null,
        public readonly ?string $description = null,
        public readonly ?array $locationRules = null,
        public readonly ?string $placementTab = null,
        public readonly ?string $placementPosition = null,
        public readonly ?int $priority = null,
        public readonly ?array $boOptions = null,
        public readonly ?array $foOptions = null,
        public readonly ?bool $active = null,
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
            title: $data['title'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            locationRules: isset($data['locationRules']) && \is_array($data['locationRules']) ? $data['locationRules'] : null,
            placementTab: $data['placementTab'] ?? null,
            placementPosition: $data['placementPosition'] ?? null,
            priority: isset($data['priority']) ? (int) $data['priority'] : null,
            boOptions: isset($data['boOptions']) && \is_array($data['boOptions']) ? $data['boOptions'] : null,
            foOptions: isset($data['foOptions']) && \is_array($data['foOptions']) ? $data['foOptions'] : null,
            active: isset($data['active']) ? (bool) $data['active'] : null,
            translations: isset($data['translations']) && \is_array($data['translations']) ? $data['translations'] : null
        );
    }
}
