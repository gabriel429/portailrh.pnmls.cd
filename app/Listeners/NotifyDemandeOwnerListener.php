<?php

namespace App\Listeners;

use App\Events\DemandeValidated;
use App\Services\NotificationService;

class NotifyDemandeOwnerListener
{
    public function handle(DemandeValidated $event): void
    {
        $demande = $event->demande;
        $agent = $demande->agent;
        $user = $agent?->user;

        if (!$user) {
            return;
        }

        $type = $event->action === 'approved' ? 'demande_approuvee' : 'demande_rejetee';
        $label = $event->action === 'approved' ? 'approuvée' : 'rejetée';

        NotificationService::envoyer(
            $user->id,
            $type,
            "Demande $label (étape {$event->step})",
            "Votre demande de {$demande->type} a été $label à l'étape {$event->step}.",
            '/requests/' . $demande->id,
        );
    }
}
