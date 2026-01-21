<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * Copyright since 2024 WeCode.
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


if (!defined('_PS_VERSION_')) {
    exit;
}

use DateTimeInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Tools;

/**
 * Date field type.
 *
 * Stores dates as Unix timestamps, displays using PrestaShop date format.
 */
final class DateField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'date';
    }

    public function getLabel(): string
    {
        return 'Date';
    }

    public function getFormType(): string
    {
        return DateType::class;
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
        if ($value instanceof DateTimeInterface) {
            return (string) $value->getTimestamp();
        }

        // Parse date string (Y-m-d or other formats)
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

        // Convert timestamp to Y-m-d string for HTML5 date input
        if (is_numeric($value)) {
            return date('Y-m-d', (int) $value);
        }

        // DateTime object - format to string
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Already a string in correct format
        if (\is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // Try to parse and format
        $timestamp = strtotime((string) $value);

        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return '';
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

        // Get timestamp
        $timestamp = is_numeric($actualValue) ? (int) $actualValue : (
            $actualValue instanceof DateTimeInterface ? $actualValue->getTimestamp() : strtotime((string) $actualValue)
        );

        if ($timestamp === false || $timestamp === 0) {
            return '';
        }

        // Use PrestaShop's date formatting
        if (\function_exists('Tools::displayDate')) {
            $formatted = Tools::displayDate(date('Y-m-d', $timestamp));
        } else {
            // Fallback to standard format
            $format = $this->getConfigValue($fieldConfig, 'displayFormat', 'd/m/Y');
            $formatted = date($format, $timestamp);
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

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $normalized = $this->normalizeValue($value, $fieldConfig);

        return $normalized !== null ? (string) $normalized : null;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        // Validate min date
        $minDate = $this->getConfigValue($fieldConfig, 'minDate');

        if ($minDate) {
            $minTimestamp = strtotime($minDate);
            $valueTimestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

            if ($minTimestamp && $valueTimestamp && $valueTimestamp < $minTimestamp) {
                $errors[] = \sprintf('Date must be on or after %s.', $minDate);
            }
        }

        // Validate max date
        $maxDate = $this->getConfigValue($fieldConfig, 'maxDate');

        if ($maxDate) {
            $maxTimestamp = strtotime($maxDate);
            $valueTimestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

            if ($maxTimestamp && $valueTimestamp && $valueTimestamp > $maxTimestamp) {
                $errors[] = \sprintf('Date must be on or before %s.', $maxDate);
            }
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'minDate' => '',
            'maxDate' => '',
            'displayFormat' => 'd/m/Y',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'minDate' => [
                'type' => 'date',
                'label' => 'Minimum Date',
                'help' => 'Earliest selectable date',
                'default' => '',
            ],
            'maxDate' => [
                'type' => 'date',
                'label' => 'Maximum Date',
                'help' => 'Latest selectable date',
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
        return 'calendar_today';
    }



    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        return \sprintf(
            '<input type="date" class="form-control form-control-sm acf-subfield-input" data-subfield="%s" value="{value}">',
            $this->escapeAttr($slug)
        );
    }
}
