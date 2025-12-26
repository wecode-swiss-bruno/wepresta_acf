<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\FieldType;

abstract class AbstractFieldType implements FieldTypeInterface
{
    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = ['required' => $validation['required'] ?? false];
        if (!empty($fieldConfig['placeholder'])) { $options['attr']['placeholder'] = $fieldConfig['placeholder']; }
        if (!empty($fieldConfig['class'])) { $options['attr']['class'] = $fieldConfig['class']; }
        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        return ($value === null || $value === '') ? null : $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed { return $value; }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        return ($value === null || $value === '') ? '' : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '') { return null; }
        return substr(is_string($value) ? $value : (string) $value, 0, 255);
    }

    public function getDefaultConfig(): array { return []; }

    public function getConfigSchema(): array
    {
        return [
            'placeholder' => ['type' => 'text', 'label' => 'Placeholder', 'default' => ''],
            'class' => ['type' => 'text', 'label' => 'CSS Class', 'default' => ''],
        ];
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = [];
        if (!empty($validation['required']) && ($value === null || $value === '')) { $errors[] = 'This field is required.'; }
        return $errors;
    }

    public function supportsTranslation(): bool { return true; }
    public function getCategory(): string { return 'basic'; }
    public function getIcon(): string { return 'text_fields'; }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars($field['config']['placeholder'] ?? '', ENT_QUOTES, 'UTF-8');
        return sprintf('<input type="text" class="form-control %s %s" id="%s%s" %s %s value="%s" placeholder="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $escapedValue, $placeholder);
    }

    public function getJsTemplate(array $field): string
    {
        $slug = htmlspecialchars($field['slug'] ?? '', ENT_QUOTES, 'UTF-8');
        $placeholder = addslashes($field['config']['placeholder'] ?? '');
        return sprintf('<input type="text" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}" placeholder="%s">', $slug, $placeholder);
    }

    protected function escapeAttr(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

    /** @return array{slug: string, sizeClass: string, nameAttr: string, dataAttr: string, inputClass: string, idPrefix: string} */
    protected function buildInputAttrs(array $field, array $context = []): array
    {
        $slug = $field['slug'] ?? '';
        $size = $context['size'] ?? '';
        $prefix = $context['prefix'] ?? 'acf_';
        $dataSubfield = !empty($context['dataSubfield']);
        $idPrefix = $context['idPrefix'] ?? 'acf_';
        $escapedSlug = $this->escapeAttr($slug);
        return [
            'slug' => $escapedSlug,
            'sizeClass' => $size ? "form-control-{$size}" : '',
            'nameAttr' => $dataSubfield ? '' : sprintf('name="%s%s"', $prefix, $escapedSlug),
            'dataAttr' => $dataSubfield ? sprintf('data-subfield="%s"', $escapedSlug) : '',
            'inputClass' => $dataSubfield ? 'acf-subfield-input' : '',
            'idPrefix' => $idPrefix,
        ];
    }

    protected function isEmpty(mixed $value): bool { return $value === null || $value === '' || (is_array($value) && count($value) === 0); }
    /** @return array<string> */
    protected function validateStringLength(string $value, array $validation): array
    {
        $errors = [];
        if (isset($validation['minLength']) && strlen($value) < (int) $validation['minLength']) { $errors[] = sprintf('Value must be at least %d characters.', $validation['minLength']); }
        if (isset($validation['maxLength']) && strlen($value) > (int) $validation['maxLength']) { $errors[] = sprintf('Value must not exceed %d characters.', $validation['maxLength']); }
        return $errors;
    }
}

