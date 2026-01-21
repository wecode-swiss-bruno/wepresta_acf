<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

/**
 * ACF Field Renderer.
 *
 * Renders field values as HTML using Smarty templates.
 * Each field type has its own template in views/templates/front/fields/.
 *
 * Templates can be overridden in themes:
 * /themes/{theme}/modules/wepresta_acf/views/templates/front/fields/{type}.tpl
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Category;
use CMS;
use Context;
use DateTime;
use Exception;
use ImageType;
use Manufacturer;
use Product;
use Smarty;
use Supplier;
use Throwable;
use Validate;

final class FieldRenderer
{
    private const MODULE_NAME = 'wepresta_acf';

    /** @var array<string, string> Cache of resolved template paths */
    private array $templateCache = [];

    /**
     * Render a field value as HTML.
     *
     * @param array<string, mixed> $fieldDef Field definition from database
     * @param mixed $value Field value
     * @param array<string, mixed> $options Render options
     *
     * @return string Rendered HTML
     */
    public function render(array $fieldDef, mixed $value, array $options = []): string
    {
        $type = $fieldDef['type'] ?? 'text';
        $config = $this->parseConfig($fieldDef['config'] ?? []);
        $foOptions = $this->parseConfig($fieldDef['fo_options'] ?? []);

        // Enrich relation data (transform IDs to objects with name/link)
        if ($type === 'relation' && $value !== null && $value !== '') {
            $value = $this->enrichRelationData($value, $config);
        }

        // Prepare template variables
        $vars = [
            'field' => $fieldDef,
            'value' => $value,
            'config' => $config,
            'foOptions' => $foOptions,
            'options' => $options,
            'slug' => $fieldDef['slug'] ?? '',
            'title' => $fieldDef['title'] ?? '',
            'type' => $type,
            'showTitle' => $foOptions['showTitle'] ?? false,
            'customClass' => $foOptions['customClass'] ?? '',
            'customId' => $foOptions['customId'] ?? '',
            'lang_id' => $this->getCurrentLangId(),
        ];

        // Try to render with type-specific template
        $html = $this->renderTemplate($type, $vars);

        // If no template found, use default renderer
        if ($html === null) {
            $html = $this->renderDefault($type, $value, $config, $foOptions);
        }

        return $html;
    }

    /**
     * Render a field with wrapper (includes title, instructions, CSS classes).
     *
     * @param array<string, mixed> $fieldDef Field definition
     * @param mixed $value Field value
     * @param array<string, mixed> $options Render options
     *
     * @return string Rendered HTML with wrapper
     */
    public function renderWithWrapper(array $fieldDef, mixed $value, array $options = []): string
    {
        $innerHtml = $this->render($fieldDef, $value, $options);

        if ($innerHtml === '') {
            return '';
        }

        $foOptions = $this->parseConfig($fieldDef['fo_options'] ?? []);
        $showTitle = $foOptions['showTitle'] ?? false;
        $customClass = $foOptions['customClass'] ?? '';
        $customId = $foOptions['customId'] ?? '';

        $vars = [
            'field' => $fieldDef,
            'innerHtml' => $innerHtml,
            'showTitle' => $showTitle,
            'title' => $fieldDef['title'] ?? '',
            'instructions' => $fieldDef['instructions'] ?? '',
            'type' => $fieldDef['type'] ?? 'text',
            'slug' => $fieldDef['slug'] ?? '',
            'customClass' => $customClass,
            'customId' => $customId,
        ];

        $wrapped = $this->renderTemplate('wrapper', $vars);

        return $wrapped ?? $innerHtml;
    }

    /**
     * Render a complete group of fields.
     *
     * @param array<int, array<string, mixed>> $fields Array of field definitions with values
     * @param array<string, mixed> $options Render options
     *
     * @return string Rendered HTML
     */
    public function renderGroup(array $fields, array $options = []): string
    {
        $vars = [
            'fields' => $fields,
            'options' => $options,
        ];

        $html = $this->renderTemplate('group', $vars);

        if ($html !== null) {
            return $html;
        }

        // Fallback: render each field
        $output = '<div class="acf-group">';

        foreach ($fields as $field) {
            $output .= $field['rendered'] ?? '';
        }
        $output .= '</div>';

        return $output;
    }

    // =========================================================================
    // TYPE-SPECIFIC RENDERERS (fallback when no template)
    // =========================================================================

    /**
     * Default renderer when no template is found.
     *
     * @param array<string, mixed> $config Field config
     * @param array<string, mixed> $foOptions Front-office options
     */
    private function renderDefault(string $type, mixed $value, array $config, array $foOptions): string
    {
        return match ($type) {
            'text', 'textarea', 'number', 'email' => $this->renderText($value),
            'richtext' => $this->renderRichText($value),
            'url' => $this->renderUrl($value, $config),
            'image' => $this->renderImage($value, $config),
            'gallery' => $this->renderGallery($value, $config),
            'video' => $this->renderVideo($value, $config),
            'file' => $this->renderFile($value, $config),
            'select', 'radio' => $this->renderSelect($value, $config),
            'checkbox' => $this->renderCheckbox($value, $config),
            'boolean' => $this->renderBoolean($value),
            'date' => $this->renderDate($value, $config),
            'datetime' => $this->renderDateTime($value, $config),
            'time' => $this->renderTime($value),
            'color' => $this->renderColor($value),
            'star_rating' => $this->renderStarRating($value, $config),
            'list' => $this->renderList($value),
            'relation' => $this->renderRelation($value, $config),
            'repeater' => $this->renderRepeater($value),
            default => $this->renderText($value),
        };
    }

    private function renderText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '<span class="acf-text">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    private function renderRichText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // RichText should not be escaped - it contains HTML
        return '<div class="acf-richtext">' . $value . '</div>';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderUrl(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $url = (string) $value;
        $target = ($config['openInNewTab'] ?? false) ? ' target="_blank" rel="noopener"' : '';
        $text = $config['linkText'] ?? $url;

        return \sprintf(
            '<a href="%s" class="acf-link"%s>%s</a>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $target,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderImage(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Value can be string (URL) or array (with metadata)
        if (\is_array($value)) {
            $url = $value['url'] ?? '';
            $alt = $value['alt'] ?? $value['title'] ?? '';
        } else {
            $url = (string) $value;
            $alt = '';
        }

        if ($url === '') {
            return '';
        }

        $width = isset($config['width']) ? ' width="' . (int) $config['width'] . '"' : '';
        $height = isset($config['height']) ? ' height="' . (int) $config['height'] . '"' : '';

        return \sprintf(
            '<img src="%s" alt="%s" class="acf-image"%s%s loading="lazy">',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
            $width,
            $height
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderGallery(mixed $value, array $config): string
    {
        if (! \is_array($value) || empty($value)) {
            return '';
        }

        $html = '<div class="acf-gallery">';

        foreach ($value as $item) {
            if (\is_array($item)) {
                $url = $item['url'] ?? '';
                $alt = $item['alt'] ?? $item['title'] ?? '';
            } else {
                $url = (string) $item;
                $alt = '';
            }

            if ($url === '') {
                continue;
            }

            $html .= \sprintf(
                '<div class="acf-gallery-item"><img src="%s" alt="%s" loading="lazy"></div>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($alt, ENT_QUOTES, 'UTF-8')
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderVideo(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Handle array value (with source, video_id, poster, etc.)
        if (\is_array($value)) {
            $source = $value['source'] ?? 'upload';
            $videoId = $value['video_id'] ?? '';
            $url = $value['url'] ?? '';
            $poster = $value['poster_url'] ?? '';

            // YouTube
            if ($source === 'youtube' && $videoId !== '') {
                return \sprintf(
                    '<div class="acf-video acf-video--youtube"><iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
                    htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8')
                );
            }

            // Vimeo
            if ($source === 'vimeo' && $videoId !== '') {
                return \sprintf(
                    '<div class="acf-video acf-video--vimeo"><iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
                    htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8')
                );
            }

            // Direct video
            if ($url !== '') {
                $posterAttr = $poster !== '' ? ' poster="' . htmlspecialchars($poster, ENT_QUOTES, 'UTF-8') . '"' : '';

                return \sprintf(
                    '<div class="acf-video"><video src="%s" controls%s></video></div>',
                    htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                    $posterAttr
                );
            }
        }

        // Simple URL value
        $url = (string) $value;

        // Try to detect YouTube/Vimeo from URL
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $m)) {
            return \sprintf(
                '<div class="acf-video acf-video--youtube"><iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
                htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')
            );
        }

        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]+\/)?videos\/|video\/|)(\d+)/i', $url, $m)) {
            return \sprintf(
                '<div class="acf-video acf-video--vimeo"><iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
                htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')
            );
        }

        // Default: HTML5 video
        return \sprintf(
            '<div class="acf-video"><video src="%s" controls></video></div>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderFile(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (\is_array($value)) {
            $url = $value['url'] ?? '';
            $title = $value['title'] ?? $value['original_name'] ?? basename($url);
        } else {
            $url = (string) $value;
            $title = basename($url);
        }

        if ($url === '') {
            return '';
        }

        return \sprintf(
            '<a href="%s" class="acf-file" download>%s</a>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderSelect(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Get label from choices with translation support
        $choices = $config['choices'] ?? [];
        $label = $this->resolveChoiceLabel($value, $choices);

        return '<span class="acf-select">' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderCheckbox(mixed $value, array $config): string
    {
        if (! \is_array($value) || empty($value)) {
            return '';
        }

        $choices = $config['choices'] ?? [];
        $labels = [];

        foreach ($value as $val) {
            $label = $this->resolveChoiceLabel($val, $choices);
            $labels[] = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        }

        return '<span class="acf-checkbox">' . implode(', ', $labels) . '</span>';
    }

    /**
     * Resolve a choice value to its translated label.
     *
     * @param mixed $value Choice value to resolve
     * @param array<int, array<string, mixed>> $choices Available choices
     *
     * @return string Resolved label or original value
     */
    private function resolveChoiceLabel(mixed $value, array $choices): string
    {
        if (! \is_string($value) && ! is_numeric($value)) {
            return (string) $value;
        }

        $langId = $this->getCurrentLangId();

        foreach ($choices as $choice) {
            if (! \is_array($choice)) {
                continue;
            }

            if (($choice['value'] ?? '') === $value) {
                // Check for translation first
                $translations = $choice['translations'] ?? [];

                // Keys in translations are strings ("1", "2"), not integers
                $langKey = (string) $langId;

                if (isset($translations[$langKey]) && $translations[$langKey] !== '') {
                    return (string) $translations[$langKey];
                }

                // Fallback to label
                if (! empty($choice['label'])) {
                    return (string) $choice['label'];
                }

                // Fallback to value
                return (string) $value;
            }
        }

        return (string) $value;
    }

    /**
     * Get current language ID from context.
     */
    private function getCurrentLangId(): int
    {
        $context = Context::getContext();

        return (int) ($context->language->id ?? 1);
    }

    private function renderBoolean(mixed $value): string
    {
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return '<span class="acf-boolean acf-boolean--' . ($bool ? 'true' : 'false') . '">' .
            ($bool ? '✓' : '✗') . '</span>';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderDate(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $format = $config['displayFormat'] ?? 'd/m/Y';

        try {
            $date = new DateTime($value);

            return '<span class="acf-date">' . $date->format($format) . '</span>';
        } catch (Exception) {
            return '<span class="acf-date">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderDateTime(mixed $value, array $config): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $format = $config['displayFormat'] ?? 'd/m/Y H:i';

        try {
            $date = new DateTime($value);

            return '<span class="acf-datetime">' . $date->format($format) . '</span>';
        } catch (Exception) {
            return '<span class="acf-datetime">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    private function renderTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '<span class="acf-time">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    private function renderColor(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $color = (string) $value;

        return \sprintf(
            '<span class="acf-color" style="background-color: %s;" title="%s"></span>',
            htmlspecialchars($color, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($color, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderStarRating(mixed $value, array $config): string
    {
        $rating = (float) $value;
        $max = (int) ($config['max'] ?? 5);

        $html = '<span class="acf-star-rating">';

        for ($i = 1; $i <= $max; ++$i) {
            $class = $i <= $rating ? 'acf-star--filled' : 'acf-star--empty';
            $html .= '<span class="acf-star ' . $class . '">★</span>';
        }
        $html .= '</span>';

        return $html;
    }

    private function renderList(mixed $value): string
    {
        if (! \is_array($value) || empty($value)) {
            return '';
        }

        $html = '<ul class="acf-list">';

        foreach ($value as $item) {
            $html .= '<li>' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderRelation(mixed $value, array $config): string
    {
        if ($value === null || $value === '' || (\is_array($value) && empty($value))) {
            return '';
        }

        // Single relation
        if (! \is_array($value) || isset($value['id'])) {
            $item = \is_array($value) ? $value : ['id' => $value, 'name' => "#{$value}"];

            return \sprintf(
                '<span class="acf-relation">%s</span>',
                htmlspecialchars($item['name'] ?? "#{$item['id']}", ENT_QUOTES, 'UTF-8')
            );
        }

        // Multiple relations
        $html = '<ul class="acf-relations">';

        foreach ($value as $item) {
            if (! \is_array($item)) {
                continue;
            }
            $html .= \sprintf(
                '<li>%s</li>',
                htmlspecialchars($item['name'] ?? "#{$item['id']}", ENT_QUOTES, 'UTF-8')
            );
        }
        $html .= '</ul>';

        return $html;
    }

    private function renderRepeater(mixed $value): string
    {
        if (! \is_array($value) || empty($value)) {
            return '';
        }

        // Repeaters should be iterated in templates, not rendered directly
        return '<div class="acf-repeater" data-rows="' . \count($value) . '">[Repeater: use $acf->repeater() to iterate]</div>';
    }

    // =========================================================================
    // TEMPLATE METHODS
    // =========================================================================

    /**
     * Render using Smarty template.
     *
     * @param array<string, mixed> $vars Template variables
     *
     * @return string|null Rendered HTML or null if template not found
     */
    private function renderTemplate(string $templateName, array $vars): ?string
    {
        $templatePath = $this->resolveTemplatePath($templateName);

        if ($templatePath === null) {
            return null;
        }

        try {
            $smarty = $this->getSmarty();

            if ($smarty === null) {
                return null;
            }

            // Assign variables
            foreach ($vars as $key => $value) {
                $smarty->assign($key, $value);
            }

            return $smarty->fetch($templatePath);
        } catch (Throwable $e) {
            if (\defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
                return '<!-- ACF Template Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
            }

            return null;
        }
    }

    /**
     * Resolve template path with theme override support.
     */
    private function resolveTemplatePath(string $templateName): ?string
    {
        if (isset($this->templateCache[$templateName])) {
            $cached = $this->templateCache[$templateName];

            return $cached !== '' ? $cached : null;
        }

        $context = Context::getContext();
        $themeName = $context->shop->theme_name ?? 'classic';

        // Paths to check (theme override first)
        $paths = [
            // Theme override
            _PS_THEME_DIR_ . 'modules/' . self::MODULE_NAME . '/views/templates/front/fields/' . $templateName . '.tpl',
            // Module path
            _PS_MODULE_DIR_ . self::MODULE_NAME . '/views/templates/front/fields/' . $templateName . '.tpl',
        ];

        // For wrapper and group templates
        if ($templateName === 'wrapper' || $templateName === 'group') {
            $paths = [
                _PS_THEME_DIR_ . 'modules/' . self::MODULE_NAME . '/views/templates/front/' . $templateName . '.tpl',
                _PS_MODULE_DIR_ . self::MODULE_NAME . '/views/templates/front/' . $templateName . '.tpl',
            ];
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->templateCache[$templateName] = $path;

                return $path;
            }
        }

        $this->templateCache[$templateName] = '';

        return null;
    }

    private function getSmarty(): ?Smarty
    {
        $context = Context::getContext();

        return $context->smarty ?? null;
    }

    /**
     * Enrich relation data by converting IDs to objects with name and link.
     *
     * @param mixed $value Raw value (ID, array of IDs, or already enriched)
     * @param array<string, mixed> $config Field configuration
     *
     * @return mixed Enriched data
     */
    private function enrichRelationData(mixed $value, array $config): mixed
    {
        $entityType = $config['entityType'] ?? 'product';
        $multiple = $config['multiple'] ?? false;
        $langId = $this->getCurrentLangId();

        // Convert string "4,2" to array [4, 2]
        if (\is_string($value) && !empty($value)) {
            $value = array_map('intval', array_filter(array_map('trim', explode(',', $value))));
        }

        // Handle array of IDs
        if (\is_array($value)) {
            // Check if already enriched (has 'id' key in first item)
            if (! empty($value) && isset($value[0]) && \is_array($value[0]) && isset($value[0]['id'])) {
                return $value;
            }

            // Array of IDs - enrich each
            return array_filter(array_map(
                fn ($id) => $this->getEntityData($entityType, (int) $id, $langId),
                $value
            ));
        }

        // Single ID
        if (is_numeric($value)) {
            $enriched = $this->getEntityData($entityType, (int) $value, $langId);

            return $multiple ? [$enriched] : $enriched;
        }

        // Already enriched or invalid
        return $value;
    }

    /**
     * Get entity data (name, link, reference, image) for a given entity type and ID.
     *
     * @return array{id: int, name: string, link: string, reference: string, image: string, type: string}|null
     */
    private function getEntityData(string $entityType, int $entityId, int $langId): ?array
    {
        if ($entityId <= 0) {
            return null;
        }

        $context = Context::getContext();

        try {
            switch ($entityType) {
                case 'product':
                    $product = new Product($entityId, false, $langId);

                    if (! Validate::isLoadedObject($product)) {
                        return null;
                    }

                    // Get product cover image
                    $imageUrl = '';
                    $cover = Product::getCover($entityId);

                    if ($cover && isset($cover['id_image'])) {
                        $imageUrl = $context->link->getImageLink(
                            $product->link_rewrite,
                            $cover['id_image'],
                            ImageType::getFormattedName('small')
                        );
                    }

                    return [
                        'id' => $entityId,
                        'name' => $product->name,
                        'link' => $context->link->getProductLink($product),
                        'reference' => $product->reference ?? '',
                        'image' => $imageUrl,
                        'price' => Product::getPriceStatic($entityId, true),
                        'type' => 'product',
                    ];

                case 'category':
                    $category = new Category($entityId, $langId);

                    if (! Validate::isLoadedObject($category)) {
                        return null;
                    }

                    // Get category image
                    $imageUrl = '';

                    if (file_exists(_PS_CAT_IMG_DIR_ . $entityId . '.jpg')) {
                        $imageUrl = _PS_CAT_IMG_DIR_ . $entityId . '.jpg';
                        $imageUrl = $context->link->getMediaLink(_THEME_CAT_DIR_ . $entityId . '.jpg');
                    }

                    return [
                        'id' => $entityId,
                        'name' => $category->name,
                        'link' => $context->link->getCategoryLink($category),
                        'reference' => '',
                        'image' => $imageUrl,
                        'description' => $category->description ?? '',
                        'type' => 'category',
                    ];

                case 'cms':
                case 'cms_page':
                    $cms = new CMS($entityId, $langId);

                    if (! Validate::isLoadedObject($cms)) {
                        return null;
                    }

                    return [
                        'id' => $entityId,
                        'name' => $cms->meta_title,
                        'link' => $context->link->getCMSLink($cms),
                        'reference' => '',
                        'image' => '',
                        'type' => 'cms',
                    ];

                case 'manufacturer':
                    $manufacturer = new Manufacturer($entityId, $langId);

                    if (! Validate::isLoadedObject($manufacturer)) {
                        return null;
                    }

                    // Get manufacturer image
                    $imageUrl = '';

                    if (file_exists(_PS_MANU_IMG_DIR_ . $entityId . '.jpg')) {
                        $imageUrl = $context->link->getMediaLink(_THEME_MANU_DIR_ . $entityId . '.jpg');
                    }

                    return [
                        'id' => $entityId,
                        'name' => $manufacturer->name,
                        'link' => $context->link->getManufacturerLink($manufacturer),
                        'reference' => '',
                        'image' => $imageUrl,
                        'type' => 'manufacturer',
                    ];

                case 'supplier':
                    $supplier = new Supplier($entityId, $langId);

                    if (! Validate::isLoadedObject($supplier)) {
                        return null;
                    }

                    // Get supplier image
                    $imageUrl = '';

                    if (file_exists(_PS_SUPP_IMG_DIR_ . $entityId . '.jpg')) {
                        $imageUrl = $context->link->getMediaLink(_THEME_SUP_DIR_ . $entityId . '.jpg');
                    }

                    return [
                        'id' => $entityId,
                        'name' => $supplier->name,
                        'link' => $context->link->getSupplierLink($supplier),
                        'reference' => '',
                        'image' => $imageUrl,
                        'type' => 'supplier',
                    ];

                default:
                    // Generic entity - return basic info
                    return [
                        'id' => $entityId,
                        'name' => ucfirst($entityType) . " #{$entityId}",
                        'link' => '',
                        'reference' => '',
                        'image' => '',
                        'type' => $entityType,
                    ];
            }
        } catch (Throwable $e) {
            // Return minimal info on error
            return [
                'id' => $entityId,
                'name' => "#{$entityId}",
                'link' => '',
                'reference' => '',
                'image' => '',
                'type' => $entityType,
            ];
        }
    }

    /**
     * Parse JSON config if string.
     *
     * @return array<string, mixed>
     */
    private function parseConfig(mixed $config): array
    {
        if (\is_string($config)) {
            $decoded = json_decode($config, true);

            return \is_array($decoded) ? $decoded : [];
        }

        return \is_array($config) ? $config : [];
    }
}
