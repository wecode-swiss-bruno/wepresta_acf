<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Rules\Action;

use WeprestaAcf\Wedev\Extension\Rules\RuleContext;

/**
 * Interface pour les actions de règles.
 */
interface ActionInterface
{
    /**
     * Exécute l'action dans le contexte donné.
     */
    public function execute(RuleContext $context): void;
}

