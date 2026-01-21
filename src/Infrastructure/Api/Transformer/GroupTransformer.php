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

namespace WeprestaAcf\Infrastructure\Api\Transformer;


if (!defined('_PS_VERSION_')) {
    exit;
}

use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Infrastructure\Api\Response\GroupResponse;

/**
 * Transform group entity array to GroupResponse DTO.
 */
final class GroupTransformer
{
    public function __construct(
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly FieldTransformer $fieldTransformer
    ) {
    }

    /**
     * Transform group entity to response DTO.
     *
     * @param array<string, mixed> $group
     * @param bool $includeFields Include all fields in response
     */
    public function transform(array $group, bool $includeFields = false): GroupResponse
    {
        $groupId = (int) $group['id_wepresta_acf_group'];

        // Get translations
        $translations = $this->groupRepository->getGroupTranslations($groupId);

        // Handle fields
        $fields = null;
        $fieldCount = null;

        if ($includeFields) {
            $fieldEntities = $this->fieldRepository->findByGroup($groupId);
            $fields = $this->fieldTransformer->transformMany($fieldEntities, true);
        } else {
            $fieldCount = $this->fieldRepository->countByGroup($groupId);
        }

        return new GroupResponse(
            id: $groupId,
            uuid: $group['uuid'],
            title: $group['title'],
            slug: $group['slug'],
            description: $group['description'] ?: null,
            locationRules: $this->decodeJson($group['location_rules'] ?? '[]'),
            placementTab: $group['placement_tab'],
            placementPosition: $group['placement_position'] ?: null,
            priority: (int) $group['priority'],
            boOptions: $this->decodeJson($group['bo_options'] ?? '{}'),
            foOptions: $this->decodeJson($group['fo_options'] ?? '{}'),
            active: (bool) $group['active'],
            translations: $translations,
            fields: $fields,
            fieldCount: $fieldCount,
            dateAdd: $group['date_add'] ?? null,
            dateUpd: $group['date_upd'] ?? null
        );
    }

    /**
     * Transform multiple groups.
     *
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<int, GroupResponse>
     */
    public function transformMany(array $groups, bool $includeFields = false): array
    {
        return array_map(fn ($group) => $this->transform($group, $includeFields), $groups);
    }

    /**
     * Decode JSON string to array.
     *
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        return json_decode($json, true) ?: [];
    }
}
