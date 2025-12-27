<?php

declare(strict_types=1);

namespace WeprestaAcf\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * DependencyInjection Extension for WePresta ACF module.
 *
 * Registers the ACF form theme for Symfony forms.
 */
final class WeprestaAcfExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Services are loaded by PrestaShop module mechanism
    }

    /**
     * Prepend configuration to add ACF form theme.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container): void
    {
        // Add ACF form theme to Twig configuration
        $container->prependExtensionConfig('twig', [
            'form_themes' => [
                '@Modules/wepresta_acf/views/templates/admin/form-theme/acf_form_theme.html.twig',
            ],
        ]);
    }
}

