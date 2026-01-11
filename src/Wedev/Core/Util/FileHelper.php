<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Core\Util;

/**
 * File utility helpers.
 *
 * Provides static methods for common file operations like formatting
 * file sizes, determining file icons from MIME types, and extracting extensions.
 */
final class FileHelper
{
    /** Material Icons mapping for common MIME types. */
    private const MIME_ICONS = [
        // Images
        'image/' => 'image',
        // Video
        'video/' => 'movie',
        // Audio
        'audio/' => 'audiotrack',
        // Documents
        'application/pdf' => 'picture_as_pdf',
        'application/msword' => 'description',
        'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'description',
        'application/vnd.ms-excel' => 'grid_on',
        'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'grid_on',
        'application/vnd.ms-powerpoint' => 'slideshow',
        'application/vnd.openxmlformats-officedocument.presentationml' => 'slideshow',
        // Archives
        'application/zip' => 'folder_zip',
        'application/x-rar' => 'folder_zip',
        'application/x-7z-compressed' => 'folder_zip',
        'application/gzip' => 'folder_zip',
        'application/x-tar' => 'folder_zip',
        // Code
        'text/html' => 'code',
        'text/css' => 'code',
        'text/javascript' => 'code',
        'application/javascript' => 'code',
        'application/json' => 'data_object',
        'application/xml' => 'code',
        'text/xml' => 'code',
        // Text
        'text/plain' => 'article',
        'text/csv' => 'grid_on',
        // Default
        'application/octet-stream' => 'insert_drive_file',
    ];

    /**
     * Format bytes to human-readable size string.
     *
     * @param int $bytes File size in bytes
     * @param int $decimals Number of decimal places (default: 1)
     *
     * @return string Formatted size (e.g., "1.5 MB", "256 KB")
     */
    public static function formatSize(int $bytes, int $decimals = 1): string
    {
        if ($bytes < 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = 0;

        while ($bytes >= 1024 && $factor < \count($units) - 1) {
            $bytes /= 1024;
            ++$factor;
        }

        // No decimals for bytes
        if ($factor === 0) {
            return $bytes . ' B';
        }

        return number_format($bytes, $decimals, '.', '') . ' ' . $units[$factor];
    }

    /**
     * Get Material Icon name for a MIME type.
     *
     * @param string $mimeType MIME type (e.g., "image/jpeg", "application/pdf")
     *
     * @return string Material Icon name (e.g., "image", "picture_as_pdf")
     */
    public static function getMimeIcon(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        // Direct match first
        if (isset(self::MIME_ICONS[$mimeType])) {
            return self::MIME_ICONS[$mimeType];
        }

        // Partial match (for image/, video/, audio/)
        foreach (self::MIME_ICONS as $pattern => $icon) {
            if (str_ends_with($pattern, '/') && str_starts_with($mimeType, $pattern)) {
                return $icon;
            }

            if (str_contains($mimeType, $pattern)) {
                return $icon;
            }
        }

        return 'insert_drive_file';
    }

    /**
     * Get file extension from filename.
     *
     * @param string $filename Filename or path
     *
     * @return string Lowercase extension without dot (e.g., "pdf", "jpg")
     */
    public static function getExtension(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        return strtolower($ext);
    }

    /**
     * Check if MIME type is an image.
     */
    public static function isImage(string $mimeType): bool
    {
        return str_starts_with(strtolower($mimeType), 'image/');
    }

    /**
     * Check if MIME type is a video.
     */
    public static function isVideo(string $mimeType): bool
    {
        return str_starts_with(strtolower($mimeType), 'video/');
    }

    /**
     * Check if MIME type is audio.
     */
    public static function isAudio(string $mimeType): bool
    {
        return str_starts_with(strtolower($mimeType), 'audio/');
    }

    /**
     * Check if MIME type is a document (PDF, Word, Excel, etc.).
     */
    public static function isDocument(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);
        $docTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats', 'application/vnd.ms-'];

        foreach ($docTypes as $type) {
            if (str_contains($mimeType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get safe filename by removing unsafe characters.
     *
     * @param string $filename Original filename
     *
     * @return string Sanitized filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove special characters except dots, dashes, underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Ensure not empty
        if (empty($filename)) {
            $filename = 'file_' . time();
        }

        return $filename;
    }
}
