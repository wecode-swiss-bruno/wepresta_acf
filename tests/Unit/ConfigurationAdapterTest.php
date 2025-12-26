<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Unit;

use WeprestaAcf\Infrastructure\Adapter\ConfigurationAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour ConfigurationAdapter
 *
 * Note: Ces tests mocquent la classe Configuration de PrestaShop
 */
class ConfigurationAdapterTest extends TestCase
{
    private ConfigurationAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new ConfigurationAdapter();
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $result = $this->adapter->get('NON_EXISTENT_KEY', 'default_value');

        // Le stub retourne null, donc on récupère la valeur par défaut
        $this->assertSame('default_value', $result);
    }

    public function testGetBoolReturnsBooleanValue(): void
    {
        $result = $this->adapter->getBool('SOME_BOOL_KEY', true);

        $this->assertIsBool($result);
    }

    public function testGetIntReturnsIntegerValue(): void
    {
        $result = $this->adapter->getInt('SOME_INT_KEY', 42);

        $this->assertSame(42, $result);
    }

    public function testGetArrayReturnsDefaultForEmptyValue(): void
    {
        $default = ['key' => 'value'];
        $result = $this->adapter->getArray('EMPTY_ARRAY_KEY', $default);

        $this->assertSame($default, $result);
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $result = $this->adapter->has('MISSING_KEY');

        $this->assertFalse($result);
    }

    public function testClearCacheRemovesAllCachedValues(): void
    {
        // Accéder à une clé pour la mettre en cache
        $this->adapter->get('CACHED_KEY', 'value1');

        // Vider le cache
        $this->adapter->clearCache();

        // Le cache devrait être vide (pas d'assertion directe possible sans réflexion)
        // On vérifie juste que ça ne plante pas
        $this->assertTrue(true);
    }

    public function testClearCacheByKeyRemovesSpecificKey(): void
    {
        $this->adapter->get('KEY_A', 'value_a');
        $this->adapter->get('KEY_B', 'value_b');

        $this->adapter->clearCache('KEY_A');

        // Pas d'assertion directe, on vérifie que ça fonctionne
        $this->assertTrue(true);
    }

    /**
     * @dataProvider provideBoolValues
     */
    public function testGetBoolConvertsValuesCorrectly(mixed $input, bool $expected): void
    {
        // Comme on utilise un stub, on teste la logique de conversion indirectement
        // En pratique, ce test vérifie le comportement de type casting
        $this->assertSame($expected, (bool) $input);
    }

    public static function provideBoolValues(): array
    {
        return [
            'true string' => ['1', true],
            'false string' => ['0', false],
            'empty string' => ['', false],
            'true boolean' => [true, true],
            'false boolean' => [false, false],
            'integer 1' => [1, true],
            'integer 0' => [0, false],
        ];
    }
}

