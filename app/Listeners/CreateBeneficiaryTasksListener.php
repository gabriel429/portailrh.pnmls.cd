<?php

namespace App\Listeners;

use App\Events\FormationPlanned;
use App\Models\Tache;
use App\Services\NotificationService;

class CreateBeneficiaryTasksListener
{
    public function handle(FormationPlanned $event): void
    {
        $formation = $event->formation;
        $formation->load('beneficiaires.agent');

        foreach ($formation->beneficiaires as $beneficiaire) {
            $agent = $beneficiaire->agent;
            if (!$agent) {
                continue;
            }

            $tache = Tache::create([
                'createur_id' => $formation->created_by,
                'agent_id' => $agent->id,
                'titre' => "Formation : {$formation->titre}",
                'description' => "Participer à la formation \"{$formation->titre}\" du {$formation->date_debut->format('d/m/Y')} au {$formation->date_fin->format('d/m/Y')}. Lieu : {$formation->lieu}.",
                'source_type' => 'formation',
                'priorite' => 'haute',
                'statut' => 'nouvelle',
                'date_echeance' => $formation->date_fin,
            ]);

            $user = $agent->user;
            if ($user) {
                NotificationService::envoyer(
                    $user->id,
                    'plan_travail',
                    'Formation planifiée',
                    "Vous êtes inscrit à la formation \"{$formation->titre}\".",
                    '/taches/' . $tache->id,
                );
            }
        }
    }
}
