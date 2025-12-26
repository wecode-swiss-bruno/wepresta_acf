<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\FileType;

final class GalleryField extends AbstractFieldType
{
    public function getType(): string { return 'gallery'; }
    public function getLabel(): string { return 'Gallery'; }
    public function getFormType(): string { return FileType::class; }
    public function getCategory(): string { return 'media'; }
    public function getIcon(): string { return 'photo_library'; }
    public function supportsTranslation(): bool { return false; }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if (is_array($value)) { return json_encode($value, JSON_THROW_ON_ERROR); }
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
        $images = $this->denormalizeValue($value);
        if (empty($images)) { return ''; }
        $html = '<div class="acf-gallery">';
        foreach ($images as $img) {
            if (is_array($img) && isset($img['url'])) {
                $html .= sprintf('<img src="%s" alt="%s" style="max-width:100px;margin:5px;">', $this->escapeAttr($img['url']), $this->escapeAttr($img['alt'] ?? ''));
            }
        }
        return $html . '</div>';
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $images = $this->denormalizeValue($value);
        $html = sprintf('<input type="file" class="form-control %s %s" id="%s%s" %s %s accept="image/*" multiple>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'] . '[]', $a['dataAttr']);
        if (!empty($images)) {
            $html .= '<div class="mt-2 d-flex flex-wrap gap-2">';
            foreach ($images as $img) {
                if (is_array($img) && isset($img['url'])) { $html .= sprintf('<img src="%s" alt="" style="max-width:80px;max-height:80px;">', $this->escapeAttr($img['url'])); }
            }
            $html .= '</div>';
        }
        return $html;
    }
}

