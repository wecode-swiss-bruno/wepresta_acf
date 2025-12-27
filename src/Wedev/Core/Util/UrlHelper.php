<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Core\Util;

/**
 * URL utility helpers.
 * 
 * Provides static methods for URL parsing, validation, and video embed detection.
 */
final class UrlHelper
{
    /**
     * YouTube URL patterns.
     */
    private const YOUTUBE_PATTERNS = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
    ];

    /**
     * Vimeo URL patterns.
     */
    private const VIMEO_PATTERNS = [
        '/vimeo\.com\/(\d+)/',
        '/player\.vimeo\.com\/video\/(\d+)/',
    ];

    /**
     * Parse video URL and extract embed info.
     *
     * @param string $url Video URL (YouTube, Vimeo, or direct file)
     * @return array|null Parsed info or null if not recognized
     *                    ['source' => 'youtube'|'vimeo'|'file', 'id' => string, 'embed_url' => string]
     */
    public static function parseVideoEmbed(string $url): ?array
    {
        $url = trim($url);
        
        if (empty($url)) {
            return null;
        }

        // YouTube
        foreach (self::YOUTUBE_PATTERNS as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return [
                    'source' => 'youtube',
                    'id' => $matches[1],
                    'embed_url' => 'https://www.youtube.com/embed/' . $matches[1],
                    'thumbnail' => 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg',
                ];
            }
        }

        // Vimeo
        foreach (self::VIMEO_PATTERNS as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return [
                    'source' => 'vimeo',
                    'id' => $matches[1],
                    'embed_url' => 'https://player.vimeo.com/video/' . $matches[1],
                    'thumbnail' => null, // Vimeo requires API call for thumbnail
                ];
            }
        }

        // Direct video file URL
        if (self::isVideoUrl($url)) {
            return [
                'source' => 'file',
                'id' => null,
                'url' => $url,
                'embed_url' => null,
            ];
        }

        return null;
    }

    /**
     * Check if URL is external (different domain).
     *
     * @param string $url URL to check
     * @param string|null $currentHost Current host for comparison (uses $_SERVER if null)
     * @return bool True if external URL
     */
    public static function isExternalUrl(string $url, ?string $currentHost = null): bool
    {
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['host'])) {
            return false; // Relative URL
        }

        if ($currentHost === null) {
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        }

        return strtolower($parsedUrl['host']) !== strtolower($currentHost);
    }

    /**
     * Check if URL points to a video file.
     *
     * @param string $url URL to check
     * @return bool True if URL ends with video extension
     */
    public static function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'avi', 'mkv'];
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        
        return in_array($extension, $videoExtensions, true);
    }

    /**
     * Check if URL points to an image file.
     *
     * @param string $url URL to check
     * @return bool True if URL ends with image extension
     */
    public static function isImageUrl(string $url): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        
        return in_array($extension, $imageExtensions, true);
    }

    /**
     * Check if URL is valid.
     *
     * @param string $url URL to validate
     * @return bool True if valid URL
     */
    public static function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Ensure URL has protocol (defaults to https).
     *
     * @param string $url URL that may be missing protocol
     * @return string URL with protocol
     */
    public static function ensureProtocol(string $url): string
    {
        $url = trim($url);
        
        if (empty($url)) {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $url)) {
            return 'https://' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Extract domain from URL.
     *
     * @param string $url Full URL
     * @return string|null Domain or null if invalid
     */
    public static function getDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }

    /**
     * Build URL with query parameters.
     *
     * @param string $baseUrl Base URL
     * @param array $params Query parameters
     * @return string Complete URL with query string
     */
    public static function buildUrl(string $baseUrl, array $params = []): string
    {
        if (empty($params)) {
            return $baseUrl;
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        return $baseUrl . $separator . http_build_query($params);
    }
}

