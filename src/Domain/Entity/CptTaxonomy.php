<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Taxonomy Entity - Hierarchical category system for CPT
 */
final class CptTaxonomy
{
    private ?int $id = null;
    private string $uuid;
    private string $slug;
    private string $name;
    private ?string $description = null;
    private bool $hierarchical = true;
    private array $config = [];
    private bool $active = true;
    private ?\DateTimeImmutable $dateAdd = null;
    private ?\DateTimeImmutable $dateUpd = null;

    // Related data (loaded separately)
    private array $translations = [];
    private array $terms = [];

    public function __construct(array $data = [])
    {
        if (isset($data['id_wepresta_acf_cpt_taxonomy'])) {
            $this->id = (int) $data['id_wepresta_acf_cpt_taxonomy'];
        }

        $this->uuid = $data['uuid'] ?? $this->generateUuid();
        $this->slug = $data['slug'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->hierarchical = isset($data['hierarchical']) ? (bool) $data['hierarchical'] : true;

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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isHierarchical(): bool
    {
        return $this->hierarchical;
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

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getTerms(): array
    {
        return $this->terms;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
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

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setHierarchical(bool $hierarchical): self
    {
        $this->hierarchical = $hierarchical;
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

    public function setTranslations(array $translations): self
    {
        $this->translations = $translations;
        return $this;
    }

    public function setTerms(array $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'id_wepresta_acf_cpt_taxonomy' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'hierarchical' => $this->hierarchical ? 1 : 0,
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
