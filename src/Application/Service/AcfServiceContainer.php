<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Template\FieldGroupExporter;
use WeprestaAcf\Application\Template\FieldGroupImporter;
use WeprestaAcf\Domain\Event\EventBus;
use WeprestaAcf\Infrastructure\Repository\AcfGroupRepository;
use WeprestaAcf\Infrastructure\Repository\AcfFieldRepository;
use WeprestaAcf\Infrastructure\Repository\AcfFieldValueRepository;

/**
 * Manual service container for WePresta ACF
 *
 * Fallback when Symfony DI is not available in hooks.
 * PrestaShop 9 does not always load module services automatically.
 */
final class AcfServiceContainer
{
    private static ?\Module $module = null;
    /** @var array<string, object> */
    private static array $services = [];
    private static string $moduleVersion = '1.0.0';

    public static function init(\Module $module): void { self::$module = $module; }
    public static function reset(): void { self::$module = null; self::$services = []; }
    public static function setModuleVersion(string $version): void { self::$moduleVersion = $version; }

    /** @template T of object @param class-string<T> $serviceClass @return T|null */
    public static function tryGet(string $serviceClass): ?object
    {
        if (self::$module !== null) {
            try {
                $service = self::$module->get($serviceClass);
                if ($service instanceof $serviceClass) { return $service; }
            } catch (\Exception $e) {}
        }
        return null;
    }

    public static function getFieldTypeRegistry(): FieldTypeRegistry
    {
        if (!isset(self::$services[FieldTypeRegistry::class])) {
            $service = self::tryGet(FieldTypeRegistry::class);
            self::$services[FieldTypeRegistry::class] = $service ?? new FieldTypeRegistry();
        }
        /** @var FieldTypeRegistry */
        return self::$services[FieldTypeRegistry::class];
    }

    public static function getGroupRepository(): AcfGroupRepository
    {
        if (!isset(self::$services[AcfGroupRepository::class])) {
            $service = self::tryGet(AcfGroupRepository::class);
            self::$services[AcfGroupRepository::class] = $service ?? new AcfGroupRepository();
        }
        /** @var AcfGroupRepository */
        return self::$services[AcfGroupRepository::class];
    }

    public static function getFieldRepository(): AcfFieldRepository
    {
        if (!isset(self::$services[AcfFieldRepository::class])) {
            $service = self::tryGet(AcfFieldRepository::class);
            self::$services[AcfFieldRepository::class] = $service ?? new AcfFieldRepository();
        }
        /** @var AcfFieldRepository */
        return self::$services[AcfFieldRepository::class];
    }

    public static function getValueRepository(): AcfFieldValueRepository
    {
        if (!isset(self::$services[AcfFieldValueRepository::class])) {
            $service = self::tryGet(AcfFieldValueRepository::class);
            self::$services[AcfFieldValueRepository::class] = $service ?? new AcfFieldValueRepository();
        }
        /** @var AcfFieldValueRepository */
        return self::$services[AcfFieldValueRepository::class];
    }

    public static function getValueProvider(): ValueProvider
    {
        if (!isset(self::$services[ValueProvider::class])) {
            $service = self::tryGet(ValueProvider::class);
            self::$services[ValueProvider::class] = $service ?? new ValueProvider(self::getValueRepository());
        }
        /** @var ValueProvider */
        return self::$services[ValueProvider::class];
    }

    public static function getValueHandler(): ValueHandler
    {
        if (!isset(self::$services[ValueHandler::class])) {
            $service = self::tryGet(ValueHandler::class);
            self::$services[ValueHandler::class] = $service ?? new ValueHandler(
                self::getFieldRepository(),
                self::getValueRepository(),
                self::getFieldTypeRegistry()
            );
        }
        /** @var ValueHandler */
        return self::$services[ValueHandler::class];
    }

    public static function getFileUploadService(): FileUploadService
    {
        if (!isset(self::$services[FileUploadService::class])) {
            $service = self::tryGet(FileUploadService::class);
            $moduleDir = _PS_MODULE_DIR_ . 'wepresta_acf/';
            self::$services[FileUploadService::class] = $service ?? new FileUploadService($moduleDir);
        }
        /** @var FileUploadService */
        return self::$services[FileUploadService::class];
    }

    public static function getSlugGenerator(): SlugGenerator
    {
        if (!isset(self::$services[SlugGenerator::class])) {
            $service = self::tryGet(SlugGenerator::class);
            self::$services[SlugGenerator::class] = $service ?? new SlugGenerator();
        }
        /** @var SlugGenerator */
        return self::$services[SlugGenerator::class];
    }

    public static function getEventBus(): EventBus
    {
        if (!isset(self::$services[EventBus::class])) {
            $service = self::tryGet(EventBus::class);
            self::$services[EventBus::class] = $service ?? new EventBus();
        }
        /** @var EventBus */
        return self::$services[EventBus::class];
    }

    public static function getLocationProviderRegistry(): LocationProviderRegistry
    {
        if (!isset(self::$services[LocationProviderRegistry::class])) {
            $service = self::tryGet(LocationProviderRegistry::class);
            self::$services[LocationProviderRegistry::class] = $service ?? new LocationProviderRegistry();
        }
        /** @var LocationProviderRegistry */
        return self::$services[LocationProviderRegistry::class];
    }

    public static function getFieldGroupExporter(): FieldGroupExporter
    {
        if (!isset(self::$services[FieldGroupExporter::class])) {
            $service = self::tryGet(FieldGroupExporter::class);
            self::$services[FieldGroupExporter::class] = $service ?? new FieldGroupExporter(
                self::getGroupRepository(),
                self::getFieldRepository()
            );
        }
        /** @var FieldGroupExporter */
        return self::$services[FieldGroupExporter::class];
    }

    public static function getFieldGroupImporter(): FieldGroupImporter
    {
        if (!isset(self::$services[FieldGroupImporter::class])) {
            $service = self::tryGet(FieldGroupImporter::class);
            self::$services[FieldGroupImporter::class] = $service ?? new FieldGroupImporter(
                self::getGroupRepository(),
                self::getFieldRepository()
            );
        }
        /** @var FieldGroupImporter */
        return self::$services[FieldGroupImporter::class];
    }

    /** @template T of object @param class-string<T>|string $serviceId @return T|null */
    public static function getCoreService(string $serviceId): ?object
    {
        if (self::$module === null) { return null; }
        try { return self::$module->get($serviceId); }
        catch (\Exception $e) { return null; }
    }
}

