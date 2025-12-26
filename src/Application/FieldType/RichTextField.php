<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class RichTextField extends AbstractFieldType
{
    public function getType(): string { return 'richtext'; }
    public function getLabel(): string { return 'Rich Text (WYSIWYG)'; }
    public function getFormType(): string { return TextareaType::class; }
    public function getCategory(): string { return 'content'; }
    public function getIcon(): string { return 'text_format'; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['attr']['class'] = ($options['attr']['class'] ?? '') . ' autoload_rte';
        $options['attr']['rows'] = $fieldConfig['rows'] ?? 8;
        return $options;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') { return ''; }
        // Rich text is already HTML, return as-is (sanitization should be done on save)
        return (string) $value;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '') { return null; }
        // Strip HTML for indexing
        return substr(strip_tags((string) $value), 0, 255);
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $rows = $field['config']['rows'] ?? 8;
        return sprintf('<textarea class="form-control autoload_rte %s %s" id="%s%s" %s %s rows="%d">%s</textarea>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $rows, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
    }
}

