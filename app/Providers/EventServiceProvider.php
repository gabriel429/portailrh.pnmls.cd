<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\DemandeCreated::class => [
            \App\Listeners\RouteToRenforcementListener::class,
        ],
        \App\Events\DemandeValidated::class => [
            \App\Listeners\NotifyDemandeOwnerListener::class,
        ],
        \App\Events\SignalementCreated::class => [
            \App\Listeners\NotifyChefJuridiqueListener::class,
        ],
        \App\Events\PtaModified::class => [
            \App\Listeners\NotifyCellulePlanificationListener::class,
        ],
        \App\Events\TacheAssigned::class => [
            \App\Listeners\NotifyTacheAssignedListener::class,
        ],
        \App\Events\CongeRequested::class => [
            \App\Listeners\CheckPtaConflictListener::class,
        ],
        \App\Events\CongeApproved::class => [
            \App\Listeners\NotifyCongeApprovedListener::class,
        ],
        \App\Events\FormationPlanned::class => [
            \App\Listeners\CreateBeneficiaryTasksListener::class,
        ],
        \App\Events\CommuniquePublished::class => [
            \App\Listeners\NotifyCommuniqueListener::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
