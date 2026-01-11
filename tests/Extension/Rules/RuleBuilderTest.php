<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Rules;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Extension\Rules\Action\CallableAction;
use WeprestaAcf\Extension\Rules\Condition\CartCondition;
use WeprestaAcf\Extension\Rules\Rule;
use WeprestaAcf\Extension\Rules\RuleBuilder;
use WeprestaAcf\Extension\Rules\RuleContext;

class RuleBuilderTest extends TestCase
{
    public function testCreateWithName(): void
    {
        $builder = RuleBuilder::create('test_rule');
        $rule = $builder->build();

        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertEquals('test_rule', $rule->getName());
    }

    public function testBuildWithPriority(): void
    {
        $rule = RuleBuilder::create('priority_rule')
            ->priority(100)
            ->build();

        $this->assertEquals(100, $rule->getPriority());
    }

    public function testBuildWithConditionAndAction(): void
    {
        $executed = false;

        $rule = RuleBuilder::create('action_rule')
            ->when(new CartCondition('total', '>=', 0))
            ->then(new CallableAction(function () use (&$executed): void {
                $executed = true;
            }))
            ->build();

        // Vérifier que la règle est bien construite
        $this->assertInstanceOf(Rule::class, $rule);

        // Exécuter la règle
        $context = RuleContext::with(['cart_total' => 100]);

        if ($rule->evaluate($context)) {
            $rule->execute($context);
        }

        $this->assertTrue($executed);
    }

    public function testBuildWithMultipleConditions(): void
    {
        $rule = RuleBuilder::create('multi_condition')
            ->when(new CartCondition('total', '>=', 50))
            ->and(new CartCondition('products_count', '>=', 2))
            ->build();

        $this->assertInstanceOf(Rule::class, $rule);
    }

    public function testChainedBuild(): void
    {
        $rule = RuleBuilder::create('chained')
            ->when(new CartCondition('total', '>', 0))
            ->priority(50)
            ->enabled(true)
            ->build();

        $this->assertEquals('chained', $rule->getName());
        $this->assertEquals(50, $rule->getPriority());
        $this->assertTrue($rule->isEnabled());
    }

    public function testDisabledRule(): void
    {
        $rule = RuleBuilder::create('disabled')
            ->enabled(false)
            ->build();

        $this->assertFalse($rule->isEnabled());
    }
}
