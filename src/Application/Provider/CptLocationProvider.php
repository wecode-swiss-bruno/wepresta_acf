<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Provider;

use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Location Provider for Custom Post Types.
 *
 * Dynamically exposes all CPT types as location options in the ACF Builder.
 * This allows ACF groups to target specific CPT types via Location Rules.
 */
final class CptLocationProvider implements LocationProviderInterface
{
    public function __construct(
        private readonly CptTypeRepositoryInterface $typeRepository
    ) {
    }

    public function getIdentifier(): string
    {
        return 'cpt';
    }

    public function getName(): string
    {
        return 'Custom Post Types';
    }

    public function getPriority(): int
    {
        return 50; // After core providers
    }

    /**
     * Get all CPT types as location options.
     *
     * @return array<array{type: string, value: string, label: string, group: string, icon?: string, description?: string}>
     */
    public function getLocations(): array
    {
        $locations = [];

        try {
            $types = $this->typeRepository->findActive();

            foreach ($types as $type) {
                $locations[] = [
                    'type' => 'cpt_type',
                    'value' => 'cpt_post:' . $type->getSlug(),
                    'label' => $type->getName() ?: $type->getSlug(),
                    'group' => 'Custom Post Types',
                    'icon' => $type->getIcon() ?: 'article',
                    'description' => sprintf('Display on %s posts', $type->getName() ?: $type->getSlug()),
                    'enabled' => true,
                    'integration_type' => 'active',
                    'cpt_type_id' => $type->getId(),
                    'cpt_type_slug' => $type->getSlug(),
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if CPT tables don't exist yet
        }

        return $locations;
    }

    /**
     * Match a location rule against the current context.
     *
     * Supports rules like:
     * - {"==": [{"var": "entity_type"}, "cpt_post:blog_articles"]}
     * - Legacy: {"type": "cpt_type", "operator": "equals", "value": "blog_articles"}
     *
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $context
     */
    public function matchLocation(array $rule, array $context): bool
    {
        $ruleType = $rule['type'] ?? '';
        $ruleValue = $rule['value'] ?? '';
        $ruleOperator = $rule['operator'] ?? 'equals';

        // Only handle CPT type rules
        if ($ruleType !== 'cpt_type') {
            return false;
        }

        // Check if context is for a CPT post
        $contextEntityType = $context['entity_type'] ?? '';
        if ($contextEntityType !== 'cpt_post') {
            return false;
        }

        // Get the CPT type slug from context
        $contextCptSlug = $context['cpt_type_slug'] ?? '';

        return match ($ruleOperator) {
            'equals' => $ruleValue === $contextCptSlug,
            'not_equals' => $ruleValue !== $contextCptSlug,
            default => false,
        };
    }
}
