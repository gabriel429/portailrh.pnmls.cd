<?php

namespace App\Listeners;

use App\Events\CommuniquePublished;
use App\Services\NotificationService;

class NotifyCommuniqueListener
{
    public function handle(CommuniquePublished $event): void
    {
        $communique = $event->communique;

        NotificationService::notifierTousAvecEmail(
            'communique',
            'Nouveau communiqué',
            $communique->titre,
            '/communiques/' . $communique->id,
        );
    }
}
