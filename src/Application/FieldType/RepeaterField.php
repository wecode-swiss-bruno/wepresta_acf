<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;

final class RepeaterField extends AbstractFieldType
{
    public function getType(): string { return 'repeater'; }
    public function getLabel(): string { return 'Repeater'; }
    public function getFormType(): string { return CollectionType::class; }
    public function getCategory(): string { return 'layout'; }
    public function getIcon(): string { return 'repeat'; }
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
        $rows = $this->denormalizeValue($value);
        if (empty($rows)) { return ''; }
        // Simplified table rendering - actual rendering depends on subfield types
        $html = '<table class="table"><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $val) {
                $html .= sprintf('<td><strong>%s:</strong> %s</td>', htmlspecialchars($key, ENT_QUOTES, 'UTF-8'), htmlspecialchars(is_string($val) ? $val : json_encode($val), ENT_QUOTES, 'UTF-8'));
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table>';
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        // Repeaters are not indexable
        return null;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        // Repeater rendering is handled specially by the module hook
        // This is a fallback that shows basic structure
        $a = $this->buildInputAttrs($field, $context);
        return sprintf('<div class="acf-repeater-field" data-field="%s"><div class="acf-repeater-rows"></div><button type="button" class="btn btn-sm btn-outline-primary acf-repeater-add">+ Add Row</button></div>', $a['slug']);
    }

    public function getDefaultConfig(): array
    {
        return ['min_rows' => 0, 'max_rows' => 0, 'layout' => 'table'];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'min_rows' => ['type' => 'number', 'label' => 'Minimum Rows', 'default' => 0],
            'max_rows' => ['type' => 'number', 'label' => 'Maximum Rows', 'default' => 0],
            'layout' => ['type' => 'select', 'label' => 'Layout', 'default' => 'table', 'choices' => ['table' => 'Table', 'block' => 'Block', 'row' => 'Row']],
        ]);
    }
}

