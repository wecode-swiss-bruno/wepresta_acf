<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Term Entity - Individual taxonomy term (category, tag, etc.)
 */
final class CptTerm
{
    private ?int $id = null;
    private int $taxonomyId;
    private ?int $parentId = null;
    private string $slug;
    private string $name;
    private ?string $description = null;
    private int $position = 0;
    private bool $active = true;
    private ?\DateTimeImmutable $dateAdd = null;
    private ?\DateTimeImmutable $dateUpd = null;

    // Related data (loaded separately)
    private array $translations = [];
    private ?CptTaxonomy $taxonomy = null;
    private ?CptTerm $parent = null;
    private array $children = [];

    public function __construct(array $data = [])
    {
        if (isset($data['id_wepresta_acf_cpt_term'])) {
            $this->id = (int) $data['id_wepresta_acf_cpt_term'];
        }

        $this->taxonomyId = isset($data['id_wepresta_acf_cpt_taxonomy']) ? (int) $data['id_wepresta_acf_cpt_taxonomy'] : 0;
        $this->parentId = !empty($data['id_parent']) ? (int) $data['id_parent'] : (!empty($data['parent_id']) ? (int) $data['parent_id'] : null);
        $this->slug = $data['slug'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->position = isset($data['position']) ? (int) $data['position'] : 0;
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

    public function getTaxonomyId(): int
    {
        return $this->taxonomyId;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->translations['name'] ?? $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->translations['description'] ?? $this->description;
    }

    public function getPosition(): int
    {
        return $this->position;
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

    public function getTaxonomy(): ?CptTaxonomy
    {
        return $this->taxonomy;
    }

    public function getParent(): ?CptTerm
    {
        return $this->parent;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTaxonomyId(int $taxonomyId): self
    {
        $this->taxonomyId = $taxonomyId;
        return $this;
    }

    public function setParentId(?int $parentId): self
    {
        $this->parentId = $parentId;
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

    public function setPosition(int $position): self
    {
        $this->position = $position;
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

    public function setTaxonomy(?CptTaxonomy $taxonomy): self
    {
        $this->taxonomy = $taxonomy;
        return $this;
    }

    public function setParent(?CptTerm $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function setChildren(array $children): self
    {
        $this->children = $children;
        return $this;
    }

    // Helper methods
    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function isTopLevel(): bool
    {
        return $this->parentId === null;
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        $data = [
            'id_wepresta_acf_cpt_term' => $this->id,
            'id_wepresta_acf_cpt_taxonomy' => $this->taxonomyId,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->position,
            'active' => $this->active ? 1 : 0,
        ];

        if ($this->parentId !== null) {
            $data['id_parent'] = $this->parentId;
        }

        return $data;
    }
}
