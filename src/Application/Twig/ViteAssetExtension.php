<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

/**
 * Vite Asset Extension.
 *
 * Provides Twig functions for loading Vite-compiled assets with manifest support.
 * Handles cache-busting automatically via manifest.json in production.
 *
 * Usage in Twig:
 *   {{ vite_asset('main') }}                    → Returns path to acf-main.js
 *   {{ vite_css('main') }}                      → Returns path to acf-main.css
 *   {{ vite_script('main') }}                   → Returns full <script> tag
 *   {{ vite_stylesheet('main') }}               → Returns full <link> tag
 *   {{ vite_entity_asset('entity-fields') }}    → Returns path from entity manifest
 *
 * @author Bruno Studer
 * @copyright 2024 WeCode
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ViteAssetExtension extends AbstractExtension
{
    private const MODULE_NAME = 'wepresta_acf';
    private const VITE_DIST_PATH = 'views/js/admin/dist';
    // Vite 5+ puts manifest in .vite/ subdirectory
    private const MANIFEST_FILE = '.vite/manifest.json';
    private const MANIFEST_ENTITY_FILE = 'manifest-entity.json';

    /** @var array<string, mixed>|null */
    private ?array $manifest = null;

    /** @var array<string, mixed>|null */
    private ?array $manifestEntity = null;

    private string $modulePath;

    public function __construct()
    {
        $this->modulePath = _PS_MODULE_DIR_ . self::MODULE_NAME . '/';
    }

    public function getName(): string
    {
        return 'vite_asset';
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            // Asset paths
            new TwigFunction('vite_asset', [$this, 'getAssetPath']),
            new TwigFunction('vite_css', [$this, 'getCssPath']),
            new TwigFunction('vite_entity_asset', [$this, 'getEntityAssetPath']),

            // Full HTML tags
            new TwigFunction('vite_script', [$this, 'getScriptTag'], ['is_safe' => ['html']]),
            new TwigFunction('vite_stylesheet', [$this, 'getStylesheetTag'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entity_script', [$this, 'getEntityScriptTag'], ['is_safe' => ['html']]),

            // Utilities
            new TwigFunction('vite_has_manifest', [$this, 'hasManifest']),
        ];
    }

    /**
     * Get the path to a Vite-compiled JS asset.
     *
     * @param string $entry Entry name (e.g., 'main', 'cpt')
     *
     * @return string Full URL path to the asset
     */
    public function getAssetPath(string $entry): string
    {
        $manifest = $this->getManifest();

        // Look for the entry in manifest
        $key = "src/{$entry}.ts";
        if (isset($manifest[$key]['file'])) {
            return $this->buildAssetUrl($manifest[$key]['file']);
        }

        // Fallback: try direct filename (dev mode without hash)
        $fallbackFile = "acf-{$entry}.js";
        $fallbackPath = $this->modulePath . self::VITE_DIST_PATH . '/' . $fallbackFile;

        if (file_exists($fallbackPath)) {
            return $this->buildAssetUrl($fallbackFile);
        }

        // Last fallback: return expected path anyway
        return $this->buildAssetUrl($fallbackFile);
    }

    /**
     * Get the path to a Vite-compiled CSS asset.
     *
     * @param string $entry Entry name (e.g., 'main', 'cpt')
     *
     * @return string Full URL path to the CSS asset
     */
    public function getCssPath(string $entry): string
    {
        $manifest = $this->getManifest();

        // Look for CSS in the entry's assets
        $key = "src/{$entry}.ts";
        if (isset($manifest[$key]['css'][0])) {
            return $this->buildAssetUrl($manifest[$key]['css'][0]);
        }

        // Fallback: try direct filename
        $fallbackFile = "acf-{$entry}.css";

        return $this->buildAssetUrl($fallbackFile);
    }

    /**
     * Get the path to an entity-fields asset (from separate manifest).
     *
     * @param string $entry Entry name
     *
     * @return string Full URL path to the asset
     */
    public function getEntityAssetPath(string $entry): string
    {
        $manifest = $this->getEntityManifest();

        $key = "src/{$entry}.ts";
        if (isset($manifest[$key]['file'])) {
            return $this->buildAssetUrl($manifest[$key]['file']);
        }

        // Fallback
        return $this->buildAssetUrl("{$entry}.js");
    }

    /**
     * Get a full <script> tag for a Vite entry.
     *
     * @param string $entry Entry name
     * @param bool $module Whether to use type="module" (default: true)
     *
     * @return string HTML script tag
     */
    public function getScriptTag(string $entry, bool $module = true): string
    {
        $path = $this->getAssetPath($entry);
        $type = $module ? ' type="module"' : '';

        return sprintf('<script%s src="%s"></script>', $type, htmlspecialchars($path, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Get a full <link> stylesheet tag for a Vite entry.
     *
     * @param string $entry Entry name
     *
     * @return string HTML link tag
     */
    public function getStylesheetTag(string $entry): string
    {
        $path = $this->getCssPath($entry);

        return sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($path, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Get a full <script> tag for an entity-fields entry (IIFE format).
     *
     * @param string $entry Entry name
     *
     * @return string HTML script tag
     */
    public function getEntityScriptTag(string $entry): string
    {
        $path = $this->getEntityAssetPath($entry);

        return sprintf('<script src="%s"></script>', htmlspecialchars($path, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Check if manifest exists (production mode).
     *
     * @return bool
     */
    public function hasManifest(): bool
    {
        $manifestPath = $this->modulePath . self::VITE_DIST_PATH . '/' . self::MANIFEST_FILE;

        return file_exists($manifestPath);
    }

    // =========================================================================
    // PRIVATE
    // =========================================================================

    /**
     * Get the main manifest content.
     *
     * @return array<string, mixed>
     */
    private function getManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifestPath = $this->modulePath . self::VITE_DIST_PATH . '/' . self::MANIFEST_FILE;

        if (file_exists($manifestPath)) {
            $content = file_get_contents($manifestPath);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $this->manifest = $decoded;

                    return $this->manifest;
                }
            }
        }

        $this->manifest = [];

        return $this->manifest;
    }

    /**
     * Get the entity manifest content.
     *
     * @return array<string, mixed>
     */
    private function getEntityManifest(): array
    {
        if ($this->manifestEntity !== null) {
            return $this->manifestEntity;
        }

        $manifestPath = $this->modulePath . self::VITE_DIST_PATH . '/' . self::MANIFEST_ENTITY_FILE;

        if (file_exists($manifestPath)) {
            $content = file_get_contents($manifestPath);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $this->manifestEntity = $decoded;

                    return $this->manifestEntity;
                }
            }
        }

        $this->manifestEntity = [];

        return $this->manifestEntity;
    }

    /**
     * Build a full URL for an asset file.
     *
     * @param string $filename Filename within dist directory
     *
     * @return string Full URL path
     */
    private function buildAssetUrl(string $filename): string
    {
        return '/modules/' . self::MODULE_NAME . '/' . self::VITE_DIST_PATH . '/' . $filename;
    }
}
