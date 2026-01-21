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

use RuntimeException;
use Throwable;

/**
 * Exception de base pour le module.
 *
 * Toutes les exceptions du module doivent étendre cette classe.
 */
class ModuleException extends RuntimeException
{
    /** Code d'erreur pour les erreurs de configuration. */
    protected const CODE_CONFIGURATION = 1000;

    /** Code d'erreur pour les erreurs de validation. */
    protected const CODE_VALIDATION = 1100;

    /** Code d'erreur pour les entités non trouvées. */
    protected const CODE_NOT_FOUND = 1200;

    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Contexte additionnel de l'exception.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Crée une exception avec contexte.
     */
    public static function withContext(string $message, array $context = []): self
    {
        return new self($message, 0, null, $context);
    }
}
