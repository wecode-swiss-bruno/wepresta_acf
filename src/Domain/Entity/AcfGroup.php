<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;


if (!defined('_PS_VERSION_')) {
    exit;
}

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="WeprestaAcf\Infrastructure\Repository\AcfGroupRepository")
 *
 * @ORM\Table(name="PREFIX_wepresta_acf_group")
 *
 * @ORM\HasLifecycleCallbacks
 */
class AcfGroup
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer", name="id_wepresta_acf_group", options={"unsigned": true}) */
    private int $id;

    /** @ORM\Column(type="string", length=36, unique=true) */
    private string $uuid;

    /** @ORM\Column(type="string", length=255) */
    private string $title;

    /** @ORM\Column(type="string", length=255, unique=true) */
    private string $slug;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $description = null;

    /** @ORM\Column(type="json", name="location_rules", nullable=true) @var array<mixed>|null */
    private ?array $locationRules = null;

    /** @ORM\Column(type="string", length=100, name="placement_tab") */
    private string $placementTab = 'description';

    /** @ORM\Column(type="string", length=100, name="placement_position", nullable=true) */
    private ?string $placementPosition = null;

    /** @ORM\Column(type="integer") */
    private int $priority = 10;

    /** @ORM\Column(type="json", name="bo_options", nullable=true) @var array<string, mixed>|null */
    private ?array $boOptions = null;

    /** @ORM\Column(type="boolean") */
    private bool $active = true;

    /** @ORM\Column(type="datetime", name="date_add") */
    private DateTimeInterface $dateAdd;

    /** @ORM\Column(type="datetime", name="date_upd") */
    private DateTimeInterface $dateUpd;

    /** @ORM\OneToMany(targetEntity="AcfField", mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true) @ORM\OrderBy({"position": "ASC"}) @var Collection<int, AcfField> */
    private Collection $fields;

    public function __construct()
    {
        $this->uuid = self::generateUuid();
        $this->fields = new ArrayCollection();
        $this->dateAdd = new DateTime();
        $this->dateUpd = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->dateUpd = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    public function getLocationRules(): ?array
    {
        return $this->locationRules;
    }

    /**
     * @param array<mixed>|null $locationRules
     */
    public function setLocationRules(?array $locationRules): self
    {
        $this->locationRules = $locationRules;

        return $this;
    }

    public function getPlacementTab(): string
    {
        return $this->placementTab;
    }

    public function setPlacementTab(string $placementTab): self
    {
        $this->placementTab = $placementTab;

        return $this;
    }

    public function getPlacementPosition(): ?string
    {
        return $this->placementPosition;
    }

    public function setPlacementPosition(?string $placementPosition): self
    {
        $this->placementPosition = $placementPosition;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBoOptions(): ?array
    {
        return $this->boOptions;
    }

    /**
     * @param array<string, mixed>|null $boOptions
     */
    public function setBoOptions(?array $boOptions): self
    {
        $this->boOptions = $boOptions;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getDateAdd(): DateTimeInterface
    {
        return $this->dateAdd;
    }

    public function getDateUpd(): DateTimeInterface
    {
        return $this->dateUpd;
    }

    /**
     * @return Collection<int, AcfField>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function addField(AcfField $field): self
    {
        if (! $this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setGroup($this);
        }

        return $this;
    }

    public function removeField(AcfField $field): self
    {
        if ($this->fields->removeElement($field) && $field->getGroup() === $this) {
            $field->setGroup(null);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'uuid' => $this->uuid, 'title' => $this->title, 'slug' => $this->slug,
            'description' => $this->description, 'location_rules' => $this->locationRules,
            'placement_tab' => $this->placementTab, 'placement_position' => $this->placementPosition,
            'priority' => $this->priority, 'bo_options' => $this->boOptions,
            'active' => $this->active, 'date_add' => $this->dateAdd->format('Y-m-d H:i:s'),
            'date_upd' => $this->dateUpd->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArrayWithFields(): array
    {
        $data = $this->toArray();
        $data['fields'] = $this->fields->map(fn (AcfField $f) => $f->toArray())->toArray();

        return $data;
    }

    private static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
