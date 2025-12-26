<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;

final class ListField extends AbstractFieldType
{
    public function getType(): string { return 'list'; }
    public function getLabel(): string { return 'List'; }
    public function getFormType(): string { return CollectionType::class; }
    public function getCategory(): string { return 'layout'; }
    public function getIcon(): string { return 'list'; }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if (is_array($value)) { return json_encode(array_values(array_filter($value, fn($v) => $v !== '')), JSON_THROW_ON_ERROR); }
        return (string) $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null) { return []; }
        if (is_string($value) && str_starts_with($value, '[')) { return json_decode($value, true) ?? []; }
        return is_array($value) ? $value : [];
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        $items = $this->denormalizeValue($value);
        if (empty($items)) { return ''; }
        $html = '<ul>';
        foreach ($items as $item) { $html .= sprintf('<li>%s</li>', htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8')); }
        return $html . '</ul>';
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $items = $this->denormalizeValue($value);
        $html = '<div class="acf-list-field" data-field="' . $a['slug'] . '">';
        $html .= '<div class="acf-list-items">';
        foreach ($items as $idx => $item) {
            $html .= sprintf('<div class="acf-list-item mb-2"><input type="text" class="form-control %s" %s[] value="%s"><button type="button" class="btn btn-sm btn-outline-danger ms-2 acf-list-remove">×</button></div>', $a['sizeClass'], $a['nameAttr'], $this->escapeAttr((string) $item));
        }
        $html .= '</div>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-primary acf-list-add">+ Add Item</button>';
        return $html . '</div>';
    }
}

