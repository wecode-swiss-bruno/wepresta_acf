<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Relation Entity - Defines relationships between different CPT types (e.g., Author ↔ Book)
 */
final class CptRelation
{
    private ?int $id = null;
    private string $uuid;
    private int $sourceTypeId;
    private int $targetTypeId;
    private string $slug;
    private string $name;
    private array $config = [];
    private bool $active = true;
    private ?\DateTimeImmutable $dateAdd = null;
    private ?\DateTimeImmutable $dateUpd = null;

    // Related data (loaded separately)
    private ?CptType $sourceType = null;
    private ?CptType $targetType = null;

    public function __construct(array $data = [])
    {
        if (isset($data['id_wepresta_acf_cpt_relation'])) {
            $this->id = (int) $data['id_wepresta_acf_cpt_relation'];
        }

        $this->uuid = $data['uuid'] ?? $this->generateUuid();
        $this->sourceTypeId = isset($data['id_cpt_type_source']) ? (int) $data['id_cpt_type_source'] : 0;
        $this->targetTypeId = isset($data['id_cpt_type_target']) ? (int) $data['id_cpt_type_target'] : 0;
        $this->slug = $data['slug'] ?? '';
        $this->name = $data['name'] ?? '';

        if (isset($data['config'])) {
            $this->config = is_string($data['config']) ? json_decode($data['config'], true) : $data['config'];
        }

        $this->active = isset($data['active']) ? (bool) $data['active'] : true;

        if (isset($data['date_add'])) {
            $this->dateAdd = $data['date_add'] instanceof \DateTimeImmutable
                ? $data['date_add']
                : new \DateTimeImmutable($data['date_add']);
        }

        if (isset($data['date_upd'])) {
            $this->dateUpd = $data['date_upd'] instanceof \DateTimeImmutable
                ? $data['date_upd']
                : new \DateTimeImmutable($data['date_upd']);
        }
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSourceTypeId(): int
    {
        return $this->sourceTypeId;
    }

    public function getTargetTypeId(): int
    {
        return $this->targetTypeId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getDateAdd(): ?\DateTimeImmutable
    {
        return $this->dateAdd;
    }

    public function getDateUpd(): ?\DateTimeImmutable
    {
        return $this->dateUpd;
    }

    public function getSourceType(): ?CptType
    {
        return $this->sourceType;
    }

    public function getTargetType(): ?CptType
    {
        return $this->targetType;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setSourceTypeId(int $sourceTypeId): self
    {
        $this->sourceTypeId = $sourceTypeId;
        return $this;
    }

    public function setTargetTypeId(int $targetTypeId): self
    {
        $this->targetTypeId = $targetTypeId;
        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function setSourceType(?CptType $sourceType): self
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    public function setTargetType(?CptType $targetType): self
    {
        $this->targetType = $targetType;
        return $this;
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'id_wepresta_acf_cpt_relation' => $this->id,
            'uuid' => $this->uuid,
            'id_cpt_type_source' => $this->sourceTypeId,
            'id_cpt_type_target' => $this->targetTypeId,
            'slug' => $this->slug,
            'name' => $this->name,
            'config' => json_encode($this->config),
            'active' => $this->active ? 1 : 0,
        ];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
