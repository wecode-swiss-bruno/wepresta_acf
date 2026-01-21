<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interface for location providers.
 */
interface LocationProviderInterface
{
    public function getIdentifier(): string;

    public function getName(): string;

    /**
     * @return array<array{type: string, value: string, label: string, group: string, icon?: string, description?: string}>
     */
    public function getLocations(): array;

    /**
     * @param array<string, mixed> $rule @param array<string, mixed> $context
     */
    public function matchLocation(array $rule, array $context): bool;

    public function getPriority(): int;
}
