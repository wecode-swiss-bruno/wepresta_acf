<?php
/**
 * SyncType - Auto-sync configuration form
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Form;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Sync configuration form for WePresta ACF module.
 */
class SyncType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auto_sync_enabled', SwitchType::class, [
                'label' => $this->trans('Enable auto-sync', 'Modules.Weprestaacf.Admin'),
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Weprestaacf.Admin',
        ]);
    }
}
