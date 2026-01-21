<?php

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

/**
 * Interface for all ACF field types.
 *
 * This interface defines the contract that all field types must implement.
 * Field types handle:
 * - Form generation for back-office
 * - Value normalization for storage
 * - Value rendering for front-office
 * - Validation rules
 */
interface FieldTypeInterface
{
    /**
     * Get the unique type identifier.
     *
     * This is used in the database and for registration.
     * Examples: 'text', 'number', 'select', 'richtext'
     */
    public function getType(): string;

    /**
     * Get human-readable label for this field type.
     *
     * Used in the admin UI when selecting field types.
     */
    public function getLabel(): string;

    /**
     * Get the Symfony form type class for this field.
     *
     * @return class-string The fully qualified class name of the form type
     */
    public function getFormType(): string;

    /**
     * Get form options for Symfony form building.
     *
     * @param array<string, mixed> $fieldConfig Field configuration from database
     * @param array<string, mixed> $validation Validation rules from database
     *
     * @return array<string, mixed> Symfony form options
     */
    public function getFormOptions(array $fieldConfig, array $validation = []): array;

    /**
     * Normalize value before storing in database.
     *
     * Convert form input to storage format.
     * Examples:
     * - Number: convert string to float
     * - Select multiple: convert array to JSON
     * - Checkbox: convert to boolean
     *
     * @param mixed $value Raw value from form
     * @param array<string, mixed> $fieldConfig Field configuration
     *
     * @return mixed Normalized value for storage
     */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed;

    /**
     * Denormalize value after loading from database.
     *
     * Convert stored value back to usable format.
     *
     * @param mixed $value Value from database
     * @param array<string, mixed> $fieldConfig Field configuration
     *
     * @return mixed Value ready for use in templates/forms
     */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed;

    /**
     * Render value for front-office display.
     *
     * @param mixed $value The field value
     * @param array<string, mixed> $fieldConfig Field configuration
     * @param array<string, mixed> $renderOptions Rendering options (format, locale, etc.)
     *
     * @return string HTML-safe rendered output
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string;

    /**
     * Get value for search indexing.
     *
     * Return a simplified string value for the value_index column.
     * Used for searching and sorting.
     *
     * @param mixed $value The field value
     * @param array<string, mixed> $fieldConfig Field configuration
     *
     * @return string|null Indexable string or null if not indexable
     */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string;

    /**
     * Get default configuration for this field type.
     *
     * Used when creating new fields of this type.
     *
     * @return array<string, mixed> Default configuration values
     */
    public function getDefaultConfig(): array;

    /**
     * Get available configuration schema.
     *
     * Describes what options can be configured for this field type.
     * Used by the admin UI to build the field configuration form.
     *
     * @return array<string, array<string, mixed>> Configuration schema
     */
    public function getConfigSchema(): array;

    /**
     * Validate a value according to field rules.
     *
     * @param mixed $value Value to validate
     * @param array<string, mixed> $fieldConfig Field configuration
     * @param array<string, mixed> $validation Validation rules
     *
     * @return array<string> Array of error messages (empty if valid)
     */
    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array;

    /**
     * Check if this field type supports translation.
     */
    public function supportsTranslation(): bool;

    /**
     * Get the category/group for this field type.
     *
     * Used for organizing field types in the admin UI.
     * Examples: 'basic', 'content', 'choice', 'relational', 'layout'
     */
    public function getCategory(): string;

    /**
     * Get icon identifier for this field type.
     *
     * Used in the admin UI. Can be a Material Icons name or custom icon class.
     */
    public function getIcon(): string;



    /**
     * Get JavaScript template for dynamic row creation in repeaters.
     *
     * Returns an HTML template string with {value} placeholder that will
     * be replaced by JavaScript when creating new repeater rows.
     *
     * @param array<string, mixed> $field Field definition
     *
     * @return string JavaScript template string
     */
    public function getJsTemplate(array $field): string;
}
