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

namespace WeprestaAcf\Wedev\Core\Trait;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Exception;
use Module;

/**
 * Trait pour les classes qui ont besoin d'accéder au module.
 */
trait ModuleAwareTrait
{
    protected ?Module $module = null;

    /**
     * Définit l'instance du module.
     */
    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    /**
     * Récupère l'instance du module.
     */
    public function getModule(): ?Module
    {
        return $this->module;
    }

    /**
     * Récupère le nom du module.
     */
    public function getModuleName(): string
    {
        return $this->module?->name ?? '';
    }

    /**
     * Récupère la version du module.
     */
    public function getModuleVersion(): string
    {
        return $this->module?->version ?? '0.0.0';
    }

    /**
     * Récupère le chemin du module.
     */
    public function getModulePath(): string
    {
        if ($this->module === null) {
            return '';
        }

        return _PS_MODULE_DIR_ . $this->module->name . '/';
    }

    /**
     * Récupère un service du container Symfony.
     *
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T|null
     */
    public function getService(string $serviceId): ?object
    {
        if ($this->module === null) {
            return null;
        }

        try {
            $container = $this->module->get('service_container');

            if ($container === null || ! $container->has($serviceId)) {
                return null;
            }

            return $container->get($serviceId);
        } catch (Exception $e) {
            return null;
        }
    }
}
