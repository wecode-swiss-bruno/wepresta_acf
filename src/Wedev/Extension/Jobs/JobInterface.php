<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Jobs;

use Throwable;

/**
 * Interface pour les jobs asynchrones.
 *
 * Un job représente une tâche à exécuter de manière asynchrone,
 * généralement via un CRON ou une file d'attente.
 */
interface JobInterface
{
    /**
     * Exécute le job.
     *
     * @throws Throwable En cas d'échec
     */
    public function handle(): void;

    /**
     * Retourne le nombre maximum de tentatives.
     */
    public function getMaxAttempts(): int;

    /**
     * Retourne le délai entre les tentatives (en secondes).
     */
    public function getRetryDelay(): int;

    /**
     * Retourne le timeout du job (en secondes).
     */
    public function getTimeout(): int;

    /**
     * Callback appelé en cas d'échec définitif.
     */
    public function onFailed(Throwable $exception): void;
}
