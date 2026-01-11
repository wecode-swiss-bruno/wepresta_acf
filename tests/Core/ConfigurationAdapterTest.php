<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Core;

use Configuration;
use PHPUnit\Framework\TestCase;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;

class ConfigurationAdapterTest extends TestCase
{
    private ConfigurationAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        Configuration::reset();
        $this->adapter = new ConfigurationAdapter();
    }

    public function testGetReturnsNullForNonExistentKey(): void
    {
        $result = $this->adapter->get('NON_EXISTENT_KEY');

        $this->assertNull($result);
    }

    public function testGetReturnsDefaultForNonExistentKey(): void
    {
        $result = $this->adapter->get('NON_EXISTENT_KEY', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function testSetAndGet(): void
    {
        $this->adapter->set('TEST_KEY', 'test_value');

        $result = $this->adapter->get('TEST_KEY');

        $this->assertEquals('test_value', $result);
    }

    public function testSetInt(): void
    {
        $this->adapter->setInt('INT_KEY', 42);

        // Le mock Configuration stocke tout en string
        $result = $this->adapter->get('INT_KEY');

        $this->assertEquals(42, $result);
    }

    public function testSetBool(): void
    {
        $this->adapter->setBool('BOOL_KEY', true);

        $result = $this->adapter->get('BOOL_KEY');

        $this->assertEquals(1, $result);
    }

    public function testDelete(): void
    {
        $this->adapter->set('DELETE_KEY', 'value');
        $this->adapter->delete('DELETE_KEY');

        $result = $this->adapter->get('DELETE_KEY');

        $this->assertNull($result);
    }

    public function testHasKey(): void
    {
        $this->adapter->set('EXISTS_KEY', 'value');

        $this->assertTrue($this->adapter->hasKey('EXISTS_KEY'));
        $this->assertFalse($this->adapter->hasKey('NOT_EXISTS'));
    }
}
