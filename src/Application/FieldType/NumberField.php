<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\NumberType;

final class NumberField extends AbstractFieldType
{
    public function getType(): string { return 'number'; }
    public function getLabel(): string { return 'Number'; }
    public function getFormType(): string { return NumberType::class; }
    public function getCategory(): string { return 'basic'; }
    public function getIcon(): string { return 'pin'; }
    public function supportsTranslation(): bool { return false; }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        return is_numeric($value) ? (float) $value : null;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        if (isset($fieldConfig['min'])) { $options['attr']['min'] = $fieldConfig['min']; }
        if (isset($fieldConfig['max'])) { $options['attr']['max'] = $fieldConfig['max']; }
        if (isset($fieldConfig['step'])) { $options['attr']['step'] = $fieldConfig['step']; }
        return $options;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $config = $field['config'] ?? [];
        $min = isset($config['min']) ? sprintf('min="%s"', $this->escapeAttr((string) $config['min'])) : '';
        $max = isset($config['max']) ? sprintf('max="%s"', $this->escapeAttr((string) $config['max'])) : '';
        $step = isset($config['step']) ? sprintf('step="%s"', $this->escapeAttr((string) $config['step'])) : '';
        $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        return sprintf('<input type="number" class="form-control %s %s" id="%s%s" %s %s value="%s" %s %s %s>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $escapedValue, $min, $max, $step);
    }
}

