<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\FileType;

final class FileField extends AbstractFieldType
{
    public function getType(): string { return 'file'; }
    public function getLabel(): string { return 'File'; }
    public function getFormType(): string { return FileType::class; }
    public function getCategory(): string { return 'media'; }
    public function getIcon(): string { return 'attach_file'; }
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
            return sprintf('<a href="%s" target="_blank">%s</a>', $this->escapeAttr($data['url']), htmlspecialchars($data['original_name'] ?? 'Download', ENT_QUOTES, 'UTF-8'));
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $data = $this->denormalizeValue($value);
        $html = sprintf('<input type="file" class="form-control %s %s" id="%s%s" %s %s accept="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $this->escapeAttr($field['config']['accept'] ?? '*/*'));
        if (is_array($data) && isset($data['url'])) {
            $html .= sprintf('<div class="mt-2"><a href="%s" target="_blank">%s</a></div>', $this->escapeAttr($data['url']), htmlspecialchars($data['original_name'] ?? 'Current file', ENT_QUOTES, 'UTF-8'));
        }
        return $html;
    }
}

