<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Audit;

use WeprestaAcf\Extension\Audit\AuditEntry;
use PHPUnit\Framework\TestCase;

class AuditEntryTest extends TestCase
{
    public function testCreateForCreate(): void
    {
        $entry = AuditEntry::createForCreate(
            entityType: 'Product',
            entityId: 123,
            newData: ['name' => 'Test Product', 'price' => 19.99]
        );

        $this->assertEquals(AuditEntry::ACTION_CREATE, $entry->getAction());
        $this->assertEquals('Product', $entry->getEntityType());
        $this->assertEquals(123, $entry->getEntityId());
        $this->assertNull($entry->getOldData());
        $this->assertEquals(['name' => 'Test Product', 'price' => 19.99], $entry->getNewData());
    }

    public function testCreateForUpdate(): void
    {
        $entry = AuditEntry::createForUpdate(
            entityType: 'Product',
            entityId: 123,
            oldData: ['price' => 19.99],
            newData: ['price' => 29.99]
        );

        $this->assertEquals(AuditEntry::ACTION_UPDATE, $entry->getAction());
        $this->assertEquals(['price' => 19.99], $entry->getOldData());
        $this->assertEquals(['price' => 29.99], $entry->getNewData());
    }

    public function testCreateForDelete(): void
    {
        $entry = AuditEntry::createForDelete(
            entityType: 'Product',
            entityId: 123,
            oldData: ['name' => 'Deleted Product']
        );

        $this->assertEquals(AuditEntry::ACTION_DELETE, $entry->getAction());
        $this->assertEquals(['name' => 'Deleted Product'], $entry->getOldData());
        $this->assertNull($entry->getNewData());
    }

    public function testCreateForView(): void
    {
        $entry = AuditEntry::createForView(
            entityType: 'Customer',
            entityId: 456
        );

        $this->assertEquals(AuditEntry::ACTION_VIEW, $entry->getAction());
        $this->assertEquals('Customer', $entry->getEntityType());
        $this->assertEquals(456, $entry->getEntityId());
    }

    public function testWithUser(): void
    {
        $entry = AuditEntry::createForView('Product', 1)
            ->withUser(99, 'Admin', '192.168.1.1');

        $this->assertEquals(99, $entry->getUserId());
        $this->assertEquals('Admin', $entry->getUserType());
        $this->assertEquals('192.168.1.1', $entry->getIpAddress());
    }

    public function testToArray(): void
    {
        $entry = AuditEntry::createForCreate('Product', 1, ['name' => 'Test']);
        $array = $entry->toArray();

        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('entity_type', $array);
        $this->assertArrayHasKey('entity_id', $array);
        $this->assertArrayHasKey('new_data', $array);
        $this->assertArrayHasKey('created_at', $array);
    }

    public function testGetChanges(): void
    {
        $entry = AuditEntry::createForUpdate(
            'Product',
            1,
            ['name' => 'Old Name', 'price' => 10.0, 'stock' => 100],
            ['name' => 'New Name', 'price' => 10.0, 'stock' => 50]
        );

        $changes = $entry->getChanges();

        $this->assertArrayHasKey('name', $changes);
        $this->assertArrayHasKey('stock', $changes);
        $this->assertArrayNotHasKey('price', $changes); // Pas changé

        $this->assertEquals('Old Name', $changes['name']['old']);
        $this->assertEquals('New Name', $changes['name']['new']);
    }
}

