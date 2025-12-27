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

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Video field type with multi-source support
 *
 * Supports:
 * - YouTube URLs (embedded player)
 * - Vimeo URLs (embedded player)
 * - Direct video file URLs
 * - Video file uploads (mp4, webm, ogg)
 *
 * Stores video metadata as JSON:
 * {
 *   "source": "youtube|vimeo|upload|url",
 *   "video_id": "dQw4w9WgXcQ",
 *   "url": "https://youtube.com/watch?v=...",
 *   "thumbnail_url": "https://img.youtube.com/vi/.../hqdefault.jpg",
 *   "filename": "video.mp4",
 *   "path": "videos/1_42_1.mp4",
 *   "size": 12345678,
 *   "mime": "video/mp4",
 *   "original_name": "product-demo.mp4",
 *   "title": "Video Title",
 *   "description": "Video description"
 * }
 */
final class VideoField extends AbstractFieldType
{
    /**
     * Allowed MIME types for video uploads
     */
    private const ALLOWED_VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
    ];

    public function getType(): string
    {
        return 'video';
    }

    public function getLabel(): string
    {
        return 'Video';
    }

    public function getFormType(): string
    {
        return TextType::class;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If already JSON string, validate and return
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            // Valid video data has source and either video_id, url, or filename
            if (is_array($decoded) && isset($decoded['source'])) {
                return $value;
            }

            return null;
        }

        // If array (from form), encode to JSON
        if (is_array($value) && isset($value['source'])) {
            return json_encode($value);
        }

        return null;
    }

    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Parse JSON to array
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Already an array
        if (is_array($value)) {
            return $value;
        }

        return null;
    }

    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        $data = $this->denormalizeValue($value, $fieldConfig);

        if (!is_array($data) || !isset($data['source'])) {
            return '';
        }

        $source = $data['source'];

        return match ($source) {
            'youtube' => $this->renderYouTube($data),
            'vimeo' => $this->renderVimeo($data),
            default => $this->renderVideoFile($data),
        };
    }

    private function renderYouTube(array $data): string
    {
        $videoId = htmlspecialchars($data['video_id'] ?? '', ENT_QUOTES, 'UTF-8');
        if (empty($videoId)) {
            return '';
        }

        return sprintf(
            '<div class="acf-video acf-video-youtube"><iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
            $videoId
        );
    }

    private function renderVimeo(array $data): string
    {
        $videoId = htmlspecialchars($data['video_id'] ?? '', ENT_QUOTES, 'UTF-8');
        if (empty($videoId)) {
            return '';
        }

        return sprintf(
            '<div class="acf-video acf-video-vimeo"><iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
            $videoId
        );
    }

    private function renderVideoFile(array $data): string
    {
        $url = htmlspecialchars($data['url'] ?? '', ENT_QUOTES, 'UTF-8');
        if (empty($url)) {
            return '';
        }

        $poster = isset($data['thumbnail_url']) ? htmlspecialchars($data['thumbnail_url'], ENT_QUOTES, 'UTF-8') : '';
        $posterAttr = $poster ? ' poster="' . $poster . '"' : '';

        return sprintf(
            '<div class="acf-video acf-video-file"><video src="%s" controls%s></video></div>',
            $url,
            $posterAttr
        );
    }

    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string
    {
        $data = $this->denormalizeValue($value, $fieldConfig);

        if (!is_array($data)) {
            return null;
        }

        return $data['title'] ?? $data['original_name'] ?? $data['url'] ?? null;
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = parent::validate($value, $fieldConfig, $validation);

        if ($this->isEmpty($value)) {
            return $errors;
        }

        $data = $this->denormalizeValue($value, $fieldConfig);

        if (!is_array($data) || !isset($data['source'])) {
            $errors[] = 'Invalid video data.';
        }

        return $errors;
    }

    public function getDefaultConfig(): array
    {
        return [
            'allowYouTube' => true,
            'allowVimeo' => true,
            'allowUpload' => true,
            'allowUrl' => true,
            'maxSizeMB' => 100,
            'coverPoster' => false,
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'allowYouTube' => [
                'type' => 'checkbox',
                'label' => 'Allow YouTube',
                'default' => true,
            ],
            'allowVimeo' => [
                'type' => 'checkbox',
                'label' => 'Allow Vimeo',
                'default' => true,
            ],
            'allowUpload' => [
                'type' => 'checkbox',
                'label' => 'Allow Video Upload',
                'default' => true,
            ],
            'allowUrl' => [
                'type' => 'checkbox',
                'label' => 'Allow Video URL',
                'default' => true,
            ],
            'maxSizeMB' => [
                'type' => 'number',
                'label' => 'Max File Size (MB)',
                'default' => 100,
                'min' => 1,
                'max' => 500,
            ],
        ];
    }

    public function supportsTranslation(): bool
    {
        return false;
    }

    public function getCategory(): string
    {
        return 'media';
    }

    public function getIcon(): string
    {
        return 'videocam';
    }

    /**
     * Get allowed MIME types for video uploads
     *
     * @return array<string>
     */
    public function getAllowedMimes(array $fieldConfig): array
    {
        return self::ALLOWED_VIDEO_MIMES;
    }

    /**
     * Parse a video URL and detect the source
     *
     * @return array{source: string, video_id: string|null, url: string, thumbnail_url: string|null}
     */
    public static function parseVideoUrl(string $url): array
    {
        $url = trim($url);

        // YouTube: youtube.com/watch?v=xxx or youtu.be/xxx or youtube.com/embed/xxx
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];

            return [
                'source' => 'youtube',
                'video_id' => $videoId,
                'url' => $url,
                'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
            ];
        }

        // Vimeo: vimeo.com/123456789 or player.vimeo.com/video/123456789
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return [
                'source' => 'vimeo',
                'video_id' => $matches[1],
                'url' => $url,
                'thumbnail_url' => null, // Vimeo thumbnails require API call
            ];
        }

        // Direct video URL
        return [
            'source' => 'url',
            'video_id' => null,
            'url' => $url,
            'thumbnail_url' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string
    {
        $config = $this->getFieldConfig($field);
        $data = $this->denormalizeValue($value, $config);

        return $this->renderPartial('video.tpl', [
            'field' => $field,
            'fieldConfig' => $config,
            'prefix' => $context['prefix'] ?? 'acf_',
            'value' => $data,
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
            '<div class="acf-video-field acf-video-compact" data-slug="%s">' .
            '<input type="hidden" class="acf-subfield-input acf-video-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-video-preview" style="display: none;"><div class="acf-video-thumbnail"><img src="" alt=""></div><button type="button" class="btn btn-sm btn-link text-danger acf-video-remove"><i class="material-icons">delete</i></button></div>' .
            '<div class="acf-video-input-area"><input type="url" class="form-control form-control-sm acf-video-url-input" placeholder="Video URL..."></div>' .
            '</div>',
            $this->escapeAttr($slug),
            $this->escapeAttr($slug)
        );
    }
}
