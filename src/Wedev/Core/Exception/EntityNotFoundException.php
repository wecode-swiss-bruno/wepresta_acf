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

namespace WeprestaAcf\Wedev\Core\Exception;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Exception levée quand une entité n'est pas trouvée.
 */
class EntityNotFoundException extends ModuleException
{
    public static function withId(string $entityName, int|string $id): self
    {
        return new self(
            \sprintf('%s with ID "%s" not found.', $entityName, $id),
            404,
            null,
            ['entity' => $entityName, 'id' => $id]
        );
    }

    public static function withCriteria(string $entityName, array $criteria): self
    {
        return new self(
            \sprintf('%s not found with given criteria.', $entityName),
            404,
            null,
            ['entity' => $entityName, 'criteria' => $criteria]
        );
    }
}
