<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

final class DatetimeField extends AbstractFieldType
{
    public function getType(): string { return 'datetime'; }
    public function getLabel(): string { return 'DateTime'; }
    public function getFormType(): string { return DateTimeType::class; }
    public function getCategory(): string { return 'content'; }
    public function getIcon(): string { return 'event'; }
    public function supportsTranslation(): bool { return false; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['widget'] = 'single_text';
        $options['html5'] = true;
        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if ($value instanceof \DateTimeInterface) { return $value->format('Y-m-d\TH:i'); }
        return (string) $value;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        return sprintf('<input type="datetime-local" class="form-control %s %s" id="%s%s" %s %s value="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $this->escapeAttr((string) $value));
    }
}

