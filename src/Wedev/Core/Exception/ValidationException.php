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
 * Exception levée lors d'erreurs de validation.
 */
class ValidationException extends ModuleException
{
    /** @var array<string, string[]> */
    private array $errors = [];

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422, null, ['errors' => $errors]);
        $this->errors = $errors;
    }

    /**
     * Récupère les erreurs de validation.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Récupère les erreurs pour un champ.
     *
     * @return string[]
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Vérifie si un champ a des erreurs.
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]) && \count($this->errors[$field]) > 0;
    }

    /**
     * Crée une exception pour un seul champ.
     */
    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    /**
     * Crée une exception pour plusieurs champs.
     */
    public static function forFields(array $errors): self
    {
        return new self($errors);
    }
}
