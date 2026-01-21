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

/**
 * ConfigurationType - Module configuration form.
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Form;


if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Configuration form for WePresta ACF module.
 */
class ConfigurationType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // General Settings Tab
        $builder
            ->add('debug', SwitchType::class, [
                'label' => $this->trans('Debug mode', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Enable detailed logging for troubleshooting.', 'Modules.Weprestaacf.Admin'),
                'required' => false,
            ])
            ->add('max_file_size', IntegerType::class, [
                'label' => $this->trans('Max file size (MB)', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Maximum file size for uploads in megabytes.', 'Modules.Weprestaacf.Admin'),
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 100,
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Weprestaacf.Admin',
        ]);
    }
}
