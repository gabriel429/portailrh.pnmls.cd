<?php

namespace App\Listeners;

use App\Events\TacheAssigned;
use App\Services\NotificationService;

class NotifyTacheAssignedListener
{
    public function handle(TacheAssigned $event): void
    {
        $tache = $event->tache;
        $agent = $tache->agent;
        $user = $agent?->user;

        if (!$user) {
            return;
        }

        $createur = $tache->createur;
        $nom = $createur ? $createur->prenom . ' ' . $createur->nom : 'Un responsable';

        NotificationService::envoyer(
            $user->id,
            'plan_travail',
            'Nouvelle tâche assignée',
            "$nom vous a assigné la tâche \"{$tache->titre}\".",
            '/taches/' . $tache->id,
            $createur?->user?->id,
        );
    }
}
