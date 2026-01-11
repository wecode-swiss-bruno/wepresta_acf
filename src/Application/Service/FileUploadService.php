<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Configuration;
use finfo;
use InvalidArgumentException;
use RuntimeException;
use WeprestaAcf\Wedev\Core\Trait\LoggerTrait;

/**
 * Handles file uploads for ACF fields.
 */
final class FileUploadService
{
    use LoggerTrait;

    private string $uploadDir;

    private string $moduleUrl;

    public function __construct(string $moduleDir)
    {
        $this->uploadDir = $moduleDir . 'uploads/';
        $this->moduleUrl = _MODULE_DIR_ . 'wepresta_acf/uploads/';
    }

    /**
     * @param array<string, mixed> $file @param array<string> $allowedMimes @return array<string, mixed>
     */
    public function upload(array $file, int $fieldId, int $productId, int $shopId, string $type = 'files', array $allowedMimes = [], bool $useFixedPath = true, ?int $maxFileSize = null, bool $deleteExisting = true): array
    {
        $this->logInfo('File upload started', [
            'field_id' => $fieldId,
            'product_id' => $productId,
            'original_name' => $file['name'] ?? 'unknown',
            'size' => $file['size'] ?? 0,
        ]);

        $this->validateUpload($file, $allowedMimes, $maxFileSize);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dir = $this->uploadDir . $type . '/';
        $this->ensureDirectory($dir);

        if ($deleteExisting) {
            $this->deleteExisting($fieldId, $productId, $shopId, $type);
        }

        $filename = $useFixedPath
            ? \sprintf('%d_%d_%d.%s', $fieldId, $productId, $shopId, $extension)
            : \sprintf('%d_%d_%d_%d_%s.%s', $fieldId, $productId, $shopId, time(), bin2hex(random_bytes(4)), $extension);

        $destination = $dir . $filename;

        if (! copy($file['tmp_name'], $destination)) {
            $this->logError('Failed to copy uploaded file', ['destination' => $destination]);

            throw new RuntimeException('Failed to copy uploaded file');
        }
        chmod($destination, 0o644);

        $this->logInfo('File uploaded successfully', ['filename' => $filename, 'path' => $type . '/' . $filename]);

        return [
            'filename' => $filename, 'path' => $type . '/' . $filename,
            'url' => $this->moduleUrl . $type . '/' . $filename,
            'size' => (int) $file['size'], 'mime' => $this->getMimeType($destination),
            'original_name' => $file['name'],
        ];
    }

    public function delete(int $fieldId, int $productId, int $shopId, string $type = 'files'): bool
    {
        return $this->deleteExisting($fieldId, $productId, $shopId, $type);
    }

    public function getPublicUrl(string $type, string $filename): string
    {
        return $this->moduleUrl . $type . '/' . $filename;
    }

    public function fileExists(int $fieldId, int $productId, int $shopId, string $type = 'files'): bool
    {
        return ! empty(glob($this->uploadDir . $type . '/' . $fieldId . '_' . $productId . '_' . $shopId . '.*'));
    }

