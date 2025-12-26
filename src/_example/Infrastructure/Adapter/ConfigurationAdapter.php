<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Infrastructure\Adapter;

use Configuration;
use Shop;

/**
 * Adaptateur pour la configuration PrestaShop avec cache local
 */
final class ConfigurationAdapter
{
    private array $cache = [];

    /**
     * Récupère une valeur de configuration
     */
    public function get(string $key, mixed $default = null, ?int $shopId = null, ?int $langId = null): mixed
    {
        $cacheKey = $this->buildCacheKey($key, $shopId, $langId);

        if (!isset($this->cache[$cacheKey])) {
            $value = Configuration::get($key, $langId, null, $shopId);
            $this->cache[$cacheKey] = $value !== false ? $value : $default;
        }

        return $this->cache[$cacheKey];
    }

    /**
     * Définit une valeur de configuration
     */
    public function set(string $key, mixed $value, ?int $shopId = null): bool
    {
        $this->clearCache($key);

        if ($shopId !== null) {
            Shop::setContext(Shop::CONTEXT_SHOP, $shopId);
        }

        return Configuration::updateValue($key, $value);
    }

    /**
     * Supprime une configuration
     */
    public function delete(string $key): bool
    {
        $this->clearCache($key);
        return Configuration::deleteByName($key);
    }

    /**
     * Vérifie si une configuration existe et a une valeur
     */
    public function has(string $key): bool
    {
        $value = $this->get($key);
        return $value !== null && $value !== false && $value !== '';
    }

    /**
     * Récupère un booléen
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * Récupère un entier
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Récupère un tableau JSON
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key);

        if (empty($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Définit un tableau en JSON
     */
    public function setArray(string $key, array $value): bool
    {
        return $this->set($key, json_encode($value));
    }

    /**
     * Vide le cache local
     */
    public function clearCache(?string $key = null): void
    {
        if ($key === null) {
            $this->cache = [];
        } else {
            foreach (array_keys($this->cache) as $cacheKey) {
                if (str_starts_with($cacheKey, $key . '_')) {
                    unset($this->cache[$cacheKey]);
                }
            }
        }
    }

    private function buildCacheKey(string $key, ?int $shopId, ?int $langId): string
    {
        return sprintf('%s_%d_%d', $key, $shopId ?? 0, $langId ?? 0);
    }
}

