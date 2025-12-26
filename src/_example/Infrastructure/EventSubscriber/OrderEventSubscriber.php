<?php

declare(strict_types=1);

namespace WeprestaAcf\Example\Infrastructure\EventSubscriber;

use WeprestaAcf\Example\Infrastructure\Adapter\ConfigurationAdapter;
use PrestaShopLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour les événements de commande
 */
final class OrderEventSubscriber implements EventSubscriberInterface
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
            'PrestaShop\PrestaShop\Core\Domain\Order\Command\AddOrderFromBackOfficeCommand' => 'onOrderCreate',
            'PrestaShop\PrestaShop\Core\Domain\Order\Command\UpdateOrderStatusCommand' => 'onOrderStatusChange',
        ];
    }

    public function onOrderCreate(object $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->log('Order created via CQRS');

        // Exemple: Envoyer une notification, mettre à jour un CRM, etc.
    }

    public function onOrderStatusChange(object $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->log('Order status changed via CQRS');

        // Exemple: Déclencher des actions selon le nouveau statut
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

