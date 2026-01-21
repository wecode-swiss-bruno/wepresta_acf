<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Core\Adapter;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;

/**
 * Adapter pour accéder à la configuration PrestaShop.
 *
 * Fournit une interface typée et testable pour Configuration::get/update.
 */
class ConfigurationAdapter
{
    /**
     * Récupère une valeur de configuration.
     */
    public function get(string $key, ?int $idLang = null, ?int $idShopGroup = null, ?int $idShop = null): mixed
    {
        return Configuration::get($key, $idLang, $idShopGroup, $idShop);
    }

    /**
     * Récupère une valeur string.
     */
    public function getString(string $key, ?int $idLang = null): string
    {
        $value = $this->get($key, $idLang);

        return \is_string($value) ? $value : '';
    }

    /**
     * Récupère une valeur int.
     */
    public function getInt(string $key, ?int $idLang = null): int
    {
        return (int) $this->get($key, $idLang);
    }

    /**
     * Récupère une valeur bool.
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return (bool) $value;
    }

    /**
     * Récupère une valeur float.
     */
    public function getFloat(string $key): float
    {
        return (float) $this->get($key);
    }

    /**
     * Récupère une valeur JSON décodée.
     *
     * @return array<mixed>|null
     */
    public function getJson(string $key): ?array
    {
        $value = $this->getString($key);

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return \is_array($decoded) ? $decoded : null;
    }

    /**
     * Définit une valeur de configuration.
     */
    public function set(string $key, mixed $value, bool $html = false, ?int $idShopGroup = null, ?int $idShop = null): bool
    {
        return Configuration::updateValue($key, $value, $html, $idShopGroup, $idShop);
    }

    /**
     * Définit une valeur JSON.
     *
     * @param array<mixed> $value
     */
    public function setJson(string $key, array $value): bool
    {
        return $this->set($key, json_encode($value, JSON_THROW_ON_ERROR));
    }

    /**
     * Supprime une clé de configuration.
     */
    public function delete(string $key): bool
    {
        return Configuration::deleteByName($key);
    }

    /**
     * Vérifie si une clé existe.
     */
    public function has(string $key): bool
    {
        return Configuration::hasKey($key);
    }

    /**
     * Récupère une valeur globale (tous les shops).
     */
    public function getGlobal(string $key): mixed
    {
        return Configuration::getGlobalValue($key);
    }

    /**
     * Définit une valeur globale (tous les shops).
     */
    public function setGlobal(string $key, mixed $value): bool
    {
        return Configuration::updateGlobalValue($key, $value);
    }

    /**
     * Récupère plusieurs configurations d'un coup.
     *
     * @param string[] $keys
     *
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Définit plusieurs configurations d'un coup.
     *
     * @param array<string, mixed> $values
     */
    public function setMultiple(array $values): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value)) {
                $success = false;
            }
        }

        return $success;
    }
}
