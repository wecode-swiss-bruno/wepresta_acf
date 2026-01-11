<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Rules\Condition;

use WeprestaAcf\Wedev\Extension\Rules\RuleContext;

/**
 * Interface pour les conditions de règles.
 */
interface ConditionInterface
{
    /**
     * Évalue la condition dans le contexte donné.
     */
    public function evaluate(RuleContext $context): bool;
}
