<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Exception;
use Module;
use WeprestaAcf\Application\Brick\AcfBrickDiscovery;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Smarty\AcfSmartyWrapper;
use WeprestaAcf\Application\Template\FieldGroupExporter;
use WeprestaAcf\Application\Template\FieldGroupImporter;
use WeprestaAcf\Application\Twig\AcfTwigExtension;
use WeprestaAcf\Infrastructure\Repository\AcfFieldRepository;
use WeprestaAcf\Infrastructure\Repository\AcfFieldValueRepository;
use WeprestaAcf\Infrastructure\Repository\AcfGroupRepository;
use WeprestaAcf\Infrastructure\Repository\CptPostRepository;
use WeprestaAcf\Infrastructure\Repository\CptRelationRepository;
use WeprestaAcf\Infrastructure\Repository\CptTaxonomyRepository;
use WeprestaAcf\Infrastructure\Repository\CptTermRepository;
use WeprestaAcf\Infrastructure\Repository\CptTypeRepository;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;
use WeprestaAcf\Wedev\Extension\Events\DomainEventDispatcher;

/**
 * Manual service container for WePresta ACF.
 *
 * Fallback when Symfony DI is not available in hooks.
 * PrestaShop 9 does not always load module services automatically.
 */
final class AcfServiceContainer
{
    private static ?Module $module = null;

    /** @var array<string, object> */
    private static array $services = [];

    private static string $moduleVersion = '1.0.0';

    public static function init(Module $module): void
    {
        self::$module = $module;
    }

    public static function reset(): void
    {
        self::$module = null;
        self::$services = [];
    }

    public static function setModuleVersion(string $version): void
    {
        self::$moduleVersion = $version;
    }

    /**
     * @template T of object @param class-string<T> $serviceClass @return T|null
     */
    public static function tryGet(string $serviceClass): ?object
    {
        if (self::$module !== null) {
            try {
                $service = self::$module->get($serviceClass);

                if ($service instanceof $serviceClass) {
                    return $service;
                }
            } catch (Exception $e) {
            }
        }

        return null;
    }

