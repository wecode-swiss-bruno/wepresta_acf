<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Request;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Create field request DTO.
 */
final class CreateFieldRequest
{
    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $validation
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $wrapper
     * @param array<string, mixed> $foOptions
     * @param array<int, array<string, mixed>>|null $translations
     */
    public function __construct(
        public readonly int $groupId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $slug = '',
        public readonly ?int $parentId = null,
        public readonly ?string $instructions = null,
        public readonly array $config = [],
        public readonly array $validation = [],
        public readonly array $conditions = [],
        public readonly array $wrapper = [],
        public readonly array $foOptions = [],
        public readonly int $position = 0,
        public readonly bool $translatable = false,
        public readonly bool $active = true,
        public readonly ?array $translations = null
    ) {
    }

    /**
     * Create from array (request data).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, int $groupId): self
    {
        return new self(
            groupId: $groupId,
            type: $data['type'] ?? '',
            title: $data['title'] ?? '',
            slug: $data['slug'] ?? '',
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            instructions: $data['instructions'] ?? null,
            config: \is_array($data['config'] ?? null) ? $data['config'] : [],
            validation: \is_array($data['validation'] ?? null) ? $data['validation'] : [],
            conditions: \is_array($data['conditions'] ?? null) ? $data['conditions'] : [],
            wrapper: \is_array($data['wrapper'] ?? null) ? $data['wrapper'] : [],
            foOptions: \is_array($data['foOptions'] ?? null) ? $data['foOptions'] : [],
            position: isset($data['position']) ? (int) $data['position'] : 0,
            translatable: (bool) ($data['translatable'] ?? false),
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

        if (empty($this->type)) {
            $errors['type'] = 'Type is required';
        }

        if (empty($this->title)) {
            $errors['title'] = 'Title is required';
        }

        return $errors;
    }
}
