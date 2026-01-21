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

use WeprestaAcf\Wedev\Core\Adapter\ShopAdapter;

/**
 * Trait pour la gestion multi-shop dans les services.
 *
 * Fournit des méthodes utilitaires pour les opérations multi-boutique.
 *
 * @example
 * class ProductService
 * {
 *     use MultiShopTrait;
 *
 *     public function updateAllShops(int $productId, array $data): void
 *     {
 *         if (!$this->isMultiShopActive()) {
 *             $this->updateProduct($productId, $data);
 *             return;
 *         }
 *
 *         $this->forEachShop(function(array $shop) use ($productId, $data) {
 *             $this->updateProduct($productId, $data);
 *         });
 *     }
 *
 *     public function getProductForCurrentShop(int $productId): Product
 *     {
 *         $shopId = $this->getCurrentShopId();
 *         return $this->repository->findByIdAndShop($productId, $shopId);
 *     }
 * }
 */
trait MultiShopTrait
{
    private ?ShopAdapter $shopAdapter = null;

    /**
     * Retourne l'instance du ShopAdapter.
     */
    protected function getShopAdapter(): ShopAdapter
    {
        if ($this->shopAdapter === null) {
            $this->shopAdapter = new ShopAdapter();
        }

        return $this->shopAdapter;
    }

    /**
     * Vérifie si la fonctionnalité multi-shop est active.
     */
    protected function isMultiShopActive(): bool
    {
        return $this->getShopAdapter()->isMultiShopActive();
    }

    /**
     * Retourne l'ID de la boutique courante.
     */
    protected function getCurrentShopId(): int
    {
        return $this->getShopAdapter()->getCurrentShopId();
    }

    /**
     * Retourne l'ID du groupe de la boutique courante.
     */
    protected function getCurrentShopGroupId(): int
    {
        return $this->getShopAdapter()->getCurrentShopGroupId();
    }

    /**
     * Vérifie si on est en contexte de boutique unique.
     */
    protected function isSingleShopContext(): bool
    {
        return $this->getShopAdapter()->isSingleShopContext();
    }

    /**
     * Exécute une callback pour chaque boutique active.
     *
     * @param callable(array{id_shop: int, name: string, active: bool, id_shop_group: int}): void $callback
     */
    protected function forEachShop(callable $callback): void
    {
        $this->getShopAdapter()->forEachShop($callback);
    }

    /**
     * Exécute une callback dans le contexte d'une boutique spécifique.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    protected function executeInShopContext(int $shopId, callable $callback): mixed
    {
        return $this->getShopAdapter()->executeInShopContext($shopId, $callback);
    }

    /**
     * Retourne les IDs des boutiques actives.
     *
     * @return array<int>
     */
    protected function getActiveShopIds(): array
    {
        return $this->getShopAdapter()->getActiveShopIds();
    }

    /**
     * Exécute une callback pour chaque boutique et collecte les résultats.
     *
     * @template T
     *
     * @param callable(array{id_shop: int, name: string, active: bool, id_shop_group: int}): T $callback
     *
     * @return array<int, T> Résultats indexés par shop_id
     */
    protected function mapShops(callable $callback): array
    {
        return $this->getShopAdapter()->mapShops($callback);
    }
}