    /**
     * PSR-11 like get method for manual retrieval.
     */
    public static function get(string $id): ?object
    {
        return match ($id) {
            FieldTypeRegistry::class => self::getFieldTypeRegistry(),
            FieldTypeLoader::class => self::getFieldTypeLoader(),
            AcfGroupRepository::class => self::getGroupRepository(),
            AcfFieldRepository::class => self::getFieldRepository(),
            AcfFieldValueRepository::class => self::getValueRepository(),
            ValueProvider::class => self::getValueProvider(),
            ValueHandler::class => self::getValueHandler(),
            FileUploadService::class => self::getFileUploadService(),
            SlugGenerator::class => self::getSlugGenerator(),
            DomainEventDispatcher::class => self::getEventDispatcher(),
            AcfBrickDiscovery::class => self::getBrickDiscovery(),
            LocationProviderRegistry::class => self::getLocationProviderRegistry(),
            FieldGroupExporter::class => self::getFieldGroupExporter(),
            FieldGroupImporter::class => self::getFieldGroupImporter(),
            EntityContextDetector::class => self::getContextDetector(),
            FieldRenderer::class => self::getFieldRenderer(),
            AcfFrontService::class => self::getFrontService(),
            ShortcodeParser::class => self::getShortcodeParser(),
            CptTypeService::class => self::getTypeService(),
            CptPostService::class => self::getPostService(),
            CptTaxonomyService::class => self::getTaxonomyService(),
            CptFrontService::class => self::getFrontCptService(),
            CptSeoService::class => self::getSeoService(),
            CptUrlService::class => self::getUrlService(),
            CptSyncService::class => self::getSyncCptService(),
            ContextAdapter::class => self::getContextAdapter(),
            default => self::tryGet($id)
        };
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

    public static function getFieldTypeLoader(): FieldTypeLoader
    {
        if (!isset(self::$services[FieldTypeLoader::class])) {
            $service = self::tryGet(FieldTypeLoader::class);
            $modulePath = _PS_MODULE_DIR_ . 'wepresta_acf';
            self::$services[FieldTypeLoader::class] = $service ?? new FieldTypeLoader(
                self::getFieldTypeRegistry(),
                $modulePath,
                self::getBrickDiscovery()
            );
        }

        /** @var FieldTypeLoader */
        return self::$services[FieldTypeLoader::class];
    }

    /**
     * Load custom field types from theme and uploads directories.
     * Should be called before using the registry in hooks.
     */
    public static function loadCustomFieldTypes(): void
    {
        self::getFieldTypeLoader()->loadAllCustomTypes();
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

    public static function getEventDispatcher(): DomainEventDispatcher
    {
        if (!isset(self::$services[DomainEventDispatcher::class])) {
            $service = self::tryGet(DomainEventDispatcher::class);
            self::$services[DomainEventDispatcher::class] = $service ?? new DomainEventDispatcher();
        }

        /** @var DomainEventDispatcher */
        return self::$services[DomainEventDispatcher::class];
    }

    public static function getBrickDiscovery(): AcfBrickDiscovery
    {
        if (!isset(self::$services[AcfBrickDiscovery::class])) {
            $service = self::tryGet(AcfBrickDiscovery::class);
            self::$services[AcfBrickDiscovery::class] = $service ?? new AcfBrickDiscovery();
        }

        /** @var AcfBrickDiscovery */
        return self::$services[AcfBrickDiscovery::class];
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

    /**
     * @template T of object @param class-string<T>|string $serviceId @return T|null
     */
    public static function getCoreService(string $serviceId): ?object
    {
        if (self::$module === null) {
            return null;
        }

        try {
            return self::$module->get($serviceId);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getContextAdapter(): ContextAdapter
    {
        if (!isset(self::$services[ContextAdapter::class])) {
            $service = self::tryGet(ContextAdapter::class);
            self::$services[ContextAdapter::class] = $service ?? new ContextAdapter();
        }

        /** @var ContextAdapter */
        return self::$services[ContextAdapter::class];
    }

    // =========================================================================
    // FRONT-OFFICE SERVICES
    // =========================================================================

    public static function getContextDetector(): EntityContextDetector
    {
        if (!isset(self::$services[EntityContextDetector::class])) {
            $service = self::tryGet(EntityContextDetector::class);
            self::$services[EntityContextDetector::class] = $service ?? new EntityContextDetector();
        }

        /** @var EntityContextDetector */
        return self::$services[EntityContextDetector::class];
    }

    public static function getFieldRenderer(): FieldRenderer
    {
        if (!isset(self::$services[FieldRenderer::class])) {
            $service = self::tryGet(FieldRenderer::class);
            self::$services[FieldRenderer::class] = $service ?? new FieldRenderer();
        }

        /** @var FieldRenderer */
        return self::$services[FieldRenderer::class];
    }

    public static function getFrontService(): AcfFrontService
    {
        if (!isset(self::$services[AcfFrontService::class])) {
            $service = self::tryGet(AcfFrontService::class);
            self::$services[AcfFrontService::class] = $service ?? new AcfFrontService(
                self::getContextDetector(),
                self::getFieldRenderer(),
                self::getValueProvider(),
                self::getFieldRepository(),
                self::getGroupRepository()
            );
        }

        /** @var AcfFrontService */
        return self::$services[AcfFrontService::class];
    }

    public static function getShortcodeParser(): ShortcodeParser
    {
        if (!isset(self::$services[ShortcodeParser::class])) {
            $service = self::tryGet(ShortcodeParser::class);
            self::$services[ShortcodeParser::class] = $service ?? new ShortcodeParser(
                self::getFrontService(),
                self::getFieldRenderer()
            );
        }

        /** @var ShortcodeParser */
        return self::$services[ShortcodeParser::class];
    }

    public static function getSmartyWrapper(): AcfSmartyWrapper
    {
        if (!isset(self::$services[AcfSmartyWrapper::class])) {
            self::$services[AcfSmartyWrapper::class] = new AcfSmartyWrapper();
        }

        /** @var AcfSmartyWrapper */
        return self::$services[AcfSmartyWrapper::class];
    }

    public static function getTwigExtension(): AcfTwigExtension
    {
        if (!isset(self::$services[AcfTwigExtension::class])) {
            $service = self::tryGet(AcfTwigExtension::class);
            self::$services[AcfTwigExtension::class] = $service ?? new AcfTwigExtension(
                self::getFrontService(),
                self::getFieldRenderer()
            );
        }

        /** @var AcfTwigExtension */
        return self::$services[AcfTwigExtension::class];
    }

    // =========================================================================
    // CPT SERVICES
    // =========================================================================

    public static function getTypeRepository(): CptTypeRepository
    {
        if (!isset(self::$services[CptTypeRepository::class])) {
            $service = self::tryGet(CptTypeRepository::class);
            self::$services[CptTypeRepository::class] = $service ?? new CptTypeRepository();
        }

        /** @var CptTypeRepository */
        return self::$services[CptTypeRepository::class];
    }

    public static function getPostRepository(): CptPostRepository
    {
        if (!isset(self::$services[CptPostRepository::class])) {
            $service = self::tryGet(CptPostRepository::class);
            self::$services[CptPostRepository::class] = $service ?? new CptPostRepository();
        }

        /** @var CptPostRepository */
        return self::$services[CptPostRepository::class];
    }

    public static function getTaxonomyRepository(): CptTaxonomyRepository
    {
        if (!isset(self::$services[CptTaxonomyRepository::class])) {
            $service = self::tryGet(CptTaxonomyRepository::class);
            self::$services[CptTaxonomyRepository::class] = $service ?? new CptTaxonomyRepository();
        }

        /** @var CptTaxonomyRepository */
        return self::$services[CptTaxonomyRepository::class];
    }

    public static function getTermRepository(): CptTermRepository
    {
        if (!isset(self::$services[CptTermRepository::class])) {
            $service = self::tryGet(CptTermRepository::class);
            self::$services[CptTermRepository::class] = $service ?? new CptTermRepository();
        }

        /** @var CptTermRepository */
        return self::$services[CptTermRepository::class];
    }

    public static function getTypeService(): CptTypeService
    {
        if (!isset(self::$services[CptTypeService::class])) {
            $service = self::tryGet(CptTypeService::class);
            self::$services[CptTypeService::class] = $service ?? new CptTypeService(
                self::getTypeRepository(),
                self::getContextAdapter()
            );
        }

        /** @var CptTypeService */
        return self::$services[CptTypeService::class];
    }

    public static function getPostService(): CptPostService
    {
        if (!isset(self::$services[CptPostService::class])) {
            $service = self::tryGet(CptPostService::class);
            self::$services[CptPostService::class] = $service ?? new CptPostService(
                self::getPostRepository(),
                self::getContextAdapter()
            );
        }

        /** @var CptPostService */
        return self::$services[CptPostService::class];
    }

    public static function getTaxonomyService(): CptTaxonomyService
    {
        if (!isset(self::$services[CptTaxonomyService::class])) {
            $service = self::tryGet(CptTaxonomyService::class);
            self::$services[CptTaxonomyService::class] = $service ?? new CptTaxonomyService(
                self::getTaxonomyRepository(),
                self::getTermRepository(),
                self::getContextAdapter()
            );
        }

        /** @var CptTaxonomyService */
        return self::$services[CptTaxonomyService::class];
    }

    public static function getFrontCptService(): CptFrontService
    {
        if (!isset(self::$services[CptFrontService::class])) {
            $service = self::tryGet(CptFrontService::class);
            self::$services[CptFrontService::class] = $service ?? new CptFrontService(
                self::getPostRepository(),
                self::getTypeRepository(),
                self::getContextAdapter()
            );
        }

        /** @var CptFrontService */
        return self::$services[CptFrontService::class];
    }

    public static function getSeoService(): CptSeoService
    {
        if (!isset(self::$services[CptSeoService::class])) {
            $service = self::tryGet(CptSeoService::class);
            self::$services[CptSeoService::class] = $service ?? new CptSeoService();
        }

        /** @var CptSeoService */
        return self::$services[CptSeoService::class];
    }

    public static function getUrlService(): CptUrlService
    {
        if (!isset(self::$services[CptUrlService::class])) {
            $service = self::tryGet(CptUrlService::class);
            self::$services[CptUrlService::class] = $service ?? new CptUrlService();
        }

        /** @var CptUrlService */
        return self::$services[CptUrlService::class];
    }

    public static function getSyncCptService(): CptSyncService
    {
        if (!isset(self::$services[CptSyncService::class])) {
            $service = self::tryGet(CptSyncService::class);
            self::$services[CptSyncService::class] = $service ?? new CptSyncService(
                self::getTypeRepository(),
                self::getTaxonomyRepository(),
                self::getTermRepository()
            );
        }

        /** @var CptSyncService */
        return self::$services[CptSyncService::class];
    }
}
