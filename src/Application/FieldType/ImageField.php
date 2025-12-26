<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\FileType;

final class ImageField extends AbstractFieldType
{
    public function getType(): string { return 'image'; }
    public function getLabel(): string { return 'Image'; }
    public function getFormType(): string { return FileType::class; }
    public function getCategory(): string { return 'media'; }
    public function getIcon(): string { return 'image'; }
    public function supportsTranslation(): bool { return false; }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if (is_array($value)) { return json_encode($value, JSON_THROW_ON_ERROR); }
        return (string) $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null) { return null; }
        if (is_string($value) && str_starts_with($value, '{')) { return json_decode($value, true) ?? $value; }
        return $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') { return ''; }
        $data = $this->denormalizeValue($value);
        if (is_array($data) && isset($data['url'])) {
            $alt = $data['alt'] ?? $data['original_name'] ?? '';
            return sprintf('<img src="%s" alt="%s" style="max-width:100%%;height:auto;">', $this->escapeAttr($data['url']), $this->escapeAttr($alt));
        }
        return '';
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $data = $this->denormalizeValue($value);
        $html = sprintf('<input type="file" class="form-control %s %s" id="%s%s" %s %s accept="image/*">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr']);
        if (is_array($data) && isset($data['url'])) {
            $html .= sprintf('<div class="mt-2"><img src="%s" alt="" style="max-width:150px;max-height:150px;"></div>', $this->escapeAttr($data['url']));
        }
        return $html;
    }
}

