<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class RadioField extends AbstractFieldType
{
    public function getType(): string { return 'radio'; }
    public function getLabel(): string { return 'Radio'; }
    public function getFormType(): string { return ChoiceType::class; }
    public function getCategory(): string { return 'choice'; }
    public function getIcon(): string { return 'radio_button_checked'; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['choices'] = $this->parseChoices($fieldConfig['choices'] ?? []);
        $options['multiple'] = false;
        $options['expanded'] = true;
        return $options;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $choices = $this->parseChoices($field['config']['choices'] ?? []);
        $html = '<div class="form-check-group">';
        foreach ($choices as $label => $val) {
            $checked = $value === $val ? ' checked' : '';
            $id = $a['idPrefix'] . $a['slug'] . '_' . preg_replace('/[^a-z0-9]/i', '_', $val);
            $html .= sprintf('<div class="form-check"><input type="radio" class="form-check-input %s" id="%s" %s %s value="%s"%s><label class="form-check-label" for="%s">%s</label></div>', $a['inputClass'], $id, $a['nameAttr'], $a['dataAttr'], $this->escapeAttr($val), $checked, $id, $this->escapeAttr($label));
        }
        return $html . '</div>';
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

