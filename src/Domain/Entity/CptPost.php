<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Post Entity - Individual content item (blog article, portfolio item, etc.)
 */
final class CptPost
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    private ?int $id = null;
    private string $uuid;
    private int $typeId;
    private string $slug;
    private string $title;
    private string $status = self::STATUS_DRAFT;
    private ?int $employeeId = null;
    private ?string $seoTitle = null;
    private ?string $seoDescription = null;
    private array $seoMeta = [];
    private ?\DateTimeImmutable $dateAdd = null;
    private ?\DateTimeImmutable $dateUpd = null;

    // Related data (loaded separately)
    private array $translations = [];
    private array $shops = [];
    private array $terms = [];
    private ?CptType $type = null;

    public function __construct(array $data = [])
    {
        if (isset($data['id_wepresta_acf_cpt_post'])) {
            $this->id = (int) $data['id_wepresta_acf_cpt_post'];
        }

        $this->uuid = $data['uuid'] ?? $this->generateUuid();
        $this->typeId = isset($data['id_wepresta_acf_cpt_type']) ? (int) $data['id_wepresta_acf_cpt_type'] : 0;
        $this->slug = $data['slug'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_DRAFT;
        $this->employeeId = isset($data['id_employee']) ? (int) $data['id_employee'] : null;
        $this->seoTitle = $data['seo_title'] ?? null;
        $this->seoDescription = $data['seo_description'] ?? null;

        if (isset($data['seo_meta'])) {
            $this->seoMeta = is_string($data['seo_meta']) ? json_decode($data['seo_meta'], true) : $data['seo_meta'];
        }

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

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function getSeoMeta(): array
    {
        return $this->seoMeta;
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

    public function getTerms(): array
    {
        return $this->terms;
    }

    public function getType(): ?CptType
    {
        return $this->type;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTypeId(int $typeId): self
    {
        $this->typeId = $typeId;
        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_DRAFT, self::STATUS_PUBLISHED], true)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }
        $this->status = $status;
        return $this;
    }

    public function setEmployeeId(?int $employeeId): self
    {
        $this->employeeId = $employeeId;
        return $this;
    }

    public function setSeoTitle(?string $seoTitle): self
    {
        $this->seoTitle = $seoTitle;
        return $this;
    }

    public function setSeoDescription(?string $seoDescription): self
    {
        $this->seoDescription = $seoDescription;
        return $this;
    }

    public function setSeoMeta(array $seoMeta): self
    {
        $this->seoMeta = $seoMeta;
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

    public function setTerms(array $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    public function setType(?CptType $type): self
    {
        $this->type = $type;
        return $this;
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function publish(): self
    {
        $this->status = self::STATUS_PUBLISHED;
        return $this;
    }

    public function unpublish(): self
    {
        $this->status = self::STATUS_DRAFT;
        return $this;
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'id_wepresta_acf_cpt_post' => $this->id,
            'uuid' => $this->uuid,
            'id_wepresta_acf_cpt_type' => $this->typeId,
            'slug' => $this->slug,
            'title' => $this->title,
            'status' => $this->status,
            'id_employee' => $this->employeeId,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_meta' => json_encode($this->seoMeta),
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