    /**
     * @param array<string> $allowedMimes @return array<string, mixed>
     */
    public function downloadFromUrl(string $url, int $fieldId, int $productId, int $shopId, string $type = 'files', array $allowedMimes = [], bool $useFixedPath = true): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! \in_array(strtolower($scheme ?? ''), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP(S) URLs allowed');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'wepresta_acf_');

        if ($tempFile === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        try {
            $ch = curl_init($url);
            $fp = fopen($tempFile, 'wb');

            if ($ch === false || $fp === false) {
                throw new RuntimeException('Failed to initialize download');
            }
            curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'WePresta-ACF/1.0', CURLOPT_SSL_VERIFYPEER => true, CURLOPT_FAILONERROR => true]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if ($result === false || $httpCode !== 200) {
                throw new RuntimeException('Failed to download: ' . ($error ?: 'HTTP ' . $httpCode));
            }
            $fileSize = filesize($tempFile);

            if ($fileSize === false || $fileSize === 0) {
                throw new RuntimeException('Downloaded file is empty');
            }

            $mimeType = $this->getMimeType($tempFile);

            if (! empty($allowedMimes) && ! \in_array($mimeType, $allowedMimes, true)) {
                throw new InvalidArgumentException('File type not allowed: ' . $mimeType);
            }

            $urlPath = parse_url($url, PHP_URL_PATH);
            $extension = pathinfo($urlPath ?? '', PATHINFO_EXTENSION) ?: $this->getExtensionFromMime($mimeType);

            $dir = $this->uploadDir . $type . '/';
            $this->ensureDirectory($dir);
            $this->deleteExisting($fieldId, $productId, $shopId, $type);

            $filename = $useFixedPath ? \sprintf('%d_%d_%d.%s', $fieldId, $productId, $shopId, $extension) : \sprintf('%d_%d_%d_%d_%s.%s', $fieldId, $productId, $shopId, time(), bin2hex(random_bytes(4)), $extension);
            $destination = $dir . $filename;

            if (! copy($tempFile, $destination)) {
                throw new RuntimeException('Failed to save downloaded file');
            }
            chmod($destination, 0o644);

            return ['filename' => $filename, 'path' => $type . '/' . $filename, 'url' => $this->moduleUrl . $type . '/' . $filename, 'size' => $fileSize, 'mime' => $mimeType, 'original_name' => basename($urlPath ?? 'downloaded_file'), 'source_url' => $url, 'source_type' => 'import'];
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createExternalLink(string $url): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format');
        }
        $urlPath = parse_url($url, PHP_URL_PATH);

        return ['filename' => null, 'path' => null, 'url' => $url, 'size' => null, 'mime' => null, 'original_name' => basename($urlPath ?? 'external_file'), 'source_url' => $url, 'source_type' => 'link'];
    }

    private function deleteExisting(int $fieldId, int $productId, int $shopId, string $type): bool
    {
        $files = glob($this->uploadDir . $type . '/' . $fieldId . '_' . $productId . '_' . $shopId . '*');

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return ! empty($files);
    }

    /**
     * @param array<string, mixed> $file @param array<string> $allowedMimes
     */
    private function validateUpload(array $file, array $allowedMimes, ?int $maxFileSize = null): void
    {
        if (! isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors = [UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize', UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE', UPLOAD_ERR_PARTIAL => 'File was only partially uploaded', UPLOAD_ERR_NO_FILE => 'No file was uploaded'];

            throw new RuntimeException($errors[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Unknown upload error');
        }

        if (! isset($file['tmp_name']) || ! file_exists($file['tmp_name']) || ! is_readable($file['tmp_name'])) {
            throw new RuntimeException('Invalid upload: file not found or not readable');
        }
        $mimeType = $this->getMimeType($file['tmp_name']);

        if (! empty($allowedMimes) && ! \in_array($mimeType, $allowedMimes, true)) {
            throw new InvalidArgumentException(\sprintf('File type not allowed: %s', $mimeType));
        }
        $maxSize = $maxFileSize ?: (int) Configuration::get('WEPRESTA_ACF_MAX_FILE_SIZE') ?: 10 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            throw new InvalidArgumentException(\sprintf('File too large: %d bytes. Maximum: %d', $file['size'], $maxSize));
        }
    }

    private function getMimeType(string $filepath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filepath);

        return $mimeType !== false ? $mimeType : 'application/octet-stream';
    }

    private function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir) && ! mkdir($dir, 0o755, true)) {
            throw new RuntimeException('Failed to create upload directory: ' . $dir);
        }
        $htaccess = $dir . '.htaccess';

        if (! file_exists($htaccess)) {
            file_put_contents($htaccess, "# Deny direct PHP execution\n<FilesMatch \"\\.php$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\nOptions -ExecCGI -Indexes\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps");
        }
        $indexPhp = $dir . 'index.php';

        if (! file_exists($indexPhp)) {
            file_put_contents($indexPhp, '<?php header("HTTP/1.0 403 Forbidden"); exit;');
        }
    }

    private function getExtensionFromMime(string $mimeType): string
    {
        $map = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/webm' => 'webm'];

        return $map[$mimeType] ?? 'bin';
    }
}
