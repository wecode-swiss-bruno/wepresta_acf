<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
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
