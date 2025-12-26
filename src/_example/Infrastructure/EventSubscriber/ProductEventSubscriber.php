<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Infrastructure\EventSubscriber;

use WeprestaAcf\Example\Infrastructure\Adapter\ConfigurationAdapter;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductId;
use PrestaShopLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour les événements produit
 *
 * Écoute les événements CQRS de PrestaShop 8+
 */
final class ProductEventSubscriber implements EventSubscriberInterface
{
    private const MODULE_NAME = 'wepresta_acf';

    public function __construct(
        private readonly ConfigurationAdapter $config
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // PrestaShop 8+ CQRS Events
            'PrestaShop\PrestaShop\Core\Domain\Product\Command\AddProductCommand' => 'onProductAdd',
            'PrestaShop\PrestaShop\Core\Domain\Product\Command\UpdateProductCommand' => 'onProductUpdate',
            'PrestaShop\PrestaShop\Core\Domain\Product\Command\DeleteProductCommand' => 'onProductDelete',
        ];
    }

    public function onProductAdd(object $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->log('Product added via CQRS');
    }

    public function onProductUpdate(object $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Invalider le cache si nécessaire
        $this->log('Product updated via CQRS');
    }

    public function onProductDelete(object $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->log('Product deleted via CQRS');
    }

    private function isEnabled(): bool
    {
        return $this->config->getBool('WEPRESTA_ACF_ACTIVE');
    }

    private function log(string $message, int $severity = 1): void
    {
        if ($this->config->getBool('WEPRESTA_ACF_DEBUG')) {
            PrestaShopLogger::addLog(
                '[' . self::MODULE_NAME . '] ' . $message,
                $severity
            );
        }
    }
}

