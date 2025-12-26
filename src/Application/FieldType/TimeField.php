<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\TimeType;

final class TimeField extends AbstractFieldType
{
    public function getType(): string { return 'time'; }
    public function getLabel(): string { return 'Time'; }
    public function getFormType(): string { return TimeType::class; }
    public function getCategory(): string { return 'content'; }
    public function getIcon(): string { return 'schedule'; }
    public function supportsTranslation(): bool { return false; }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);
        $options['widget'] = 'single_text';
        $options['html5'] = true;
        return $options;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        return sprintf('<input type="time" class="form-control %s %s" id="%s%s" %s %s value="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $this->escapeAttr((string) $value));
    }
}

