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

namespace WeprestaAcf\Wedev\Core\Trait;


if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShopLogger;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Trait pour le logging.
 */
trait LoggerTrait
{
    protected ?LoggerInterface $logger = null;

    /**
     * Définit le logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Log un message de debug.
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->log($message, 1, $context);
    }

    /**
     * Log un message d'information.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log($message, 1, $context);
    }

    /**
     * Log un message d'avertissement.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log($message, 2, $context);
    }

    /**
     * Log un message d'erreur.
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log($message, 3, $context);
    }

    /**
     * Log une exception.
     */
    protected function logException(Throwable $e, array $context = []): void
    {
        $context['exception'] = \get_class($e);
        $context['file'] = $e->getFile();
        $context['line'] = $e->getLine();
        $context['trace'] = $e->getTraceAsString();

        $this->logError($e->getMessage(), $context);
    }

    /**
     * Log un message.
     *
     * @param int $severity 1=info, 2=warning, 3=error, 4=critical
     */
    protected function log(string $message, int $severity = 1, array $context = []): void
    {
        // Logger PSR si disponible
        if ($this->logger !== null) {
            match ($severity) {
                1 => $this->logger->info($message, $context),
                2 => $this->logger->warning($message, $context),
                3 => $this->logger->error($message, $context),
                4 => $this->logger->critical($message, $context),
                default => $this->logger->debug($message, $context),
            };

            return;
        }

        // Fallback: PrestaShopLogger
        $contextString = ! empty($context) ? ' | ' . json_encode($context) : '';
        $moduleName = property_exists($this, 'name') ? $this->name : 'module';

        PrestaShopLogger::addLog(
            "[{$moduleName}] {$message}{$contextString}",
            $severity,
            null,
            null,
            null,
            true
        );
    }
}
