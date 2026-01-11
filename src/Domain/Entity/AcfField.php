<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="WeprestaAcf\Infrastructure\Repository\AcfFieldRepository")
 *
 * @ORM\Table(name="PREFIX_wepresta_acf_field")
 *
 * @ORM\HasLifecycleCallbacks
 */
class AcfField
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer", name="id_wepresta_acf_field", options={"unsigned": true}) */
    private int $id;

    /** @ORM\Column(type="string", length=36, unique=true) */
    private string $uuid;

    /** @ORM\ManyToOne(targetEntity="AcfGroup", inversedBy="fields") @ORM\JoinColumn(name="id_wepresta_acf_group", referencedColumnName="id_wepresta_acf_group", nullable=false, onDelete="CASCADE") */
    private ?AcfGroup $group = null;

    /** @ORM\ManyToOne(targetEntity="AcfField", inversedBy="children") @ORM\JoinColumn(name="id_parent", referencedColumnName="id_wepresta_acf_field", nullable=true, onDelete="CASCADE") */
    private ?AcfField $parent = null;

    /** @ORM\OneToMany(targetEntity="AcfField", mappedBy="parent", cascade={"persist", "remove"}, orphanRemoval=true) @ORM\OrderBy({"position": "ASC"}) @var Collection<int, AcfField> */
    private Collection $children;

    /** @ORM\Column(type="string", length=50) */
    private string $type;

    /** @ORM\Column(type="string", length=255) */
    private string $title;

    /** @ORM\Column(type="string", length=255) */
    private string $slug;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $instructions = null;

    /** @ORM\Column(type="json", nullable=true) @var array<string, mixed>|null */
    private ?array $config = null;

    /** @ORM\Column(type="json", nullable=true) @var array<string, mixed>|null */
    private ?array $validation = null;

    /** @ORM\Column(type="json", nullable=true) @var array<string, mixed>|null */
    private ?array $conditions = null;

    /** @ORM\Column(type="json", nullable=true) @var array<string, mixed>|null */
    private ?array $wrapper = null;

    /** @ORM\Column(type="integer") */
    private int $position = 0;

    /** @ORM\Column(type="boolean") */
    private bool $translatable = false;

    /** @ORM\Column(type="boolean") */
    private bool $active = true;

    /** @ORM\Column(type="datetime", name="date_add") */
    private DateTimeInterface $dateAdd;

    /** @ORM\Column(type="datetime", name="date_upd") */
    private DateTimeInterface $dateUpd;

    /** @ORM\OneToMany(targetEntity="AcfFieldValue", mappedBy="field", cascade={"remove"}, orphanRemoval=true) @var Collection<int, AcfFieldValue> */
    private Collection $values;

    public function __construct()
    {
        $this->uuid = self::generateUuid();
        $this->children = new ArrayCollection();
        $this->values = new ArrayCollection();
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

    public function getGroup(): ?AcfGroup
    {
        return $this->group;
    }

    public function setGroup(?AcfGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, AcfField>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (! $this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getValidation(): ?array
    {
        return $this->validation;
    }

    /**
     * @param array<string, mixed>|null $validation
     */
    public function setValidation(?array $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    public function isRequired(): bool
    {
        return (bool) ($this->validation['required'] ?? false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    /**
     * @param array<string, mixed>|null $conditions
     */
    public function setConditions(?array $conditions): self
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getWrapper(): ?array
    {
        return $this->wrapper;
    }

    /**
     * @param array<string, mixed>|null $wrapper
     */
    public function setWrapper(?array $wrapper): self
    {
        $this->wrapper = $wrapper;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function setTranslatable(bool $translatable): self
    {
        $this->translatable = $translatable;

        return $this;
    }

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
     * @return Collection<int, AcfFieldValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'uuid' => $this->uuid,
            'id_wepresta_acf_group' => $this->group?->getId(), 'id_parent' => $this->parent?->getId(),
            'type' => $this->type, 'title' => $this->title, 'slug' => $this->slug,
            'instructions' => $this->instructions, 'config' => $this->config,
            'validation' => $this->validation, 'conditions' => $this->conditions,
            'wrapper' => $this->wrapper,
            'position' => $this->position, 'translatable' => $this->translatable, 'active' => $this->active,
            'date_add' => $this->dateAdd->format('Y-m-d H:i:s'), 'date_upd' => $this->dateUpd->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        $options = ['required' => $this->isRequired()];

        if ($this->instructions) {
            $options['help'] = $this->instructions;
        }

        if (isset($this->wrapper['class'])) {
            $options['attr'] = ['class' => $this->wrapper['class']];
        }

        return $options;
    }

    private static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
