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
            ->add('active', SwitchType::class, [
                'label' => $this->trans('Enable module', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Enable or disable the module functionality.', 'Modules.Weprestaacf.Admin'),
                'required' => false,
            ])
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
            ])

            // Sync Settings Tab
            ->add('sync_enabled', SwitchType::class, [
                'label' => $this->trans('Enable JSON sync', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Enable synchronization of field groups with theme JSON files.', 'Modules.Weprestaacf.Admin'),
                'required' => false,
            ])
            ->add('auto_sync_on_save', SwitchType::class, [
                'label' => $this->trans('Auto-sync on save', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Automatically export field groups to JSON when saved.', 'Modules.Weprestaacf.Admin'),
                'required' => false,
            ])
            ->add('sync_on_install', SwitchType::class, [
                'label' => $this->trans('Sync on module install', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Automatically import field groups from theme when module is installed.', 'Modules.Weprestaacf.Admin'),
                'required' => false,
            ])
            ->add('sync_path_type', ChoiceType::class, [
                'label' => $this->trans('Sync path', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Where to store/read JSON files.', 'Modules.Weprestaacf.Admin'),
                'choices' => [
                    $this->trans('Active Theme', 'Modules.Weprestaacf.Admin') => 'theme',
                    $this->trans('Parent Theme', 'Modules.Weprestaacf.Admin') => 'parent',
                    $this->trans('Custom Path', 'Modules.Weprestaacf.Admin') => 'custom',
                ],
            ])
            ->add('sync_custom_path', TextType::class, [
                'label' => $this->trans('Custom path', 'Modules.Weprestaacf.Admin'),
                'help' => $this->trans('Absolute path to store JSON files (only when custom path is selected).', 'Modules.Weprestaacf.Admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => '/var/www/html/themes/my-theme/acf/',
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

