<?php

namespace App\Listeners;

use App\Events\DemandeCreated;
use App\Models\User;
use App\Services\NotificationService;

class RouteToRenforcementListener
{
    public function handle(DemandeCreated $event): void
    {
        $demande = $event->demande;

        if ($demande->type !== 'renforcement_capacites') {
            return;
        }

        $chefIds = User::whereHas('role', fn ($q) => $q->where('nom_role', 'Chef Section Renforcement'))
            ->pluck('id')
            ->toArray();

        if (empty($chefIds)) {
            return;
        }

        $agent = $demande->agent;
        $nom = $agent ? $agent->prenom . ' ' . $agent->nom : 'Un agent';

        NotificationService::envoyerMultiple(
            $chefIds,
            'demande',
            'Demande de renforcement des capacités',
            "$nom a soumis une demande de renforcement des capacités.",
            '/renforcement?source=demande&id=' . $demande->id,
            $demande->agent?->user?->id,
        );
    }
}
