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

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * DateTime field type
 *
 * Combines date and time, stores as Unix timestamp.
 */
final class DatetimeField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'datetime';
    }

    public function getLabel(): string
    {
        return 'Date & Time';
    }

    public function getFormType(): string
    {
        return DateTimeType::class;
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

        // Already a timestamp
        if (is_numeric($value)) {
            return (string) (int) $value;
        }

        // DateTime object
        if ($value instanceof \DateTimeInterface) {
            return (string) $value->getTimestamp();
        }

        // Parse datetime string
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return (string) $timestamp;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Convert timestamp to datetime-local format for HTML5 input
        if (is_numeric($value)) {
            return date('Y-m-d\TH:i', (int) $value);
        }

        // DateTime object - format to string
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d\TH:i');
        }

        // Already a string in correct format
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            return $value;
        }

        // Try to parse and format
        $timestamp = strtotime((string) $value);
        if ($timestamp !== false) {
            return date('Y-m-d\TH:i', $timestamp);
        }

        return '';
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Get timestamp
        $timestamp = is_numeric($value) ? (int) $value : (
            $value instanceof \DateTimeInterface ? $value->getTimestamp() : strtotime((string) $value)
        );

        if ($timestamp === false || $timestamp === 0) {
            return '';
        }

        // Use PrestaShop's date formatting + time
        if (function_exists('Tools::displayDate')) {
            return \Tools::displayDate(date('Y-m-d H:i:s', $timestamp), null, true);
        }

        // Fallback format
        $dateFormat = $this->getConfigValue($fieldConfig, 'dateFormat', 'd/m/Y');
        $timeFormat = $this->getConfigValue($fieldConfig, 'timeFormat', 'H:i');

        return date($dateFormat . ' ' . $timeFormat, $timestamp);
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $normalized = $this->normalizeValue($value, $fieldConfig);

        return $normalized !== null ? (string) $normalized : null;
    }

    public function getDefaultConfig(): array
    {
        return [
            'minDate' => '',
            'maxDate' => '',
            'dateFormat' => 'd/m/Y',
            'timeFormat' => 'H:i',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'minDate' => [
                'type' => 'datetime',
                'label' => 'Minimum Date/Time',
                'default' => '',
            ],
            'maxDate' => [
                'type' => 'datetime',
                'label' => 'Maximum Date/Time',
                'default' => '',
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
        return 'event';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $datetimeValue = $this->denormalizeValue($value, $config);

        return $this->renderPartial('datetime.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $datetimeValue,
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
            '<input type="datetime-local" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}">',
            $this->escapeAttr($slug)
        );
    }
}
