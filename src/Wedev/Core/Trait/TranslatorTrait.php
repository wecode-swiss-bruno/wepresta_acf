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

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait pour les traductions.
 */
trait TranslatorTrait
{
    protected ?TranslatorInterface $translator = null;

    protected string $translationDomain = 'Modules.Modulestarter.Admin';

    /**
     * Définit le translator.
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * Définit le domaine de traduction.
     */
    public function setTranslationDomain(string $domain): void
    {
        $this->translationDomain = $domain;
    }

    /**
     * Traduit un message.
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        if ($this->translator === null) {
            // Fallback: retourner l'ID avec les paramètres remplacés
            $translation = $id;

            foreach ($parameters as $key => $value) {
                $translation = str_replace($key, (string) $value, $translation);
            }

            return $translation;
        }

        return $this->translator->trans(
            $id,
            $parameters,
            $domain ?? $this->translationDomain
        );
    }

    /**
     * Traduit un message pour le front-office.
     */
    protected function transFront(string $id, array $parameters = []): string
    {
        $domain = str_replace('.Admin', '.Shop', $this->translationDomain);

        return $this->trans($id, $parameters, $domain);
    }

    /**
     * Traduit un message global PrestaShop.
     */
    protected function transGlobal(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'Admin.Global');
    }

    /**
     * Traduit un message d'action PrestaShop.
     */
    protected function transAction(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'Admin.Actions');
    }

    /**
     * Traduit un message de notification PrestaShop.
     */
    protected function transNotification(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'Admin.Notifications.Success');
    }
}
