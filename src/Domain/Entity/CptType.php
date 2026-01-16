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
    /** @var array<int, string>|string */
    private $name;
    /** @var array<int, string>|string|null */
    private $description = null;
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

        // Decode JSON for name if it's a string
        $name = $data['name'] ?? '';
        if (is_string($name) && !empty($name)) {
            $decoded = json_decode($name, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $name = $decoded;
            }
        }
        $this->setName($name);

        // Decode JSON for description if it's a string
        $description = $data['description'] ?? null;
        if (is_string($description) && !empty($description)) {
            $decoded = json_decode($description, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $description = $decoded;
            }
        }
        $this->setDescription($description);

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

        // Handle translations
        if (isset($data['translations']) && is_array($data['translations'])) {
            $this->setTranslations($data['translations']);
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

    /**
     * @return array<int, string>|string
     */
    public function getName($langId = null)
    {
        if ($langId !== null && is_array($this->name)) {
            if (isset($this->name[$langId])) {
                return $this->name[$langId];
            }
            // Fallback to default or first available
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            return $this->name[$defaultLangId] ?? (reset($this->name) ?: '');
        }
        // If name is an array, return default language or first available
        if (is_array($this->name)) {
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            return $this->name[$defaultLangId] ?? (reset($this->name) ?: '');
        }
        return $this->name;
    }

    /**
     * @return array<int, string>|string|null
     */
    public function getDescription($langId = null)
    {
        if ($langId !== null && is_array($this->description)) {
            if (isset($this->description[$langId])) {
                return $this->description[$langId];
            }
            // Fallback to default or first available
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            return $this->description[$defaultLangId] ?? (reset($this->description) ?: '');
        }
        // If description is an array, return default language or first available
        if (is_array($this->description)) {
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            return $this->description[$defaultLangId] ?? (reset($this->description) ?: '');
        }
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

    /**
     * @param array<int, string>|string $name
     */
    public function setName($name): self
    {
        $this->name = $name;
        if (is_array($name)) {
            foreach ($name as $langId => $val) {
                $this->translations[$langId]['name'] = $val;
            }
        }
        return $this;
    }

    /**
     * @param array<int, string>|string|null $description
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        if (is_array($description)) {
            foreach ($description as $langId => $val) {
                $this->translations[$langId]['description'] = $val;
            }
        }
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

        // Also hydrate name and description if they are empty or if we want to sync
        if (!empty($translations)) {
            $names = [];
            $descriptions = [];
            foreach ($translations as $langId => $trans) {
                if (isset($trans['name'])) {
                    $names[$langId] = $trans['name'];
                }
                if (isset($trans['description'])) {
                    $descriptions[$langId] = $trans['description'];
                }
            }
            if (!empty($names)) {
                $this->name = $names;
            }
            if (!empty($descriptions)) {
                $this->description = $descriptions;
            }
        }

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
        // For main table, store only the default language value as simple string (not JSON)
        // Translations are stored separately in _lang table
        $nameValue = $this->name;
        if (is_array($this->name)) {
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            $nameValue = $this->name[$defaultLangId] ?? (reset($this->name) ?: '');
        }

        $descValue = $this->description;
        if (is_array($this->description)) {
            $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
            $descValue = $this->description[$defaultLangId] ?? (reset($this->description) ?: '');
        }

        return [
            'id_wepresta_acf_cpt_type' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $nameValue,
            'description' => $descValue,
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
