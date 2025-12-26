<?php

declare(strict_types=1);

namespace WeprestaAcf\Domain\Event;

/**
 * Event Bus for publishing ACF events
 */
final class EventBus
{
    /** @var array<string, array<callable>> */
    private array $subscribers = [];
    private bool $dispatchToHooks = true;
    /** @var array<array{event: string, timestamp: float}> */
    private array $eventHistory = [];
    private int $maxHistorySize = 100;

    public function __construct(bool $dispatchToHooks = true)
    {
        $this->dispatchToHooks = $dispatchToHooks;
    }

    public function publish(AbstractAcfEvent $event): void
    {
        $eventName = $event->getEventName();
        $fullEventName = $event->getFullEventName();

        $this->recordEvent($fullEventName);
        $this->dispatchToSubscribers($event, $eventName);
        $this->dispatchToSubscribers($event, $fullEventName);

        if ($this->dispatchToHooks) {
            $this->dispatchToHook($event);
        }
    }

    public function subscribe(string $eventName, callable $callback): string
    {
        $subscriptionId = uniqid('sub_', true);
        $this->subscribers[$eventName][$subscriptionId] = $callback;
        return $subscriptionId;
    }

    public function unsubscribe(string $eventName, string $subscriptionId): bool
    {
        if (isset($this->subscribers[$eventName][$subscriptionId])) {
            unset($this->subscribers[$eventName][$subscriptionId]);
            return true;
        }
        return false;
    }

    public function subscribeAll(callable $callback): string
    {
        return $this->subscribe('*', $callback);
    }

    private function dispatchToSubscribers(AbstractAcfEvent $event, string $eventName): void
    {
        foreach ($this->subscribers[$eventName] ?? [] as $callback) {
            try {
                $callback($event);
            } catch (\Throwable $e) {
                $this->logError($event, $e);
            }
        }

        if ($eventName !== '*') {
            foreach ($this->subscribers['*'] ?? [] as $callback) {
                try {
                    $callback($event);
                } catch (\Throwable $e) {
                    $this->logError($event, $e);
                }
            }
        }
    }

    private function dispatchToHook(AbstractAcfEvent $event): void
    {
        $className = (new \ReflectionClass($event))->getShortName();
        $hookName = 'actionWeprestaAcf' . str_replace('Event', '', $className);

        \Hook::exec($hookName, [
            'event' => $event,
            'event_name' => $event->getFullEventName(),
            'event_data' => $event->toArray(),
        ]);
    }

    private function recordEvent(string $eventName): void
    {
        $this->eventHistory[] = ['event' => $eventName, 'timestamp' => microtime(true)];
        if (count($this->eventHistory) > $this->maxHistorySize) {
            $this->eventHistory = array_slice($this->eventHistory, -$this->maxHistorySize);
        }
    }

    private function logError(AbstractAcfEvent $event, \Throwable $e): void
    {
        if (class_exists('PrestaShopLogger')) {
            \PrestaShopLogger::addLog(
                sprintf('WePresta ACF EventBus error for event %s: %s', $event->getFullEventName(), $e->getMessage()),
                3, null, 'WeprestaAcf', 0, true
            );
        }
    }

    /** @return array<array{event: string, timestamp: float}> */
    public function getEventHistory(): array { return $this->eventHistory; }
    public function clearHistory(): void { $this->eventHistory = []; }
    public function getSubscriberCount(string $eventName): int { return count($this->subscribers[$eventName] ?? []); }
    public function isHookDispatchEnabled(): bool { return $this->dispatchToHooks; }
    public function setHookDispatch(bool $enabled): void { $this->dispatchToHooks = $enabled; }
}

