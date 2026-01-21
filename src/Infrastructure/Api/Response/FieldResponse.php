<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Response;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Field response DTO.
 */
final class FieldResponse
{
    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $validation
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $wrapper
     * @param array<string, mixed> $foOptions
     * @param array<int, array<string, mixed>> $translations
     * @param array<int, self>|null $children
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $groupId,
        public readonly ?int $parentId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $instructions,
        public readonly array $config,
        public readonly array $validation,
        public readonly array $conditions,
        public readonly array $wrapper,
        public readonly array $foOptions,
        public readonly int $position,
        public readonly bool $valueTranslatable,
        public readonly bool $active,
        public readonly array $translations,
        public readonly ?array $children = null,
        public readonly ?string $dateAdd = null,
        public readonly ?string $dateUpd = null
    ) {
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'groupId' => $this->groupId,
            'parentId' => $this->parentId,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'instructions' => $this->instructions,
            'config' => $this->config,
            'validation' => $this->validation,
            'conditions' => $this->conditions,
            'wrapper' => $this->wrapper,
            'foOptions' => $this->foOptions,
            'position' => $this->position,
            'value_translatable' => $this->valueTranslatable,
            'translatable' => $this->valueTranslatable, // Legacy support
            'active' => $this->active,
            'translations' => $this->translations,
        ];

        if ($this->dateAdd !== null) {
            $data['dateAdd'] = $this->dateAdd;
        }

        if ($this->dateUpd !== null) {
            $data['dateUpd'] = $this->dateUpd;
        }

        if ($this->children !== null) {
            $data['children'] = array_map(fn (self $child) => $child->toArray(), $this->children);
        }

        return $data;
    }
}
