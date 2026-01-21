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

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="WeprestaAcf\Infrastructure\Repository\AcfFieldValueRepository")
 *
 * @ORM\Table(name="PREFIX_wepresta_acf_field_value")
 *
 * @ORM\HasLifecycleCallbacks
 */
class AcfFieldValue
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer", name="id_wepresta_acf_field_value", options={"unsigned": true}) */
    private int $id;

    /** @ORM\ManyToOne(targetEntity="AcfField", inversedBy="values") @ORM\JoinColumn(name="id_wepresta_acf_field", referencedColumnName="id_wepresta_acf_field", nullable=false, onDelete="CASCADE") */
    private AcfField $field;

    /** @ORM\Column(type="integer", name="id_product", options={"unsigned": true}) */
    private int $productId;

    /** @ORM\Column(type="integer", name="id_shop", options={"unsigned": true}) */
    private int $shopId = 1;

    /** @ORM\Column(type="integer", name="id_lang", nullable=true, options={"unsigned": true}) */
    private ?int $langId = null;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $value = null;

    /** @ORM\Column(type="string", length=255, name="value_index", nullable=true) */
    private ?string $valueIndex = null;

    /** @ORM\Column(type="datetime", name="date_add") */
    private DateTimeInterface $dateAdd;

    /** @ORM\Column(type="datetime", name="date_upd") */
    private DateTimeInterface $dateUpd;

    public function __construct()
    {
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

    public function getField(): AcfField
    {
        return $this->field;
    }

    public function setField(AcfField $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function setShopId(int $shopId): self
    {
        $this->shopId = $shopId;

        return $this;
    }

    public function getLangId(): ?int
    {
        return $this->langId;
    }

    public function setLangId(?int $langId): self
    {
        $this->langId = $langId;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDecodedValue(): mixed
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }
        $decoded = json_decode($this->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->value;
    }

    public function setMixedValue(mixed $value): self
    {
        if ($value === null) {
            $this->value = null;
        } elseif (\is_array($value) || \is_object($value)) {
            $this->value = json_encode($value, JSON_THROW_ON_ERROR);
        } else {
            $this->value = (string) $value;
        }

        return $this;
    }

    public function getValueIndex(): ?string
    {
        return $this->valueIndex;
    }

    public function setValueIndex(?string $valueIndex): self
    {
        $this->valueIndex = $valueIndex !== null ? substr($valueIndex, 0, 255) : null;

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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'id_wepresta_acf_field' => $this->field->getId(),
            'id_product' => $this->productId, 'id_shop' => $this->shopId, 'id_lang' => $this->langId,
            'value' => $this->getDecodedValue(), 'value_index' => $this->valueIndex,
            'date_add' => $this->dateAdd->format('Y-m-d H:i:s'), 'date_upd' => $this->dateUpd->format('Y-m-d H:i:s'),
        ];
    }
}
