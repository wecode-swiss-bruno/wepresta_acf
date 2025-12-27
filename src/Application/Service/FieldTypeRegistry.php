<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Application\FieldType\FieldTypeInterface;
use WeprestaAcf\Application\FieldType\TextField;
use WeprestaAcf\Application\FieldType\TextareaField;
use WeprestaAcf\Application\FieldType\NumberField;
use WeprestaAcf\Application\FieldType\SelectField;
use WeprestaAcf\Application\FieldType\BooleanField;
use WeprestaAcf\Application\FieldType\DateField;
use WeprestaAcf\Application\FieldType\CheckboxField;
use WeprestaAcf\Application\FieldType\RadioField;
use WeprestaAcf\Application\FieldType\EmailField;
use WeprestaAcf\Application\FieldType\UrlField;
use WeprestaAcf\Application\FieldType\ColorField;
use WeprestaAcf\Application\FieldType\TimeField;
use WeprestaAcf\Application\FieldType\DatetimeField;
use WeprestaAcf\Application\FieldType\RichTextField;
use WeprestaAcf\Application\FieldType\FileField;
use WeprestaAcf\Application\FieldType\ImageField;
use WeprestaAcf\Application\FieldType\GalleryField;
use WeprestaAcf\Application\FieldType\VideoField;
use WeprestaAcf\Application\FieldType\FilesField;
use WeprestaAcf\Application\FieldType\RelationField;
use WeprestaAcf\Application\FieldType\ListField;
use WeprestaAcf\Application\FieldType\RepeaterField;
use WeprestaAcf\Application\FieldType\StarRatingField;

final class FieldTypeRegistry
{
    /** @var array<string, FieldTypeInterface> */
    private array $types = [];
    private bool $initialized = false;

    public function __construct() { $this->registerBuiltInTypes(); }

    private function registerBuiltInTypes(): void
    {
        if ($this->initialized) { return; }
        // Basic
        $this->register(new TextField());
        $this->register(new TextareaField());
        $this->register(new NumberField());
        $this->register(new EmailField());
        $this->register(new UrlField());
        // Choice
        $this->register(new SelectField());
        $this->register(new CheckboxField());
        $this->register(new RadioField());
        $this->register(new BooleanField());
        $this->register(new StarRatingField());
        // Content
        $this->register(new DateField());
        $this->register(new TimeField());
        $this->register(new DatetimeField());
        $this->register(new ColorField());
        $this->register(new RichTextField());
        // Media
        $this->register(new FileField());
        $this->register(new ImageField());
        $this->register(new VideoField());
        $this->register(new GalleryField());
        $this->register(new FilesField());
        // Relational
        $this->register(new RelationField());
        // Layout
        $this->register(new ListField());
        $this->register(new RepeaterField());
        $this->initialized = true;
    }

    public function register(FieldTypeInterface $fieldType): self
    {
        $type = $fieldType->getType();
        if (isset($this->types[$type])) { throw new \InvalidArgumentException(sprintf('Field type "%s" already registered.', $type)); }
        $this->types[$type] = $fieldType;
        return $this;
    }

    public function registerOrReplace(FieldTypeInterface $fieldType): self { $this->types[$fieldType->getType()] = $fieldType; return $this; }
    public function has(string $type): bool { return isset($this->types[$type]); }
    public function get(string $type): FieldTypeInterface { if (!$this->has($type)) { throw new \InvalidArgumentException(sprintf('Unknown field type: "%s"', $type)); } return $this->types[$type]; }
    public function getOrNull(string $type): ?FieldTypeInterface { return $this->types[$type] ?? null; }
    /** @return array<string, FieldTypeInterface> */
    public function getAll(): array { return $this->types; }
    /** @return array<string> */
    public function getTypeIdentifiers(): array { return array_keys($this->types); }

    /** @return array<string, array<string, FieldTypeInterface>> */
    public function getAllGroupedByCategory(): array
    {
        $grouped = [];
        foreach ($this->types as $type => $fieldType) { $grouped[$fieldType->getCategory()][$type] = $fieldType; }
        return $grouped;
    }

    /** @return array<string, array<string, mixed>> */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->types as $type => $fieldType) {
            $result[$type] = ['type' => $type, 'label' => $fieldType->getLabel(), 'category' => $fieldType->getCategory(), 'icon' => $fieldType->getIcon(), 'supportsTranslation' => $fieldType->supportsTranslation(), 'defaultConfig' => $fieldType->getDefaultConfig(), 'configSchema' => $fieldType->getConfigSchema()];
        }
        return $result;
    }

    /** @param array<string, mixed> $config */
    public function normalizeValue(string $type, mixed $value, array $config = []): mixed { return $this->getOrNull($type)?->normalizeValue($value, $config) ?? $value; }
    /** @param array<string, mixed> $config */
    public function denormalizeValue(string $type, mixed $value, array $config = []): mixed { return $this->getOrNull($type)?->denormalizeValue($value, $config) ?? $value; }
    /** @param array<string, mixed> $config @param array<string, mixed> $options */
    public function renderValue(string $type, mixed $value, array $config = [], array $options = []): string { return $this->getOrNull($type)?->renderValue($value, $config, $options) ?? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    /** @param array<string, mixed> $config */
    public function getIndexValue(string $type, mixed $value, array $config = []): ?string { return $this->getOrNull($type)?->getIndexValue($value, $config) ?? ($value === null ? null : substr((string) $value, 0, 255)); }
    public function getFormType(string $type): ?string { return $this->getOrNull($type)?->getFormType(); }
    /** @param array<string, mixed> $config @param array<string, mixed> $validation @return array<string, mixed> */
    public function getFormOptions(string $type, array $config = [], array $validation = []): array { return $this->getOrNull($type)?->getFormOptions($config, $validation) ?? []; }
    /** @param array<string, mixed> $config @param array<string, mixed> $validation @return array<string> */
    public function validate(string $type, mixed $value, array $config = [], array $validation = []): array { return $this->getOrNull($type)?->validate($value, $config, $validation) ?? []; }

    /**
     * Render admin input for a field
     *
     * @param array<string, mixed> $field Field data including type and config
     * @param mixed $value Current value
     * @param array<string, mixed> $context Render context
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $type = $field['type'] ?? '';
        $fieldType = $this->getOrNull($type);
        if (!$fieldType) {
            return '<span class="text-muted">Unknown field type: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        return $fieldType->renderAdminInput($field, $value, $context);
    }
}

