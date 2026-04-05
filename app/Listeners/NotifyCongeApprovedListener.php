<?php

namespace App\Listeners;

use App\Events\CongeApproved;
use App\Services\CongeConflictService;
use App\Services\NotificationService;

class NotifyCongeApprovedListener
{
    public function handle(CongeApproved $event): void
    {
        $holiday = $event->holiday;
        $user = $holiday->agent?->user;

        if ($user) {
            NotificationService::envoyer(
                $user->id,
                'demande_approuvee',
                'Congé approuvé',
                "Votre demande de congé du {$holiday->date_debut->format('d/m/Y')} au {$holiday->date_fin->format('d/m/Y')} a été approuvée.",
                '/mon-planning-conges',
            );
        }

        // Re-check PTA conflicts now that leave is confirmed
        $conflicts = CongeConflictService::checkPtaConflict(
            $holiday->agent_id,
            $holiday->date_debut,
            $holiday->date_fin,
        );

        if ($conflicts->isNotEmpty()) {
            // Notify PTA activity creators about confirmed conflict
            foreach ($conflicts as $activite) {
                $createurUser = $activite->createur?->user;
                if ($createurUser) {
                    $agentNom = $holiday->agent ? $holiday->agent->prenom . ' ' . $holiday->agent->nom : 'Un agent';
                    NotificationService::envoyer(
                        $createurUser->id,
                        'plan_travail',
                        'Conflit congé confirmé sur votre activité PTA',
                        "$agentNom sera en congé du {$holiday->date_debut->format('d/m/Y')} au {$holiday->date_fin->format('d/m/Y')}, chevauchant \"{$activite->titre}\".",
                        '/plan-travail/' . $activite->id,
                    );
                }
            }
        }
    }
}
