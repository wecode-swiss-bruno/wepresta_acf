<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class BooleanField extends AbstractFieldType
{
    public function getType(): string { return 'boolean'; }
    public function getLabel(): string { return 'True/False'; }
    public function getFormType(): string { return CheckboxType::class; }
    public function getCategory(): string { return 'choice'; }
    public function getIcon(): string { return 'toggle_on'; }
    public function supportsTranslation(): bool { return false; }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed { return $value === '1' || $value === 1 || $value === true; }
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string { return $this->denormalizeValue($value) ? 'Yes' : 'No'; }
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string { return $this->denormalizeValue($value) ? '1' : '0'; }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $checked = $this->denormalizeValue($value) ? ' checked' : '';
        return sprintf('<div class="form-check form-switch"><input type="checkbox" class="form-check-input %s" id="%s%s" %s %s value="1"%s><label class="form-check-label" for="%s%s">%s</label></div>', $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $checked, $a['idPrefix'], $a['slug'], $this->escapeAttr($field['title'] ?? ''));
    }
}

