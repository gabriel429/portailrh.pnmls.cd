<?php

namespace App\Listeners;

use App\Events\SignalementCreated;
use App\Models\User;
use App\Services\NotificationService;

class NotifyChefJuridiqueListener
{
    public function handle(SignalementCreated $event): void
    {
        $signalement = $event->signalement;

        $chefIds = User::whereHas('role', fn ($q) => $q->where('nom_role', 'Chef Section Juridique'))
            ->pluck('id')
            ->toArray();

        if (empty($chefIds)) {
            return;
        }

        $source = $signalement->is_anonymous ? 'anonyme' : 'identifié';

        NotificationService::envoyerMultiple(
            $chefIds,
            'demande',
            "Nouveau signalement ($source)",
            "Un signalement de sévérité {$signalement->severite} a été soumis ({$source}).",
            '/signalements/' . $signalement->id,
        );
    }
}
