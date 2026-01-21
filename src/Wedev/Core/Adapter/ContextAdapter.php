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

use Cart;
use Configuration;
use Context;
use Controller;
use Cookie;
use Currency;
use Customer;
use Employee;
use Language;
use Link;
use Shop;
use Smarty;

/**
 * Adapter pour accéder au Context PrestaShop.
 *
 * Fournit une interface typée et testable pour Context::getContext().
 */
class ContextAdapter
{
    private ?Context $context = null;

    /**
     * Récupère le contexte.
     */
    public function getContext(): Context
    {
        if ($this->context === null) {
            $this->context = Context::getContext();
        }

        return $this->context;
    }

    /**
     * Définit le contexte (pour les tests).
     */
    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    // =========================================================================
    // SHOP
    // =========================================================================

    public function getShop(): ?Shop
    {
        return $this->getContext()->shop;
    }

    public function getShopId(): int
    {
        return (int) ($this->getShop()?->id ?? 1);
    }

    public function getShopGroupId(): int
    {
        return (int) ($this->getShop()?->id_shop_group ?? 1);
    }

    public function isMultishop(): bool
    {
        return Shop::isFeatureActive();
    }

    // =========================================================================
    // LANGUAGE
    // =========================================================================

    public function getLanguage(): ?Language
    {
        return $this->getContext()->language;
    }

    public function getLanguageId(): int
    {
        return (int) ($this->getLanguage()?->id ?? 1);
    }

    public function getLangId(): int
    {
        return $this->getLanguageId();
    }

    public function getLanguageIso(): string
    {
        return $this->getLanguage()?->iso_code ?? 'en';
    }

    public function isRtl(): bool
    {
        return (bool) ($this->getLanguage()?->is_rtl ?? false);
    }

    // =========================================================================
    // CURRENCY
    // =========================================================================

    public function getCurrency(): ?Currency
    {
        return $this->getContext()->currency;
    }

    public function getCurrencyId(): int
    {
        return (int) ($this->getCurrency()?->id ?? 1);
    }

    public function getCurrencyIso(): string
    {
        return $this->getCurrency()?->iso_code ?? 'EUR';
    }

    public function getCurrencySign(): string
    {
        return $this->getCurrency()?->sign ?? '€';
    }

    // =========================================================================
    // CUSTOMER
    // =========================================================================

    public function getCustomer(): ?Customer
    {
        return $this->getContext()->customer;
    }

    public function getCustomerId(): int
    {
        return (int) ($this->getCustomer()?->id ?? 0);
    }

    public function isCustomerLogged(): bool
    {
        return $this->getCustomer()?->isLogged() ?? false;
    }

    public function getCustomerEmail(): string
    {
        return $this->getCustomer()?->email ?? '';
    }

    public function getCustomerGroups(): array
    {
        if (!$this->isCustomerLogged()) {
            return [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
        }

        return $this->getCustomer()->getGroups();
    }

    // =========================================================================
    // EMPLOYEE (Admin)
    // =========================================================================

    public function getEmployee(): ?Employee
    {
        return $this->getContext()->employee;
    }

    public function getEmployeeId(): int
    {
        return (int) ($this->getEmployee()?->id ?? 0);
    }

    public function isEmployeeLogged(): bool
    {
        return $this->getEmployee() !== null && $this->getEmployee()->id > 0;
    }

    // =========================================================================
    // CART
    // =========================================================================

    public function getCart(): ?Cart
    {
        return $this->getContext()->cart;
    }

    public function getCartId(): int
    {
        return (int) ($this->getCart()?->id ?? 0);
    }

    public function getCartProductsCount(): int
    {
        return (int) ($this->getCart()?->nbProducts() ?? 0);
    }

    // =========================================================================
    // CONTROLLER
    // =========================================================================

    public function getController(): ?Controller
    {
        return $this->getContext()->controller;
    }

    public function getControllerName(): string
    {
        return $this->getController()?->php_self ?? '';
    }

    public function isAdminContext(): bool
    {
        return \defined('_PS_ADMIN_DIR_');
    }

    public function isFrontContext(): bool
    {
        return !$this->isAdminContext();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function getLink(): Link
    {
        return $this->getContext()->link;
    }

    public function getSmarty(): Smarty
    {
        return $this->getContext()->smarty;
    }

    public function getCookie(): Cookie
    {
        return $this->getContext()->cookie;
    }

    /**
     * Assigne des variables au template Smarty.
     *
     * @param array<string, mixed> $variables
     */
    public function assignSmarty(array $variables): void
    {
        $this->getSmarty()->assign($variables);
    }
}
