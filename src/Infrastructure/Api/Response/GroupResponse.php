<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api\Response;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Group response DTO.
 */
final class GroupResponse
{
    /**
     * @param array<int, array<string, mixed>> $locationRules
     * @param array<string, mixed> $boOptions
     * @param array<string, mixed> $foOptions
     * @param array<int, array<string, mixed>> $translations
     * @param array<int, FieldResponse>|null $fields
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly array $locationRules,
        public readonly string $placementTab,
        public readonly ?string $placementPosition,
        public readonly int $priority,
        public readonly array $boOptions,
        public readonly array $foOptions,
        public readonly bool $active,
        public readonly array $translations,
        public readonly ?array $fields = null,
        public readonly ?int $fieldCount = null,
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'locationRules' => $this->locationRules,
            'placementTab' => $this->placementTab,
            'placementPosition' => $this->placementPosition,
            'priority' => $this->priority,
            'boOptions' => $this->boOptions,
            'foOptions' => $this->foOptions,
            'active' => $this->active,
            'translations' => $this->translations,
        ];

        if ($this->dateAdd !== null) {
            $data['dateAdd'] = $this->dateAdd;
        }

        if ($this->dateUpd !== null) {
            $data['dateUpd'] = $this->dateUpd;
        }

        if ($this->fields !== null) {
            $data['fields'] = array_map(fn (FieldResponse $field) => $field->toArray(), $this->fields);
        }

        if ($this->fieldCount !== null) {
            $data['fieldCount'] = $this->fieldCount;
        }

        return $data;
    }
}
