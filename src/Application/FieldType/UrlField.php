<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\UrlType;

final class UrlField extends AbstractFieldType
{
    public function getType(): string { return 'url'; }
    public function getLabel(): string { return 'URL'; }
    public function getFormType(): string { return UrlType::class; }
    public function getIcon(): string { return 'link'; }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) { $errors[] = 'Invalid URL.'; }
        return $errors;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') { return ''; }
        return sprintf('<a href="%s" target="_blank" rel="noopener">%s</a>', $this->escapeAttr((string) $value), htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        return sprintf('<input type="url" class="form-control %s %s" id="%s%s" %s %s value="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $this->escapeAttr((string) $value));
    }
}

