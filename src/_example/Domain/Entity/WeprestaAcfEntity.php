<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Domain\Entity;

use DateTimeImmutable;

/**
 * Entité exemple pour le module
 * Utilisez Doctrine pour la persistance en PS 8+
 */
class WeprestaAcfEntity
{
    private ?int $id = null;
    private string $name;
    private string $description = '';
    private bool $active = true;
    private int $position = 0;
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // =========================================================================
    // SETTERS (Fluent)
    // =========================================================================

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->touch();
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        $this->touch();
        return $this;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        $this->touch();
        return $this;
    }

    // =========================================================================
    // DOMAIN LOGIC
    // =========================================================================

    public function activate(): self
    {
        $this->active = true;
        $this->touch();
        return $this;
    }

    public function deactivate(): self
    {
        $this->active = false;
        $this->touch();
        return $this;
    }

    public function moveUp(): self
    {
        if ($this->position > 0) {
            $this->position--;
            $this->touch();
        }
        return $this;
    }

    public function moveDown(): self
    {
        $this->position++;
        $this->touch();
        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // =========================================================================
    // SERIALIZATION
    // =========================================================================

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'position' => $this->position,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromArray(array $data): self
    {
        $entity = new self($data['name'] ?? '');

        if (isset($data['id'])) {
            $entity->setId((int) $data['id']);
        }

        if (isset($data['description'])) {
            $entity->description = $data['description'];
        }

        if (isset($data['active'])) {
            $entity->active = (bool) $data['active'];
        }

        if (isset($data['position'])) {
            $entity->position = (int) $data['position'];
        }

        if (isset($data['created_at'])) {
            $entity->createdAt = new DateTimeImmutable($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $entity->updatedAt = new DateTimeImmutable($data['updated_at']);
        }

        return $entity;
    }
}

