<?php
/**
 * Module Starter - Front Controller Display
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class WeprestaAcfDisplayModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool Si vrai, le template utilise le layout du thème
     */
    public $php_self = 'display';

    /**
     * Initialisation du contrôleur
     */
    public function init(): void
    {
        parent::init();

        // Vérifier que le module est actif
        if (!Configuration::get('WEPRESTA_ACF_ACTIVE')) {
            Tools::redirect('index.php?controller=404');
        }
    }

    /**
     * Définir les médias (CSS/JS)
     */
    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'wepresta_acf-display',
            'modules/' . $this->module->name . '/views/dist/front.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->registerJavascript(
            'wepresta_acf-display',
            'modules/' . $this->module->name . '/views/dist/front.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    /**
     * Définition du titre de la page
     */
    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = Configuration::get('WEPRESTA_ACF_TITLE') ?: 'Module Starter';
        return $page;
    }

    /**
     * Construction de la page
     */
    public function initContent(): void
    {
        parent::initContent();

        $this->context->smarty->assign([
            'wepresta_acf' => [
                'title' => Configuration::get('WEPRESTA_ACF_TITLE'),
                'description' => Configuration::get('WEPRESTA_ACF_DESCRIPTION'),
            ],
        ]);

        $this->setTemplate('module:wepresta_acf/views/templates/front/display.tpl');
    }

    /**
     * Définir le fil d'Ariane
     */
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => Configuration::get('WEPRESTA_ACF_TITLE') ?: 'Module Starter',
            'url' => $this->context->link->getModuleLink('wepresta_acf', 'display'),
        ];

        return $breadcrumb;
    }
}

