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
 * Interface de base pour les repositories.
 *
 * Définit les opérations CRUD standard pour l'accès aux données.
 *
 * @template T of object
 *
 * @example
 * class ProductRepository implements RepositoryInterface
 * {
 *     public function findById(int $id): ?Product
 *     {
 *         $product = new Product($id);
 *         return $product->id ? $product : null;
 *     }
 *
 *     public function findAll(): array
 *     {
 *         return Product::getProducts($this->langId, 0, 0, 'id_product', 'ASC');
 *     }
 *
 *     public function save(object $entity): bool
 *     {
 *         return $entity->save();
 *     }
 *
 *     public function delete(int $id): bool
 *     {
 *         $product = new Product($id);
 *         return $product->delete();
 *     }
 * }
 */
interface RepositoryInterface
{
    /**
     * Trouve une entité par son ID.
     *
     * @return T|null
     */
    public function findById(int $id): ?object;

    /**
     * Retourne toutes les entités.
     *
     * @return array<T>
     */
    public function findAll(): array;

    /**
     * Persiste une entité.
     *
     * @param T $entity
     *
     * @return bool True si la sauvegarde a réussi
     */
    public function save(object $entity): bool;

    /**
     * Supprime une entité par son ID.
     *
     * @return bool True si la suppression a réussi
     */
    public function delete(int $id): bool;
}
