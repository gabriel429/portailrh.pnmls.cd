<?php

namespace App\Listeners;

use App\Events\CongeRequested;
use App\Services\CongeConflictService;
use App\Services\NotificationService;

class CheckPtaConflictListener
{
    public function handle(CongeRequested $event): void
    {
        $holiday = $event->holiday;

        $conflicts = CongeConflictService::checkPtaConflict(
            $holiday->agent_id,
            $holiday->date_debut,
            $holiday->date_fin,
        );

        foreach ($conflicts as $activite) {
            CongeConflictService::createConflitRecord(
                $holiday->id,
                $activite->id,
                'chevauchement_pta',
                "Congé chevauche l'activité PTA \"{$activite->titre}\"",
            );
        }

        if ($conflicts->isNotEmpty()) {
            NotificationService::notifierRH(
                'demande',
                'Conflit congé / PTA détecté',
                "Le congé de l'agent #{$holiday->agent_id} entre en conflit avec {$conflicts->count()} activité(s) PTA.",
                '/holidays/' . $holiday->id,
            );

            $agentUser = $holiday->agent?->user;
            if ($agentUser) {
                NotificationService::envoyer(
                    $agentUser->id,
                    'demande',
                    'Conflit détecté pour votre congé',
                    "Votre demande de congé chevauche {$conflicts->count()} activité(s) PTA. Le RH a été notifié.",
                    '/mon-planning-conges',
                );
            }
        }

        // Check department quota
        $agent = $holiday->agent;
        if ($agent && $agent->departement_id) {
            $quotaWarning = CongeConflictService::checkDepartmentQuota(
                $agent->departement_id,
                $holiday->date_debut,
                $holiday->date_fin,
            );

            if ($quotaWarning) {
                NotificationService::notifierRH(
                    'demande',
                    'Quota département dépassé',
                    $quotaWarning,
                    '/holidays/' . $holiday->id,
                );
            }
        }
    }
}
