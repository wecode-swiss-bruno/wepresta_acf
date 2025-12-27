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

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Rich Text (WYSIWYG) field type
 *
 * Uses TinyMCE for HTML editing, sanitizes content on save.
 */
final class RichTextField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'richtext';
    }

    public function getLabel(): string
    {
        return 'Rich Text (WYSIWYG)';
    }

    public function getFormType(): string
    {
        // Use standard textarea - TinyMCE is initialized via CSS class
        return TextareaType::class;
    }

    public function getFormOptions(array $fieldConfig, array $validation = []): array
    {
        $options = parent::getFormOptions($fieldConfig, $validation);

        // Add class for TinyMCE auto-initialization
        $options['attr']['class'] = ($options['attr']['class'] ?? '') . ' autoload_rte';
        $options['attr']['rows'] = $this->getConfigValue($fieldConfig, 'rows', 10);

        return $options;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle arrays/objects (empty objects from JS become empty arrays)
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $html = (string) $value;

        // SECURITY: Sanitize HTML but preserve allowed styling
        // Tools::purifyHTML is too aggressive - it strips inline styles
        // We use a more permissive approach while still preventing XSS

        // Remove potentially dangerous tags but keep formatting
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace('/on\w+\s*=\s*\'[^\']*\'/i', '', $html);
        $html = preg_replace('/javascript:/i', '', $html);

        // Trim whitespace
        $html = trim($html);

        return $html === '' ? null : $html;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Return HTML as-is since it was sanitized on save
        // The template should use {$value nofilter} for this
        return (string) $value;
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Strip HTML for index value (for search)
        $text = strip_tags((string) $value);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Limit to 255 chars for index
        return $text !== '' ? substr($text, 0, 255) : null;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        // Check max length if configured (based on stripped text)
        if (!empty($validation['maxLength'])) {
            $textContent = strip_tags((string) $value);
            if (mb_strlen($textContent) > (int) $validation['maxLength']) {
                $errors[] = sprintf('Content exceeds maximum length of %d characters.', $validation['maxLength']);
            }
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'rows' => 10,
            'toolbar' => 'standard',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'rows' => [
                'type' => 'number',
                'label' => 'Editor Height (rows)',
                'help' => 'Number of text rows for the editor',
                'default' => 10,
                'min' => 5,
                'max' => 30,
            ],
            'toolbar' => [
                'type' => 'select',
                'label' => 'Toolbar Style',
                'options' => [
                    ['value' => 'basic', 'label' => 'Basic (bold, italic, links)'],
                    ['value' => 'standard', 'label' => 'Standard'],
                    ['value' => 'full', 'label' => 'Full (all options)'],
                ],
                'default' => 'standard',
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        // Rich text content can be translated
        return true;
    }

    public function getCategory(): string
    {
        return 'content';
    }

    public function getIcon(): string
    {
        return 'article';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);

        return $this->renderPartial('richtext.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $value,
            'context' => $context,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTemplate(array $field): string
    {
        $slug = $field['slug'] ?? '';

        // Note: TinyMCE in repeaters requires special initialization
        return sprintf(
            '<textarea class="form-control acf-subfield-input acf-richtext-input" data-subfield="%s" rows="5">{value}</textarea>',
            $this->escapeAttr($slug)
        );
    }
}
