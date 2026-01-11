<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WeprestaAcf\Application\Service\WeprestaAcfService;
use WeprestaAcf\Domain\Entity\WeprestaAcfEntity;
use WeprestaAcf\Domain\Repository\WeprestaAcfRepositoryInterface;
use WeprestaAcf\Infrastructure\Adapter\ConfigurationAdapter;

/**
 * Tests pour WeprestaAcfService.
 */
class WeprestaAcfServiceTest extends TestCase
{
    private WeprestaAcfRepositoryInterface&MockObject $repository;

    private ConfigurationAdapter&MockObject $config;

    private WeprestaAcfService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(WeprestaAcfRepositoryInterface::class);
        $this->config = $this->createMock(ConfigurationAdapter::class);
        $this->service = new WeprestaAcfService($this->repository, $this->config);
    }

    public function testGetActiveItemsReturnsEmptyArrayWhenDisabled(): void
    {
        $this->config
            ->method('getBool')
            ->with('WEPRESTA_ACF_ACTIVE')
            ->willReturn(false);

        $result = $this->service->getActiveItems();

        $this->assertSame([], $result);
    }

    public function testGetActiveItemsReturnsItemsWhenEnabled(): void
    {
        $items = [
            new WeprestaAcfEntity('Item 1'),
            new WeprestaAcfEntity('Item 2'),
        ];

        $this->config
            ->method('getBool')
            ->with('WEPRESTA_ACF_ACTIVE')
            ->willReturn(true);

        $this->repository
            ->method('findActive')
            ->willReturn($items);

        $result = $this->service->getActiveItems();

        $this->assertCount(2, $result);
        $this->assertSame('Item 1', $result[0]->getName());
        $this->assertSame('Item 2', $result[1]->getName());
    }

    public function testGetItemReturnsEntityWhenFound(): void
    {
        $entity = new WeprestaAcfEntity('Test');
        $entity->setId(1);

        $this->repository
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $result = $this->service->getItem(1);

        $this->assertNotNull($result);
        $this->assertSame('Test', $result->getName());
    }

    public function testGetItemReturnsNullWhenNotFound(): void
    {
        $this->repository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getItem(999);

        $this->assertNull($result);
    }

    public function testCreateItemSavesAndReturnsEntity(): void
    {
        $this->repository
            ->method('count')
            ->willReturn(5);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $result = $this->service->createItem('New Item', 'Description');

        $this->assertSame('New Item', $result->getName());
        $this->assertSame('Description', $result->getDescription());
        $this->assertSame(5, $result->getPosition());
    }

    public function testUpdateItemReturnsNullWhenNotFound(): void
    {
        $this->repository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->updateItem(999, ['name' => 'Updated']);

        $this->assertNull($result);
    }

    public function testUpdateItemUpdatesAndReturnsEntity(): void
    {
        $entity = new WeprestaAcfEntity('Original');
        $entity->setId(1);

        $this->repository
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $result = $this->service->updateItem(1, [
            'name' => 'Updated',
            'description' => 'New desc',
            'active' => false,
            'position' => 10,
        ]);

        $this->assertNotNull($result);
        $this->assertSame('Updated', $result->getName());
        $this->assertSame('New desc', $result->getDescription());
        $this->assertFalse($result->isActive());
        $this->assertSame(10, $result->getPosition());
    }

    public function testDeleteItemReturnsFalseWhenNotFound(): void
    {
        $this->repository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->deleteItem(999);

        $this->assertFalse($result);
    }

    public function testDeleteItemDeletesAndReturnsTrue(): void
    {
        $entity = new WeprestaAcfEntity('To Delete');
        $entity->setId(1);

        $this->repository
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($entity);

        $result = $this->service->deleteItem(1);

        $this->assertTrue($result);
    }

    public function testToggleItemTogglesActiveState(): void
    {
        $entity = new WeprestaAcfEntity('Toggle Me');
        $entity->setId(1);
        $entity->setActive(true);

        $this->repository
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $result = $this->service->toggleItem(1);

        $this->assertNotNull($result);
        $this->assertFalse($result->isActive());
    }

    public function testIsEnabledDelegatesToConfig(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getBool')
            ->with('WEPRESTA_ACF_ACTIVE')
            ->willReturn(true);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testGetTitleDelegatesToConfig(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('WEPRESTA_ACF_TITLE', 'Module Starter')
            ->willReturn('Custom Title');

        $this->assertSame('Custom Title', $this->service->getTitle());
    }

    public function testGetCacheTtlDelegatesToConfig(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getInt')
            ->with('WEPRESTA_ACF_CACHE_TTL', 3600)
            ->willReturn(7200);

        $this->assertSame(7200, $this->service->getCacheTtl());
    }
}
