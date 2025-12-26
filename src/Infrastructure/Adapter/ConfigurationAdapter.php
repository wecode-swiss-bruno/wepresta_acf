<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Adapter;

use Configuration;

/**
 * Adapter for PrestaShop Configuration access
 */
class ConfigurationAdapter
{
    public function get(string $key, ?int $idLang = null, ?int $idShopGroup = null, ?int $idShop = null): mixed
    {
        return Configuration::get($key, $idLang, $idShopGroup, $idShop);
    }

    public function getString(string $key, ?int $idLang = null): string
    {
        $value = $this->get($key, $idLang);
        return is_string($value) ? $value : '';
    }

    public function getInt(string $key, ?int $idLang = null): int
    {
        return (int) $this->get($key, $idLang);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if ($value === null || $value === '') { return $default; }
        return (bool) $value;
    }

    public function getFloat(string $key): float
    {
        return (float) $this->get($key);
    }

    /** @return array<mixed>|null */
    public function getJson(string $key): ?array
    {
        $value = $this->getString($key);
        if ($value === '') { return null; }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function set(string $key, mixed $value, bool $html = false, ?int $idShopGroup = null, ?int $idShop = null): bool
    {
        return Configuration::updateValue($key, $value, $html, $idShopGroup, $idShop);
    }

    /** @param array<mixed> $value */
    public function setJson(string $key, array $value): bool
    {
        return $this->set($key, json_encode($value, JSON_THROW_ON_ERROR));
    }

    public function delete(string $key): bool
    {
        return Configuration::deleteByName($key);
    }

    public function has(string $key): bool
    {
        return Configuration::hasKey($key);
    }

    public function getGlobal(string $key): mixed
    {
        return Configuration::getGlobalValue($key);
    }

    public function setGlobal(string $key, mixed $value): bool
    {
        return Configuration::updateGlobalValue($key, $value);
    }

    /** @param string[] $keys @return array<string, mixed> */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) { $result[$key] = $this->get($key); }
        return $result;
    }

    /** @param array<string, mixed> $values */
    public function setMultiple(array $values): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value)) { $success = false; }
        }
        return $success;
    }
}

