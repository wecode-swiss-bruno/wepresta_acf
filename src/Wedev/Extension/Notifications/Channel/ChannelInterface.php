<?php

declare(strict_types=1);

namespace WeprestaAcf\Wedev\Extension\Notifications\Channel;

use WeprestaAcf\Wedev\Extension\Notifications\NotificationInterface;

/**
 * Interface pour les canaux de notification.
 */
interface ChannelInterface
{
    /**
     * Envoie une notification via ce canal.
     *
     * @return int Nombre de destinataires atteints
     */
    public function send(NotificationInterface $notification): int;

    /**
     * Vérifie si le canal est configuré et fonctionnel.
     */
    public function isConfigured(): bool;
}

