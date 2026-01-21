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

namespace WeprestaAcf\Wedev\Core\Exception;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Exception pour les erreurs de configuration.
 *
 * Lancée lorsqu'une clé de configuration est manquante,
 * invalide ou mal formatée.
 *
 * @example
 * // Clé manquante
 * if (!Configuration::hasKey('MY_MODULE_API_KEY')) {
 *     throw ConfigurationException::missingKey('MY_MODULE_API_KEY');
 * }
 *
 * // Valeur invalide
 * $value = Configuration::get('MY_MODULE_TIMEOUT');
 * if (!is_numeric($value) || $value < 0) {
 *     throw ConfigurationException::invalidValue('MY_MODULE_TIMEOUT', $value);
 * }
 */
final class ConfigurationException extends ModuleException
{
    /**
     * Crée une exception pour une clé de configuration manquante.
     */
    public static function missingKey(string $key): self
    {
        return new self(
            \sprintf('Configuration key "%s" is missing.', $key),
            self::CODE_CONFIGURATION
        );
    }

    /**
     * Crée une exception pour une valeur de configuration invalide.
     */
    public static function invalidValue(string $key, mixed $value): self
    {
        return new self(
            \sprintf(
                'Invalid value for configuration key "%s": %s',
                $key,
                self::formatValue($value)
            ),
            self::CODE_CONFIGURATION
        );
    }

    /**
     * Crée une exception pour un type de valeur incorrect.
     */
    public static function invalidType(string $key, string $expectedType, mixed $actualValue): self
    {
        return new self(
            \sprintf(
                'Configuration key "%s" expects %s, got %s.',
                $key,
                $expectedType,
                get_debug_type($actualValue)
            ),
            self::CODE_CONFIGURATION
        );
    }

    /**
     * Crée une exception pour une configuration non initialisée.
     */
    public static function notInitialized(string $prefix): self
    {
        return new self(
            \sprintf('Configuration with prefix "%s" has not been initialized.', $prefix),
            self::CODE_CONFIGURATION
        );
    }

    /**
     * Formate une valeur pour l'affichage dans les messages d'erreur.
     */
    private static function formatValue(mixed $value): string
    {
        if (\is_array($value)) {
            return 'array(' . \count($value) . ')';
        }

        if (\is_object($value)) {
            return \get_class($value);
        }

        if (\is_string($value) && \strlen($value) > 50) {
            return '"' . substr($value, 0, 47) . '..."';
        }

        return var_export($value, true);
    }
}
