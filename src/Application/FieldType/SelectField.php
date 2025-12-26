<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class SelectField extends AbstractFieldType
{
    public function getType(): string { return 'select'; }
    public function getLabel(): string { return 'Select'; }
    public function getFormType(): string { return ChoiceType::class; }
    public function getCategory(): string { return 'choice'; }
    public function getIcon(): string { return 'arrow_drop_down_circle'; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['choices'] = $this->parseChoices($fieldConfig['choices'] ?? []);
        $options['multiple'] = $fieldConfig['multiple'] ?? false;
        $options['placeholder'] = $fieldConfig['placeholder'] ?? 'Select...';
        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if (is_array($value)) { return json_encode(array_values($value), JSON_THROW_ON_ERROR); }
        return (string) $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null) { return null; }
        if (is_string($value) && str_starts_with($value, '[')) {
            return json_decode($value, true) ?? $value;
        }
        return $value;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $config = $field['config'] ?? [];
        $choices = $this->parseChoices($config['choices'] ?? []);
        $multiple = $config['multiple'] ?? false;
        $selectedValues = is_array($value) ? $value : [$value];

        $html = sprintf('<select class="form-control %s %s" id="%s%s" %s %s%s>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'] . ($multiple ? '[]' : ''), $a['dataAttr'], $multiple ? ' multiple' : '');
        if (!$multiple && !empty($config['placeholder'])) { $html .= sprintf('<option value="">%s</option>', $this->escapeAttr($config['placeholder'])); }
        foreach ($choices as $label => $val) { $selected = in_array($val, $selectedValues, true) ? ' selected' : ''; $html .= sprintf('<option value="%s"%s>%s</option>', $this->escapeAttr($val), $selected, $this->escapeAttr($label)); }
        return $html . '</select>';
    }

    /** @param array<mixed>|string $choices @return array<string, string> */
    private function parseChoices(array|string $choices): array
    {
        if (is_string($choices)) { $choices = explode("\n", $choices); }
        $result = [];
        foreach ($choices as $choice) {
            if (is_array($choice) && isset($choice['label'], $choice['value'])) { $result[$choice['label']] = $choice['value']; }
            elseif (is_string($choice)) { $parts = explode(':', trim($choice), 2); $result[trim($parts[1] ?? $parts[0])] = trim($parts[0]); }
        }
        return $result;
    }
}

