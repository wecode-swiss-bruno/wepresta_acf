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
use WeprestaAcf\Infrastructure\Api\Response\FieldResponse;

/**
 * Transform field entity array to FieldResponse DTO.
 */
final class FieldTransformer
{
    public function __construct(
        private readonly AcfFieldRepositoryInterface $fieldRepository
    ) {
    }

    /**
     * Transform field entity to response DTO.
     *
     * @param array<string, mixed> $field
     * @param bool $includeChildren Include children for repeater fields
     */
    public function transform(array $field, bool $includeChildren = false): FieldResponse
    {
        $fieldId = (int) $field['id_wepresta_acf_field'];

        // Get translations
        $translations = $this->fieldRepository->getFieldTranslations($fieldId);

        // Handle children for repeater fields
        $children = null;

        if ($includeChildren && $field['type'] === 'repeater') {
            $childFields = $this->fieldRepository->findByParent($fieldId);
            $children = array_map(fn ($child) => $this->transform($child, false), $childFields);
        }

        return new FieldResponse(
            id: $fieldId,
            uuid: $field['uuid'],
            groupId: (int) $field['id_wepresta_acf_group'],
            parentId: isset($field['id_parent']) && $field['id_parent'] ? (int) $field['id_parent'] : null,
            type: $field['type'],
            title: $field['title'],
            slug: $field['slug'],
            instructions: $field['instructions'] ?: null,
            config: $this->decodeJson($field['config'] ?? '[]'),
            validation: $this->decodeJson($field['validation'] ?? '[]'),
            conditions: $this->decodeJson($field['conditions'] ?? '[]'),
            wrapper: $this->decodeJson($field['wrapper'] ?? '[]'),
            foOptions: $this->decodeJson($field['fo_options'] ?? '[]'),
            position: (int) $field['position'],
            valueTranslatable: (bool) ($field['value_translatable'] ?? $field['translatable'] ?? false),
            active: (bool) $field['active'],
            translations: $translations,
            children: $children,
            dateAdd: $field['date_add'] ?? null,
            dateUpd: $field['date_upd'] ?? null
        );
    }

    /**
     * Transform multiple fields.
     *
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<int, FieldResponse>
     */
    public function transformMany(array $fields, bool $includeChildren = false): array
    {
        return array_map(fn ($field) => $this->transform($field, $includeChildren), $fields);
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
