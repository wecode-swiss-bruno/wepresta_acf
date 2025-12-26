<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class TextareaField extends AbstractFieldType
{
    public function getType(): string { return 'textarea'; }
    public function getLabel(): string { return 'Textarea'; }
    public function getFormType(): string { return TextareaType::class; }
    public function getIcon(): string { return 'notes'; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['attr']['rows'] = $fieldConfig['rows'] ?? 4;
        return $options;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $rows = $field['config']['rows'] ?? 4;
        $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        return sprintf('<textarea class="form-control %s %s" id="%s%s" %s %s rows="%d">%s</textarea>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $rows, $escapedValue);
    }
}

