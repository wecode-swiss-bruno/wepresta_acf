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

namespace WeprestaAcf\Wedev\Core\Contract;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interface pour les Extensions WEDEV.
 *
 * Toutes les extensions (Http, Rules, Jobs, etc.) doivent implémenter
 * cette interface pour être détectables par l'ExtensionLoader.
 *
 * @example
 * final class HttpClient implements ExtensionInterface
 * {
 *     public static function getName(): string
 *     {
 *         return 'Http';
 *     }
 *
 *     public static function getVersion(): string
 *     {
 *         return '1.0.0';
 *     }
 *
 *     public static function getDependencies(): array
 *     {
 *         return []; // Pas de dépendance
 *     }
 * }
 *
 * final class NotificationService implements ExtensionInterface
 * {
 *     public static function getName(): string
 *     {
 *         return 'Notifications';
 *     }
 *
 *     public static function getVersion(): string
 *     {
 *         return '1.0.0';
 *     }
 *
 *     public static function getDependencies(): array
 *     {
 *         return ['Http']; // Requiert l'extension Http
 *     }
 * }
 */
interface ExtensionInterface
{
    /**
     * Retourne le nom de l'extension.
     *
     * Doit correspondre au nom du dossier dans Extension/
     */
    public static function getName(): string;

    /**
     * Retourne la version de l'extension.
     *
     * Format SemVer: MAJOR.MINOR.PATCH
     */
    public static function getVersion(): string;

    /**
     * Retourne les dépendances de l'extension.
     *
     * @return array<string> Noms des extensions requises
     */
    public static function getDependencies(): array;
}
