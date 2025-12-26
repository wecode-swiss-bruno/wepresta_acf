<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Application\Form;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Formulaire de configuration Symfony
 */
class ConfigurationType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('active', SwitchType::class, [
                'label' => $this->trans('Enable module', 'Modules.WeprestaAcf.Admin'),
                'help' => $this->trans('Enable or disable the module functionality.', 'Modules.WeprestaAcf.Admin'),
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'label' => $this->trans('Title', 'Modules.WeprestaAcf.Admin'),
                'help' => $this->trans('The title displayed on the front-office.', 'Modules.WeprestaAcf.Admin'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->trans('Title is required.', 'Modules.WeprestaAcf.Admin'),
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->trans('Description', 'Modules.WeprestaAcf.Admin'),
                'help' => $this->trans('Optional description for the module.', 'Modules.WeprestaAcf.Admin'),
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
            ])
            ->add('debug', SwitchType::class, [
                'label' => $this->trans('Debug mode', 'Modules.WeprestaAcf.Admin'),
                'help' => $this->trans('Enable detailed logging for troubleshooting.', 'Modules.WeprestaAcf.Admin'),
                'required' => false,
            ])
            ->add('cache_ttl', IntegerType::class, [
                'label' => $this->trans('Cache TTL (seconds)', 'Modules.WeprestaAcf.Admin'),
                'help' => $this->trans('Time-to-live for cached data.', 'Modules.WeprestaAcf.Admin'),
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 86400,
                        'notInRangeMessage' => $this->trans('Value must be between {{ min }} and {{ max }}.', 'Modules.WeprestaAcf.Admin'),
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 86400,
                ],
            ])
            ->add('api_enabled', SwitchType::class, [
                'label' => $this->trans('Enable REST API', 'Modules.WeprestaAcf.Admin'),
                'help' => $this->trans('Enable API endpoints for external integrations.', 'Modules.WeprestaAcf.Admin'),
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.WeprestaAcf.Admin',
        ]);
    }
}

