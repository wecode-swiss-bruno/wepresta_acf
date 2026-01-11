<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Domain\Entity\WeprestaAcfEntity;

/**
 * Tests unitaires pour l'entité WeprestaAcf.
 */
class WeprestaAcfTest extends TestCase
{
    public function testEntityCreation(): void
    {
        $entity = new WeprestaAcfEntity('Test Item');

        $this->assertNull($entity->getId());
        $this->assertSame('Test Item', $entity->getName());
        $this->assertSame('', $entity->getDescription());
        $this->assertTrue($entity->isActive());
        $this->assertSame(0, $entity->getPosition());
        $this->assertNotNull($entity->getCreatedAt());
        $this->assertNotNull($entity->getUpdatedAt());
    }

    public function testEntitySetters(): void
    {
        $entity = new WeprestaAcfEntity('Original');

        $entity->setName('Updated Name')
            ->setDescription('New description')
            ->setActive(false)
            ->setPosition(5);

        $this->assertSame('Updated Name', $entity->getName());
        $this->assertSame('New description', $entity->getDescription());
        $this->assertFalse($entity->isActive());
        $this->assertSame(5, $entity->getPosition());
    }

    public function testEntityActivateDeactivate(): void
    {
        $entity = new WeprestaAcfEntity('Test');

        $this->assertTrue($entity->isActive());

        $entity->deactivate();
        $this->assertFalse($entity->isActive());

        $entity->activate();
        $this->assertTrue($entity->isActive());
    }

    public function testEntityMoveUpDown(): void
    {
        $entity = new WeprestaAcfEntity('Test');
        $entity->setPosition(5);

        $entity->moveUp();
        $this->assertSame(4, $entity->getPosition());

        $entity->moveDown();
        $this->assertSame(5, $entity->getPosition());

        // Position 0 ne peut pas descendre en dessous
        $entity->setPosition(0);
        $entity->moveUp();
        $this->assertSame(0, $entity->getPosition());
    }

    public function testEntityToArray(): void
    {
        $entity = new WeprestaAcfEntity('Test');
        $entity->setId(1)
            ->setDescription('Description')
            ->setActive(true)
            ->setPosition(3);

        $array = $entity->toArray();

        $this->assertSame(1, $array['id']);
        $this->assertSame('Test', $array['name']);
        $this->assertSame('Description', $array['description']);
        $this->assertTrue($array['active']);
        $this->assertSame(3, $array['position']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function testEntityFromArray(): void
    {
        $data = [
            'id' => 42,
            'name' => 'From Array',
            'description' => 'Created from array',
            'active' => false,
            'position' => 7,
            'created_at' => '2024-01-15 10:30:00',
            'updated_at' => '2024-01-16 14:00:00',
        ];

        $entity = WeprestaAcfEntity::fromArray($data);

        $this->assertSame(42, $entity->getId());
        $this->assertSame('From Array', $entity->getName());
        $this->assertSame('Created from array', $entity->getDescription());
        $this->assertFalse($entity->isActive());
        $this->assertSame(7, $entity->getPosition());
    }

    /**
     * @dataProvider provideValidNames
     */
    public function testValidNamesAreAccepted(string $name): void
    {
        $entity = new WeprestaAcfEntity($name);
        $this->assertSame($name, $entity->getName());
    }

    public static function provideValidNames(): array
    {
        return [
            'simple' => ['Hello World'],
            'with numbers' => ['Test 123'],
            'with special chars' => ['Test & Module'],
            'unicode' => ['Été français'],
            'empty' => [''],
            'long' => [str_repeat('a', 255)],
        ];
    }

    public function testUpdatedAtChangesOnModification(): void
    {
        $entity = new WeprestaAcfEntity('Test');
        $originalUpdatedAt = $entity->getUpdatedAt();

        // Petite pause pour s'assurer que le timestamp change
        usleep(1000);

        $entity->setName('New Name');
        $newUpdatedAt = $entity->getUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $newUpdatedAt);
    }
}
