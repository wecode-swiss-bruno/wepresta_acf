<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Type Entity - Represents a Custom Post Type (Blog, Portfolio, Events, etc.)
 */
final class CptType
{
    private ?int $id = null;
    private string $uuid;
    private string $slug;
    private string $name;
    private ?string $description = null;
    private array $config = [];
    private string $urlPrefix;
    private bool $hasArchive = true;
    private ?string $archiveSlug = null;
    private array $seoConfig = [];
    private string $icon = 'article';
    private int $position = 0;
    private bool $active = true;
    private ?\DateTimeImmutable $dateAdd = null;
    private ?\DateTimeImmutable $dateUpd = null;

    // Translations (loaded separately)
    private array $translations = [];

    // Shops (loaded separately)
    private array $shops = [];

    // ACF Groups (loaded separately)
    private array $acfGroups = [];

    // Taxonomies (loaded separately)
    private array $taxonomies = [];

    public function __construct(array $data = [])
    {
        if (isset($data['id_wepresta_acf_cpt_type'])) {
            $this->id = (int) $data['id_wepresta_acf_cpt_type'];
        }

        $this->uuid = $data['uuid'] ?? $this->generateUuid();
        $this->slug = $data['slug'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;

        if (isset($data['config'])) {
            $this->config = is_string($data['config']) ? json_decode($data['config'], true) : $data['config'];
        }

        $this->urlPrefix = $data['url_prefix'] ?? $this->slug;
        $this->hasArchive = isset($data['has_archive']) ? (bool) $data['has_archive'] : true;
        $this->archiveSlug = $data['archive_slug'] ?? null;

        if (isset($data['seo_config'])) {
            $this->seoConfig = is_string($data['seo_config']) ? json_decode($data['seo_config'], true) : $data['seo_config'];
        }

        $this->icon = $data['icon'] ?? 'article';
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

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    public function hasArchive(): bool
    {
        return $this->hasArchive;
    }

    public function getArchiveSlug(): ?string
    {
        return $this->archiveSlug;
    }

    public function getSeoConfig(): array
    {
        return $this->seoConfig;
    }

    public function getIcon(): string
    {
        return $this->icon;
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

    public function getShops(): array
    {
        return $this->shops;
    }

    public function getAcfGroups(): array
    {
        return $this->acfGroups;
    }

    public function getTaxonomies(): array
    {
        return $this->taxonomies;
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

    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function setUrlPrefix(string $urlPrefix): self
    {
        $this->urlPrefix = $urlPrefix;
        return $this;
    }

    public function setHasArchive(bool $hasArchive): self
    {
        $this->hasArchive = $hasArchive;
        return $this;
    }

    public function setArchiveSlug(?string $archiveSlug): self
    {
        $this->archiveSlug = $archiveSlug;
        return $this;
    }

    public function setSeoConfig(array $seoConfig): self
    {
        $this->seoConfig = $seoConfig;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
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

    public function setShops(array $shops): self
    {
        $this->shops = $shops;
        return $this;
    }

    public function setAcfGroups(array $acfGroups): self
    {
        $this->acfGroups = $acfGroups;
        return $this;
    }

    public function setTaxonomies(array $taxonomies): self
    {
        $this->taxonomies = $taxonomies;
        return $this;
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'id_wepresta_acf_cpt_type' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'config' => json_encode($this->config),
            'url_prefix' => $this->urlPrefix,
            'has_archive' => $this->hasArchive ? 1 : 0,
            'archive_slug' => $this->archiveSlug,
            'seo_config' => json_encode($this->seoConfig),
            'icon' => $this->icon,
            'position' => $this->position,
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
