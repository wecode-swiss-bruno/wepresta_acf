<?php

/**
 * Copyright since 2024 WeCode
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 *
 * @author    Wecode <prestashop@wecode.swiss>
 * @copyright Since 2024 WeCode
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\TimeType;

/**
 * Time field type
 *
 * Stores time as HH:MM string, supports 12h/24h display.
 */
final class TimeField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'time';
    }

    public function getLabel(): string
    {
        return 'Time';
    }

    public function getFormType(): string
    {
        return TimeType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        $options['widget'] = 'single_text';
        $options['html5'] = true;

        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // DateTime object - extract time
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        // Already in HH:MM format
        if (is_string($value) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return substr($value, 0, 5); // Normalize to HH:MM
        }

        // Try to parse
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('H:i', $timestamp);
        }

        return null;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Already a DateTime
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        // Parse HH:MM string
        if (is_string($value) && preg_match('/^(\d{2}):(\d{2})/', $value, $matches)) {
            return $value; // Keep as string for HTML5 time input
        }

        return $value;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        if ($actualValue === null || $actualValue === '') {
            return '';
        }

        $timeStr = $actualValue instanceof \DateTimeInterface
            ? $actualValue->format('H:i')
            : (string) $actualValue;

        // Check display format
        $format = $this->getConfigValue($fieldConfig, 'format', '24h');

        if ($format === '12h' && preg_match('/^(\d{2}):(\d{2})/', $timeStr, $matches)) {
            $hour = (int) $matches[1];
            $minute = $matches[2];
            $ampm = $hour >= 12 ? 'PM' : 'AM';
            $hour12 = $hour % 12 ?: 12;

            $formatted = sprintf('%d:%s %s', $hour12, $minute, $ampm);
        } else {
            $formatted = htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8');
        }

        // Add prefix/suffix for display
        $prefix = $fieldConfig['prefix'] ?? '';
        $suffix = $fieldConfig['suffix'] ?? '';

        if ($prefix !== '') {
            $formatted = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . ' ' . $formatted;
        }

        if ($suffix !== '') {
            $formatted .= ' ' . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8');
        }

        return $formatted;
    }

    public function getDefaultConfig(): array
    {
        return [
            'format' => '24h',
            'step' => 1,
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'format' => [
                'type' => 'select',
                'label' => 'Time Format',
                'options' => [
                    ['value' => '24h', 'label' => '24 Hour (14:30)'],
                    ['value' => '12h', 'label' => '12 Hour (2:30 PM)'],
                ],
                'default' => '24h',
            ],
            'step' => [
                'type' => 'select',
                'label' => 'Minute Step',
                'options' => [
                    ['value' => 1, 'label' => '1 minute'],
                    ['value' => 5, 'label' => '5 minutes'],
                    ['value' => 15, 'label' => '15 minutes'],
                    ['value' => 30, 'label' => '30 minutes'],
                ],
                'default' => 1,
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        return false;
    }

    public function getCategory(): string
    {
        return 'content';
    }

    public function getIcon(): string
    {
        return 'schedule';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $timeValue = $this->denormalizeValue($value, $config);

        return $this->renderPartial('time.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $timeValue,
            'context' => $context,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        return sprintf(
            '<input type="time" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}">',
            $this->escapeAttr($slug)
        );
    }
}
