<?php

namespace App\Listeners;

use App\Events\PtaModified;
use App\Models\User;
use App\Services\NotificationService;

class NotifyCellulePlanificationListener
{
    public function handle(PtaModified $event): void
    {
        $activite = $event->activitePlan;

        $celluleIds = User::whereHas('role', fn ($q) => $q->whereIn('nom_role', [
            'Cellule Planification',
            'Chef Section Planification',
        ]))->pluck('id')->toArray();

        if (empty($celluleIds)) {
            return;
        }

        $action = match ($event->changeType) {
            'created' => 'créée',
            'updated' => 'modifiée',
            'status_changed' => 'statut mis à jour',
            default => 'modifiée',
        };

        NotificationService::envoyerMultiple(
            $celluleIds,
            'plan_travail',
            "Activité PTA $action",
            "L'activité \"{$activite->titre}\" a été $action.",
            '/plan-travail/' . $activite->id,
        );
    }
}
