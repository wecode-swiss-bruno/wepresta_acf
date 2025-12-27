<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Symfony FormType for rendering ACF field groups.
 *
 * This form type renders all ACF fields for a given entity type and ID.
 * It can be used to integrate ACF fields into any Symfony form.
 *
 * Usage:
 * ```php
 * $formBuilder->add('acf_fields', AcfFieldsType::class, [
 *     'entity_type' => 'customer',
 *     'entity_id' => $customerId,
 *     'acf_groups' => $matchingGroups,
 *     'acf_values' => $existingValues,
 * ]);
 * ```
 */
class AcfFieldsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $groups = $options['acf_groups'] ?? [];
        $values = $options['acf_values'] ?? [];
        $entityType = $options['entity_type'];
        $entityId = $options['entity_id'];

        // Add hidden fields for entity identification
        $builder->add('acf_entity_type', HiddenType::class, [
            'data' => $entityType,
            'mapped' => false,
        ]);

        $builder->add('acf_entity_id', HiddenType::class, [
            'data' => $entityId,
            'mapped' => false,
        ]);

        // Add fields from each group
        foreach ($groups as $group) {
            $fields = $group['fields'] ?? [];

            foreach ($fields as $field) {
                $slug = $field['slug'];
                $fieldName = 'acf_' . $slug;
                $fieldType = $this->mapToSymfonyType($field['type']);
                $fieldOptions = $this->buildFieldOptions($field, $values[$slug] ?? null);

                $builder->add($fieldName, $fieldType, $fieldOptions);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'acf_groups' => [],
            'acf_values' => [],
            'entity_type' => '',
            'entity_id' => null,
            'mapped' => false,
            'inherit_data' => false,
            'label' => false,
        ]);

        $resolver->setAllowedTypes('acf_groups', 'array');
        $resolver->setAllowedTypes('acf_values', 'array');
        $resolver->setAllowedTypes('entity_type', 'string');
        $resolver->setAllowedTypes('entity_id', ['int', 'null']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'acf_fields';
    }

    /**
     * Maps ACF field type to Symfony form type class.
     *
     * @param string $acfType ACF field type identifier
     * @return string Symfony form type class name
     */
    private function mapToSymfonyType(string $acfType): string
    {
        return match ($acfType) {
            'text', 'url', 'email' => TextType::class,
            'textarea' => TextareaType::class,
            'richtext' => TextareaType::class, // Will be enhanced with TinyMCE via JS
            'number', 'star_rating' => NumberType::class,
            'boolean', 'checkbox' => CheckboxType::class,
            'select', 'radio' => ChoiceType::class,
            'date' => DateType::class,
            'datetime' => DateTimeType::class,
            'time' => TimeType::class,
            'color' => ColorType::class,
            // Complex types are rendered as hidden with JS handling
            'image', 'gallery', 'file', 'files', 'video', 'relation', 'repeater', 'list' => HiddenType::class,
            default => TextType::class,
        };
    }

    /**
     * Builds Symfony form field options from ACF field configuration.
     *
     * @param array $field ACF field configuration
     * @param mixed $value Existing value
     * @return array Symfony form options
     */
    private function buildFieldOptions(array $field, mixed $value): array
    {
        $validation = json_decode($field['validation'] ?? '{}', true) ?: [];
        $settings = json_decode($field['settings'] ?? '{}', true) ?: [];

        $options = [
            'label' => $field['title'] ?? $field['slug'],
            'required' => (bool) ($validation['required'] ?? false),
            'mapped' => false,
            'attr' => [
                'class' => 'acf-field acf-field-' . $field['type'],
                'data-acf-type' => $field['type'],
                'data-acf-slug' => $field['slug'],
            ],
        ];

        // Help text
        if (!empty($field['instructions'])) {
            $options['help'] = $field['instructions'];
        }

        // Set value
        if ($value !== null) {
            // For complex types, serialize to JSON
            if (in_array($field['type'], ['image', 'gallery', 'file', 'files', 'video', 'relation', 'repeater', 'list'], true)) {
                $options['data'] = is_array($value) ? json_encode($value) : $value;
            } else {
                $options['data'] = $value;
            }
        } elseif (isset($field['default_value'])) {
            $options['data'] = $field['default_value'];
        }

        // Choice field options
        if (in_array($field['type'], ['select', 'radio'], true)) {
            $choices = $settings['options'] ?? [];
            if (!empty($choices)) {
                // Build choices array
                $choiceOptions = [];
                foreach ($choices as $choice) {
                    if (is_array($choice)) {
                        $choiceOptions[$choice['label'] ?? $choice['value']] = $choice['value'];
                    } else {
                        $choiceOptions[$choice] = $choice;
                    }
                }
                $options['choices'] = $choiceOptions;
            }

            if ($field['type'] === 'radio') {
                $options['expanded'] = true;
                $options['multiple'] = false;
            }

            if (!($validation['required'] ?? false)) {
                $options['placeholder'] = '-- Select --';
            }
        }

        // Textarea rows
        if ($field['type'] === 'textarea') {
            $options['attr']['rows'] = $settings['rows'] ?? 5;
        }

        // Rich text
        if ($field['type'] === 'richtext') {
            $options['attr']['class'] .= ' js-acf-richtext';
            $options['attr']['rows'] = $settings['rows'] ?? 10;
        }

        // Number constraints
        if ($field['type'] === 'number') {
            if (isset($validation['min'])) {
                $options['attr']['min'] = $validation['min'];
            }
            if (isset($validation['max'])) {
                $options['attr']['max'] = $validation['max'];
            }
            if (isset($settings['step'])) {
                $options['attr']['step'] = $settings['step'];
            }
        }

        // Star rating
        if ($field['type'] === 'star_rating') {
            $options['attr']['min'] = 0;
            $options['attr']['max'] = $settings['max_stars'] ?? 5;
            $options['attr']['step'] = $settings['allow_half'] ?? false ? 0.5 : 1;
            $options['attr']['class'] .= ' js-acf-star-rating';
        }

        // URL field
        if ($field['type'] === 'url') {
            $options['attr']['type'] = 'url';
            $options['attr']['pattern'] = 'https?://.+';
        }

        // Email field
        if ($field['type'] === 'email') {
            $options['attr']['type'] = 'email';
        }

        return $options;
    }
}

