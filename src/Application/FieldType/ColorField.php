<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\ColorType;

final class ColorField extends AbstractFieldType
{
    public function getType(): string { return 'color'; }
    public function getLabel(): string { return 'Color'; }
    public function getFormType(): string { return ColorType::class; }
    public function getCategory(): string { return 'content'; }
    public function getIcon(): string { return 'palette'; }
    public function supportsTranslation(): bool { return false; }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') { return ''; }
        return sprintf('<span style="display:inline-block;width:20px;height:20px;background:%s;border:1px solid #ccc;"></span> %s', $this->escapeAttr((string) $value), htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        return sprintf('<input type="color" class="form-control form-control-color %s %s" id="%s%s" %s %s value="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $this->escapeAttr((string) ($value ?: '#000000')));
    }
}

