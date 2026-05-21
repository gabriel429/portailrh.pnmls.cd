<?php

namespace App\Listeners;

use App\Events\CommuniquePublished;
use App\Services\NotificationService;

class NotifyCommuniqueListener
{
    public function handle(CommuniquePublished $event): void
    {
        $communique = $event->communique;

        $userIds = \App\Models\User::pluck('id')->toArray();

        NotificationService::envoyerMultiple(
            $userIds,
            'communique',
            'Nouveau communiqué',
            $communique->titre,
            '/communiques/' . $communique->id,
            null,
            $event->notifyByMail
        );
    }
}
