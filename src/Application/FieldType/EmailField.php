<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\EmailType;

final class EmailField extends AbstractFieldType
{
    public function getType(): string { return 'email'; }
    public function getLabel(): string { return 'Email'; }
    public function getFormType(): string { return EmailType::class; }
    public function getIcon(): string { return 'email'; }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email address.'; }
        return $errors;
    }

    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $a = $this->buildInputAttrs($field, $context);
        return sprintf('<input type="email" class="form-control %s %s" id="%s%s" %s %s value="%s">', $a['sizeClass'], $a['inputClass'], $a['idPrefix'], $a['slug'], $a['nameAttr'], $a['dataAttr'], $this->escapeAttr((string) $value));
    }
}

