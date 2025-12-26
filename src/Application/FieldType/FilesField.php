<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\FileType;

final class FilesField extends AbstractFieldType
{
    public function getType(): string { return 'files'; }
    public function getLabel(): string { return 'Files (Multiple)'; }
    public function getFormType(): string { return FileType::class; }
    public function getCategory(): string { return 'media'; }
    public function getIcon(): string { return 'folder'; }
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
        $files = $this->denormalizeValue($value);
        if (empty($files)) { return ''; }
        $html = '<ul class="acf-files">';
        foreach ($files as $file) {
            if (is_array($file) && isset($file['url'])) {
                $html .= sprintf('<li><a href="%s" target="_blank">%s</a></li>', $this->escapeAttr($file['url']), htmlspecialchars($file['original_name'] ?? 'Download', ENT_QUOTES, 'UTF-8'));
            }
        }
        return $html . '</ul>';
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $files = $this->denormalizeValue($value);
        $html = sprintf('<input type="file" class="form-control %s %s" id="%s%s" %s %s multiple>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'] . '[]', $a['dataAttr']);
        if (!empty($files)) {
            $html .= '<ul class="mt-2">';
            foreach ($files as $file) {
                if (is_array($file) && isset($file['url'])) { $html .= sprintf('<li><a href="%s" target="_blank">%s</a></li>', $this->escapeAttr($file['url']), htmlspecialchars($file['original_name'] ?? 'File', ENT_QUOTES, 'UTF-8')); }
            }
            $html .= '</ul>';
        }
        return $html;
    }
}

