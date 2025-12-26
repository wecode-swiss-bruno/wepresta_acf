<?php
declare(strict_types=1);
namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\TextType;

final class TextField extends AbstractFieldType
{
    public function getType(): string { return 'text'; }
    public function getLabel(): string { return 'Text'; }
    public function getFormType(): string { return TextType::class; }
    public function getIcon(): string { return 'text_fields'; }
}

