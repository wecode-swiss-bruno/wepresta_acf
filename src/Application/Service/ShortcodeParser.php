<?php

/**
 * ACF Shortcode Parser.
 *
 * Parses WordPress-style shortcodes in WYSIWYG content (CMS pages, product descriptions).
 *
 * Supported shortcodes:
 *   [acf field="brand"]
 *   [acf field="brand" default="N/A"]
 *   [acf field="brand" entity_type="product" entity_id="123"]
 *   [acf_render field="image"]
 *   [acf_group id="1"]
 *   [acf_repeater slug="specs"]{row.label}: {row.value}[/acf_repeater]
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use PrestaShopLogger;
use Throwable;

final class ShortcodeParser
{
    /** Regex patterns for shortcodes. */
    private const PATTERN_SIMPLE = '/\[acf\s+([^\]]+)\]/i';

    private const PATTERN_RENDER = '/\[acf_render\s+([^\]]+)\]/i';

    private const PATTERN_GROUP = '/\[acf_group\s+([^\]]+)\]/i';

    private const PATTERN_REPEATER = '/\[acf_repeater\s+([^\]]+)\](.*?)\[\/acf_repeater\]/is';

    public function __construct(
        private readonly AcfFrontService $frontService,
        private readonly FieldRenderer $fieldRenderer
    ) {
    }

    /**
     * Parse all shortcodes in content.
     *
     * @param string $content Content with shortcodes
     * @param string|null $entityType Default entity type
     * @param int|null $entityId Default entity ID
     *
     * @return string Content with shortcodes replaced
     */
    public function parse(string $content, ?string $entityType = null, ?int $entityId = null): string
    {
        if (strpos($content, '[acf') === false) {
            return $content;
        }

        // Parse repeaters first (they may contain other shortcodes)
        $content = $this->parseRepeaters($content, $entityType, $entityId);

        // Parse simple shortcodes
        $content = $this->parseSimple($content, $entityType, $entityId);

        // Parse render shortcodes
        $content = $this->parseRender($content, $entityType, $entityId);

        // Parse group shortcodes
        $content = $this->parseGroup($content, $entityType, $entityId);

        return $content;
    }

    /**
     * Parse [acf field="..."] shortcodes.
     */
    private function parseSimple(string $content, ?string $entityType, ?int $entityId): string
    {
        return preg_replace_callback(
            self::PATTERN_SIMPLE,
            function (array $matches) use ($entityType, $entityId): string {
                return $this->handleSimpleShortcode($matches[1], $entityType, $entityId);
            },
            $content
        ) ?? $content;
    }

    /**
     * Parse [acf_render field="..."] shortcodes.
     */
    private function parseRender(string $content, ?string $entityType, ?int $entityId): string
    {
        return preg_replace_callback(
            self::PATTERN_RENDER,
            function (array $matches) use ($entityType, $entityId): string {
                return $this->handleRenderShortcode($matches[1], $entityType, $entityId);
            },
            $content
        ) ?? $content;
    }

    /**
     * Parse [acf_group id="..."] shortcodes.
     */
    private function parseGroup(string $content, ?string $entityType, ?int $entityId): string
    {
        return preg_replace_callback(
            self::PATTERN_GROUP,
            function (array $matches) use ($entityType, $entityId): string {
                return $this->handleGroupShortcode($matches[1], $entityType, $entityId);
            },
            $content
        ) ?? $content;
    }

    /**
     * Parse [acf_repeater slug="..."]...[/acf_repeater] shortcodes.
     */
    private function parseRepeaters(string $content, ?string $entityType, ?int $entityId): string
    {
        return preg_replace_callback(
            self::PATTERN_REPEATER,
            function (array $matches) use ($entityType, $entityId): string {
                return $this->handleRepeaterShortcode($matches[1], $matches[2], $entityType, $entityId);
            },
            $content
        ) ?? $content;
    }

    // =========================================================================
    // SHORTCODE HANDLERS
    // =========================================================================

    /**
     * Handle [acf field="..."] shortcode.
     */
    private function handleSimpleShortcode(string $attrs, ?string $entityType, ?int $entityId): string
    {
        try {
            $params = $this->parseAttributes($attrs);
            $field = $params['field'] ?? '';

            if ($field === '') {
                return '';
            }

            $service = $this->getService($params, $entityType, $entityId);
            $default = $params['default'] ?? '';

            $value = $service->field($field, $default);

            // Convert arrays to string
            if (\is_array($value)) {
                return implode(', ', array_map('strval', $value));
            }

            return (string) $value;
        } catch (Throwable $e) {
            return $this->handleError('acf', $e);
        }
    }

    /**
     * Handle [acf_render field="..."] shortcode.
     */
    private function handleRenderShortcode(string $attrs, ?string $entityType, ?int $entityId): string
    {
        try {
            $params = $this->parseAttributes($attrs);
            $field = $params['field'] ?? '';

            if ($field === '') {
                return '';
            }

            $service = $this->getService($params, $entityType, $entityId);

            return $service->render($field);
        } catch (Throwable $e) {
            return $this->handleError('acf_render', $e);
        }
    }

    /**
     * Handle [acf_group id="..."] shortcode.
     */
    private function handleGroupShortcode(string $attrs, ?string $entityType, ?int $entityId): string
    {
        try {
            $params = $this->parseAttributes($attrs);
            $groupId = $params['id'] ?? $params['slug'] ?? '';

            if ($groupId === '') {
                return '';
            }

            $service = $this->getService($params, $entityType, $entityId);

            // Convert to int if numeric
            $groupIdOrSlug = is_numeric($groupId) ? (int) $groupId : $groupId;
            $fields = $service->getGroupFields($groupIdOrSlug);

            if (empty($fields)) {
                return '';
            }

            return $this->fieldRenderer->renderGroup($fields, [
                'showTitles' => isset($params['show_titles']) && $params['show_titles'] !== 'false',
            ]);
        } catch (Throwable $e) {
            return $this->handleError('acf_group', $e);
        }
    }

    /**
     * Handle [acf_repeater slug="..."]...[/acf_repeater] shortcode.
     */
    private function handleRepeaterShortcode(string $attrs, string $template, ?string $entityType, ?int $entityId): string
    {
        try {
            $params = $this->parseAttributes($attrs);
            $slug = $params['slug'] ?? $params['repeater'] ?? '';

            if ($slug === '') {
                return '';
            }

            $service = $this->getService($params, $entityType, $entityId);
            $rows = $service->getRepeaterRows($slug);

            if (empty($rows)) {
                return '';
            }

            $output = '';

            foreach ($rows as $index => $row) {
                $rowHtml = $template;

                // Replace {row.field_name} placeholders
                $rowHtml = preg_replace_callback(
                    '/\{row\.([a-zA-Z0-9_]+)\}/',
                    function (array $matches) use ($row): string {
                        $key = $matches[1];
                        $value = $row[$key] ?? '';

                        if (\is_array($value)) {
                            return json_encode($value);
                        }

                        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                    },
                    $rowHtml
                );

                // Replace {row._index} placeholder
                $rowHtml = str_replace('{row._index}', (string) $index, $rowHtml ?? '');

                $output .= $rowHtml;
            }

            return $output;
        } catch (Throwable $e) {
            return $this->handleError('acf_repeater', $e);
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Parse shortcode attributes from string.
     *
     * @return array<string, string>
     */
    private function parseAttributes(string $attrs): array
    {
        $result = [];

        // Match attribute="value" or attribute='value' or attribute=value
        preg_match_all(
            '/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))/',
            $attrs,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $key = $match[1];
            // Value can be in group 2 (double quotes), 3 (single quotes), or 4 (no quotes)
            $value = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : ($match[4] ?? ''));
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Get service with context from params.
     */
    private function getService(array $params, ?string $entityType, ?int $entityId): AcfFrontService
    {
        $service = $this->frontService;

        // Override from params
        $overrideType = $params['entity_type'] ?? $entityType;
        $overrideId = isset($params['entity_id']) ? (int) $params['entity_id'] : $entityId;

        if ($overrideType !== null && $overrideId !== null) {
            $service = $service->forEntity($overrideType, $overrideId);
        }

        return $service;
    }

    /**
     * Handle error with optional debug output.
     */
    private function handleError(string $shortcode, Throwable $e): string
    {
        if (\defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            PrestaShopLogger::addLog(
                "[ACF Shortcode] Error in [{$shortcode}]: " . $e->getMessage(),
                2,
                null,
                'ShortcodeParser',
                0
            );

            return '<!-- ACF Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
        }

        return '';
    }
}
