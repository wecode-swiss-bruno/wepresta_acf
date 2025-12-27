<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use WeprestaAcf\Application\Provider\EntityField\BrandEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CategoryEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CountryEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CmsCategoryEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CmsPageEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CurrencyEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CustomerAddressEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CustomerEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\CustomerGroupEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\LanguageEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\ProductEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\StateEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\SupplierEntityFieldProvider;
use WeprestaAcf\Application\Provider\EntityField\ZoneEntityFieldProvider;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;
use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldRegistry;

/**
 * Initializes the EntityFieldRegistry by registering all entity field providers.
 *
 * This service runs on kernel request to ensure all providers are registered
 * before they are needed by ACF location rules and hook handlers.
 */
final class EntityFieldRegistryInitializer implements EventSubscriberInterface
{
    private bool $initialized = false;

    /**
     * @param EntityFieldRegistry $registry
     * @param array<EntityFieldProviderInterface> $providers
     */
    public function __construct(
        private readonly EntityFieldRegistry $registry,
        private readonly array $providers
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->initialized || !$event->isMainRequest()) {
            return;
        }

        $this->initialize();
        $this->initialized = true;
    }

    /**
     * Registers all entity field providers with the registry.
     */
    public function initialize(): void
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof EntityFieldProviderInterface) {
                $this->registry->registerEntityType(
                    $provider->getEntityType(),
                    $provider
                );
            }
        }
    }
}

