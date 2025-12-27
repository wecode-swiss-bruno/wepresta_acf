<?php
/**
 * FieldTypeLoader - Discover and load custom field types
 *
 * Discovers field types from:
 * - Core: src/Application/FieldType/
 * - Theme: themes/{theme}/acf/field-types/
 * - Uploads: modules/wepresta_acf/custom-field-types/
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Application\Brick\AcfBrickDiscovery;
use WeprestaAcf\Application\FieldType\FieldTypeInterface;
use WeprestaAcf\Wedev\Core\Trait\LoggerTrait;

final class FieldTypeLoader
{
    use LoggerTrait;

    public const SOURCE_CORE = 'core';
    public const SOURCE_THEME = 'theme';
    public const SOURCE_UPLOADED = 'uploaded';
    public const SOURCE_BRICK = 'brick';

    private const CUSTOM_TYPES_DIR = 'custom-field-types';
    private const THEME_TYPES_DIR = 'acf/field-types';

    /** @var array<string, array{type: FieldTypeInterface, source: string, path: string}> */
    private array $loadedTypes = [];

    /** @var array<string, string> */
    private array $discoveryPaths = [];

    private ?AcfBrickDiscovery $brickDiscovery = null;

    public function __construct(
        private readonly FieldTypeRegistry $registry,
        private readonly string $modulePath,
        ?AcfBrickDiscovery $brickDiscovery = null
    ) {
        $this->brickDiscovery = $brickDiscovery;
        $this->initDiscoveryPaths();
    }

    /**
     * Initialize discovery paths.
     */
    private function initDiscoveryPaths(): void
    {
        // Core types (already registered in FieldTypeRegistry)
        $this->discoveryPaths[self::SOURCE_CORE] = $this->modulePath . '/src/Application/FieldType/';

        // Uploaded custom types
        $this->discoveryPaths[self::SOURCE_UPLOADED] = $this->modulePath . '/' . self::CUSTOM_TYPES_DIR . '/';

        // Theme types (resolved dynamically)
        $themeName = \Configuration::get('PS_THEME_NAME') ?: 'classic';
        $this->discoveryPaths[self::SOURCE_THEME] = _PS_ALL_THEMES_DIR_ . $themeName . '/' . self::THEME_TYPES_DIR . '/';
    }

    /**
     * Load all custom field types from discovery paths.
     */
    public function loadAllCustomTypes(): void
    {
        // Load from theme
        $this->loadTypesFromPath(
            $this->discoveryPaths[self::SOURCE_THEME],
            self::SOURCE_THEME
        );

        // Load from uploads
        $this->loadTypesFromPath(
            $this->discoveryPaths[self::SOURCE_UPLOADED],
            self::SOURCE_UPLOADED
        );

        // Load from bricks (third-party modules)
        $this->loadTypesFromBricks();
    }

    /**
     * Load field types from discovered bricks.
     */
    private function loadTypesFromBricks(): void
    {
        $discovery = $this->brickDiscovery ?? new AcfBrickDiscovery();

        foreach ($discovery->discoverFieldTypeBricks() as $name => $brick) {
            try {
                $fieldType = $brick->getFieldType();
                $type = $fieldType->getType();

                // Don't override existing types
                if ($this->registry->has($type)) {
                    $this->logInfo('Brick field type skipped (already exists)', [
                        'brick' => $name,
                        'type' => $type,
                    ]);
                    continue;
                }

                $this->registry->register($fieldType);

                $this->loadedTypes[$type] = [
                    'type' => $fieldType,
                    'source' => self::SOURCE_BRICK,
                    'path' => 'brick:' . $name,
                ];

                $this->logInfo('Brick field type loaded', [
                    'brick' => $name,
                    'type' => $type,
                ]);
            } catch (\Throwable $e) {
                $this->logError('Failed to load field type from brick', [
                    'brick' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Load field types from a specific path.
     */
    private function loadTypesFromPath(string $path, string $source): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '*FieldType.php') ?: [];
        $files = array_merge($files, glob($path . '*Field.php') ?: []);

        foreach ($files as $file) {
            $this->loadTypeFromFile($file, $source);
        }
    }

    /**
     * Load a single field type from a PHP file.
     */
    private function loadTypeFromFile(string $file, string $source): bool
    {
        if (!file_exists($file) || !is_readable($file)) {
            $this->logError('Field type file not readable', ['file' => $file]);
            return false;
        }

        // Extract class name from file
        $className = $this->extractClassName($file);
        if ($className === null) {
            $this->logError('Could not extract class name from file', ['file' => $file]);
            return false;
        }

        // Include the file if class doesn't exist
        if (!class_exists($className)) {
            require_once $file;
        }

        if (!class_exists($className)) {
            $this->logError('Class not found after include', ['class' => $className, 'file' => $file]);
            return false;
        }

        // Validate it implements the interface
        if (!is_subclass_of($className, FieldTypeInterface::class)) {
            $this->logError('Class does not implement FieldTypeInterface', ['class' => $className]);
            return false;
        }

        try {
            /** @var FieldTypeInterface $instance */
            $instance = new $className();
            $type = $instance->getType();

            // Register with the registry (replaces if exists)
            $this->registry->registerOrReplace($instance);

            // Track loaded type
            $this->loadedTypes[$type] = [
                'type' => $instance,
                'source' => $source,
                'path' => $file,
            ];

            $this->logInfo('Field type loaded', [
                'type' => $type,
                'source' => $source,
                'file' => basename($file),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logError('Failed to instantiate field type', [
                'class' => $className,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Extract class name from PHP file.
     */
    private function extractClassName(string $file): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]) . '\\';
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $namespace . $matches[1];
        }

        return null;
    }

    /**
     * Get all loaded types with metadata.
     *
     * @return array<string, array{type: string, label: string, source: string, path: string}>
     */
    public function getLoadedTypesInfo(): array
    {
        $info = [];

        // First, add core types
        foreach ($this->registry->getAll() as $type => $fieldType) {
            $source = $this->loadedTypes[$type]['source'] ?? self::SOURCE_CORE;
            $path = $this->loadedTypes[$type]['path'] ?? $this->discoveryPaths[self::SOURCE_CORE];

            $info[$type] = [
                'type' => $type,
                'label' => $fieldType->getLabel(),
                'category' => $fieldType->getCategory(),
                'icon' => $fieldType->getIcon(),
                'source' => $source,
                'path' => $path,
                'can_delete' => $source !== self::SOURCE_CORE,
            ];
        }

        return $info;
    }

    /**
     * Get discovery paths.
     *
     * @return array<string, array{path: string, exists: bool, count: int}>
     */
    public function getDiscoveryPaths(): array
    {
        $paths = [];

        foreach ($this->discoveryPaths as $source => $path) {
            $exists = is_dir($path);
            $count = 0;

            if ($exists) {
                $files = glob($path . '*Field*.php') ?: [];
                $count = count($files);
            }

            $paths[$source] = [
                'path' => $path,
                'exists' => $exists,
                'count' => $count,
            ];
        }

        return $paths;
    }

    /**
     * Upload a custom field type file.
     */
    public function uploadFieldType(string $tmpPath, string $filename): array
    {
        $uploadsDir = $this->discoveryPaths[self::SOURCE_UPLOADED];

        // Ensure directory exists
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
            return ['success' => false, 'error' => 'Cannot create upload directory'];
        }

        // Validate filename
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*Field(Type)?\.php$/', $filename)) {
            return ['success' => false, 'error' => 'Invalid filename. Must be PascalCase ending with Field.php or FieldType.php'];
        }

        // Validate PHP file content
        $content = file_get_contents($tmpPath);
        if ($content === false) {
            return ['success' => false, 'error' => 'Cannot read uploaded file'];
        }

        // Security check: must implement FieldTypeInterface
        if (strpos($content, 'FieldTypeInterface') === false && strpos($content, 'AbstractFieldType') === false) {
            return ['success' => false, 'error' => 'File must implement FieldTypeInterface or extend AbstractFieldType'];
        }

        // Check for dangerous functions
        $dangerousFunctions = ['eval', 'exec', 'system', 'shell_exec', 'passthru', 'popen', 'proc_open'];
        foreach ($dangerousFunctions as $func) {
            if (preg_match('/\b' . $func . '\s*\(/', $content)) {
                return ['success' => false, 'error' => 'File contains forbidden function: ' . $func];
            }
        }

        $targetPath = $uploadsDir . $filename;
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            // Fallback for non-uploaded files (e.g., in tests)
            if (!copy($tmpPath, $targetPath)) {
                return ['success' => false, 'error' => 'Cannot save uploaded file'];
            }
        }

        // Try to load it
        $loaded = $this->loadTypeFromFile($targetPath, self::SOURCE_UPLOADED);
        if (!$loaded) {
            unlink($targetPath); // Remove invalid file
            return ['success' => false, 'error' => 'File loaded but is not a valid field type'];
        }

        return [
            'success' => true,
            'path' => $targetPath,
            'filename' => $filename,
        ];
    }

    /**
     * Delete a custom field type.
     */
    public function deleteFieldType(string $type): array
    {
        if (!isset($this->loadedTypes[$type])) {
            // Check if it's a core type
            if ($this->registry->has($type)) {
                return ['success' => false, 'error' => 'Cannot delete core field types'];
            }
            return ['success' => false, 'error' => 'Field type not found'];
        }

        $info = $this->loadedTypes[$type];
        if ($info['source'] === self::SOURCE_CORE) {
            return ['success' => false, 'error' => 'Cannot delete core field types'];
        }

        // Only allow deleting uploaded types
        if ($info['source'] !== self::SOURCE_UPLOADED) {
            return ['success' => false, 'error' => 'Can only delete uploaded field types'];
        }

        $path = $info['path'];
        if (file_exists($path) && !unlink($path)) {
            return ['success' => false, 'error' => 'Cannot delete file'];
        }

        unset($this->loadedTypes[$type]);

        $this->logInfo('Field type deleted', ['type' => $type, 'path' => $path]);

        return ['success' => true, 'type' => $type];
    }

    /**
     * Get the uploads directory path.
     */
    public function getUploadsPath(): string
    {
        return $this->discoveryPaths[self::SOURCE_UPLOADED];
    }

    /**
     * Get the theme field types path.
     */
    public function getThemePath(): string
    {
        return $this->discoveryPaths[self::SOURCE_THEME];
    }
}

