<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\DateType;

final class DateField extends AbstractFieldType
{
    public function getType(): string { return 'date'; }
    public function getLabel(): string { return 'Date'; }
    public function getFormType(): string { return DateType::class; }
    public function getCategory(): string { return 'content'; }
    public function getIcon(): string { return 'calendar_today'; }
    public function supportsTranslation(): bool { return false; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['widget'] = 'single_text';
        $options['format'] = 'yyyy-MM-dd';
        $options['html5'] = true;
        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if ($value instanceof \DateTimeInterface) { return $value->format('Y-m-d'); }
        return (string) $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        return (string) $value; // Return as formatted string for HTML5 date input
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        return sprintf('<input type="date" class="form-control %s %s" id="%s%s" %s %s value="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $escapedValue);
    }
}

