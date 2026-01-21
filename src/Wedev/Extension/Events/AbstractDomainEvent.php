<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Events;


if (!defined('_PS_VERSION_')) {
    exit;
}

use DateTimeImmutable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Base class for all domain events.
 *
 * Domain events represent something that happened in the domain.
 * They are immutable and carry all the information needed to
 * describe the event.
 *
 * @example
 * // Create a custom event
 * final class OrderShippedEvent extends AbstractDomainEvent
 * {
 *     public function __construct(
 *         public readonly int $orderId,
 *         public readonly string $trackingNumber,
 *         public readonly string $carrier
 *     ) {
 *         parent::__construct();
 *     }
 *
 *     public function getEventName(): string
 *     {
 *         return 'order.shipped';
 *     }
 * }
 *
 * // Dispatch the event
 * $dispatcher->dispatch(new OrderShippedEvent(123, 'TRACK123', 'DHL'));
 */
abstract class AbstractDomainEvent
{
    /** When the event occurred. */
    public readonly DateTimeImmutable $occurredAt;

    /** Unique identifier for this event instance. */
    public readonly string $eventId;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
        $this->eventId = $this->generateEventId();
    }

    /**
     * Returns the name of the event.
     *
     * Convention: 'entity.action' (e.g., 'order.created', 'product.updated')
     */
    abstract public function getEventName(): string;

    /**
     * Converts the event to an array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s.u'),
        ];

        // Add public properties from child classes
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (! isset($data[$name]) && $name !== 'occurredAt' && $name !== 'eventId') {
                $data[$name] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Generates a unique event ID.
     */
    private function generateEventId(): string
    {
        return \sprintf(
            '%s-%s',
            substr(str_replace('.', '_', $this->getEventName()), 0, 20),
            bin2hex(random_bytes(8))
        );
    }
}
