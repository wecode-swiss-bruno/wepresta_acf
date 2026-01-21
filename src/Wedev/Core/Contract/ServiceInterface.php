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
 * Interface marqueur pour les services injectables.
 *
 * Utilisée pour identifier les services qui peuvent être
 * injectés via le conteneur de dépendances Symfony.
 *
 * Cette interface est intentionnellement vide (marqueur).
 * Elle permet d'identifier les services dans les configurations
 * et lors de l'analyse statique.
 *
 * @example
 * class ProductService implements ServiceInterface
 * {
 *     public function __construct(
 *         private readonly ProductRepository $repository,
 *         private readonly CacheService $cache
 *     ) {}
 *
 *     public function getProduct(int $id): ?Product
 *     {
 *         return $this->cache->get(
 *             "product_{$id}",
 *             fn() => $this->repository->findById($id)
 *         );
 *     }
 * }
 *
 * // Dans services.yml
 * services:
 *     _instanceof:
 *         ModuleStarter\Core\Contract\ServiceInterface:
 *             public: true
 */
interface ServiceInterface
{
}
