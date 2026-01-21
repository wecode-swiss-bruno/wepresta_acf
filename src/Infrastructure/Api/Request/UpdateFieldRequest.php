<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Request;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Update field request DTO.
 */
final class UpdateFieldRequest
{
    /**
     * @param array<string, mixed>|null $config
     * @param array<string, mixed>|null $validation
     * @param array<string, mixed>|null $conditions
     * @param array<string, mixed>|null $wrapper
     * @param array<string, mixed>|null $foOptions
     * @param array<int, array<string, mixed>>|null $translations
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $slug = null,
        public readonly ?string $instructions = null,
        public readonly ?array $config = null,
        public readonly ?array $validation = null,
        public readonly ?array $conditions = null,
        public readonly ?array $wrapper = null,
        public readonly ?array $foOptions = null,
        public readonly ?int $position = null,
        public readonly ?bool $valueTranslatable = null,
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
            instructions: $data['instructions'] ?? null,
            config: isset($data['config']) && \is_array($data['config']) ? $data['config'] : null,
            validation: isset($data['validation']) && \is_array($data['validation']) ? $data['validation'] : null,
            conditions: isset($data['conditions']) && \is_array($data['conditions']) ? $data['conditions'] : null,
            wrapper: isset($data['wrapper']) && \is_array($data['wrapper']) ? $data['wrapper'] : null,
            foOptions: isset($data['foOptions']) && \is_array($data['foOptions']) ? $data['foOptions'] : null,
            position: isset($data['position']) ? (int) $data['position'] : null,
            valueTranslatable: isset($data['value_translatable'])
                ? (bool) $data['value_translatable']
                : (isset($data['valueTranslatable']) ? (bool) $data['valueTranslatable'] : (isset($data['translatable']) ? (bool) $data['translatable'] : null)),
            active: isset($data['active']) ? (bool) $data['active'] : null,
            translations: isset($data['translations']) && \is_array($data['translations']) ? $data['translations'] : null
        );
    }
}
