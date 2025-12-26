<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Rules;

use WeprestaAcf\Extension\Rules\RuleContext;
use PHPUnit\Framework\TestCase;

class RuleContextTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $context = new RuleContext();
        $context->set('key', 'value');

        $this->assertEquals('value', $context->get('key'));
    }

    public function testGetWithDefault(): void
    {
        $context = new RuleContext();

        $this->assertEquals('default', $context->get('non_existent', 'default'));
    }

    public function testHas(): void
    {
        $context = new RuleContext();
        $context->set('exists', true);

        $this->assertTrue($context->has('exists'));
        $this->assertFalse($context->has('not_exists'));
    }

    public function testRemove(): void
    {
        $context = new RuleContext();
        $context->set('key', 'value');
        $context->remove('key');

        $this->assertFalse($context->has('key'));
    }

    public function testAll(): void
    {
        $context = new RuleContext();
        $context->set('key1', 'value1');
        $context->set('key2', 'value2');

        $all = $context->all();

        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
        $this->assertEquals('value1', $all['key1']);
        $this->assertEquals('value2', $all['key2']);
    }

    public function testMerge(): void
    {
        $context = new RuleContext();
        $context->set('existing', 'original');
        $context->merge([
            'existing' => 'overwritten',
            'new_key' => 'new_value',
        ]);

        $this->assertEquals('overwritten', $context->get('existing'));
        $this->assertEquals('new_value', $context->get('new_key'));
    }

    public function testWithStaticConstructor(): void
    {
        $context = RuleContext::with([
            'key1' => 'value1',
            'key2' => 123,
        ]);

        $this->assertEquals('value1', $context->get('key1'));
        $this->assertEquals(123, $context->get('key2'));
    }
}

