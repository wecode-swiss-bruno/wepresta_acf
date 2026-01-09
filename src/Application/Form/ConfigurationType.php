<?php
/**
 * ConfigurationType - Module configuration form
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Form;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

