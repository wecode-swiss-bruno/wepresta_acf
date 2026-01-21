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

namespace WeprestaAcf\Application\Form\Type;


if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Container form type for ACF fields.
 *
 * This type extends HiddenType and stores pre-rendered ACF HTML
 * in a data attribute. JavaScript then injects this HTML into the DOM.
 */
final class AcfContainerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'label' => false,
            'acf_html' => '',
        ]);

        $resolver->setAllowedTypes('acf_html', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Store HTML in a data attribute for JavaScript injection
        // Base64 encode to avoid HTML parsing issues
        $view->vars['attr']['data-acf-html'] = base64_encode($options['acf_html']);
        $view->vars['attr']['class'] = 'acf-container-data';
        $view->vars['attr']['id'] = 'acf-container-' . uniqid();
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'acf_container';
    }
}
