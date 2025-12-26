<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class RelationField extends AbstractFieldType
{
    public function getType(): string { return 'relation'; }
    public function getLabel(): string { return 'Relation'; }
    public function getFormType(): string { return ChoiceType::class; }
    public function getCategory(): string { return 'relational'; }
    public function getIcon(): string { return 'link'; }
    public function supportsTranslation(): bool { return false; }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') { return null; }
        if (is_array($value)) { return json_encode(array_map('intval', $value), JSON_THROW_ON_ERROR); }
        return (string) (int) $value;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null) { return null; }
        if (is_string($value) && str_starts_with($value, '[')) {
            return array_map('intval', json_decode($value, true) ?? []);
        }
        $multiple = $fieldConfig['multiple'] ?? false;
        return $multiple ? [(int) $value] : (int) $value;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        $config = $field['config'] ?? [];
        $entityType = $config['entity_type'] ?? 'product';
        $multiple = $config['multiple'] ?? false;
        // This is a placeholder - actual implementation would use AJAX for entity search
        return sprintf('<select class="form-control acf-relation-field %s %s" id="%s%s" %s %s data-entity-type="%s"%s><option value="">Select...</option></select>', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'] . ($multiple ? '[]' : ''), $a['dataAttr'], $this->escapeAttr($entityType), $multiple ? ' multiple' : '');
    }

    public function getDefaultConfig(): array
    {
        return ['entity_type' => 'product', 'multiple' => false];
    }

    public function getConfigSchema(): array
    {
        return array_merge(parent::getConfigSchema(), [
            'entity_type' => ['type' => 'select', 'label' => 'Entity Type', 'default' => 'product', 'choices' => ['product' => 'Product', 'category' => 'Category', 'manufacturer' => 'Manufacturer']],
            'multiple' => ['type' => 'boolean', 'label' => 'Multiple Selection', 'default' => false],
        ]);
    }
}

