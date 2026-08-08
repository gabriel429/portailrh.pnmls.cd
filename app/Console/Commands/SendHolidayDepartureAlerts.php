<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendHolidayDepartureAlerts extends Command
{
    protected $signature = 'holidays:send-departure-alerts';

    protected $description = 'Envoie les alertes dix jours ouvrables avant les départs en congé validés';

    public function handle(): int
    {
        $today = today();
        $sent = 0;

        Holiday::with(['agent.user', 'agent.departement', 'holidayPlanning.validatedBy.user'])
            ->where('statut_demande', 'approuve')
            ->whereNull('departure_alert_sent_at')
            ->whereDate('date_debut', '>', $today)
            ->whereDate('date_debut', '<=', $today->copy()->addDays(20))
            ->orderBy('date_debut')
            ->get()
            ->filter(fn (Holiday $holiday) => $this->workingDaysUntil($today, Carbon::parse($holiday->date_debut)) === 10)
            ->each(function (Holiday $holiday) use (&$sent) {
                $recipients = array_filter([
                    $holiday->agent?->user?->id,
                    $holiday->holidayPlanning?->validatedBy?->user?->id,
                    ...$this->rhRecipients($holiday),
                ]);

                $structure = $holiday->holidayPlanning?->nom_structure
                    ?? $holiday->agent?->departement?->nom
                    ?? $holiday->agent?->organe
                    ?? 'Structure non précisée';

                NotificationService::envoyerMultiple(
                    $recipients,
                    'holiday_departure_reminder',
                    'Départ en congé dans 10 jours ouvrables',
                    sprintf(
                        '%s : du %s au %s, %d jour(s), %s.',
                        $holiday->agent?->nom_complet ?? 'Agent',
                        Carbon::parse($holiday->date_debut)->format('d/m/Y'),
                        Carbon::parse($holiday->date_fin)->format('d/m/Y'),
                        $holiday->nombre_jours,
                        $structure,
                    ),
                    '/rh/holidays/planning',
                    null,
                    false,
                );

                $holiday->update(['departure_alert_sent_at' => now()]);
                $sent++;
            });

        $this->info("{$sent} alerte(s) de départ envoyée(s).");

        return self::SUCCESS;
    }

    private function workingDaysUntil(Carbon $from, Carbon $departure): int
    {
        $days = 0;
        $cursor = $from->copy()->addDay();

        while ($cursor->lte($departure)) {
            if ($cursor->isWeekday()) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function rhRecipients(Holiday $holiday): array
    {
        $query = User::query()->whereHas('role', fn ($role) => $role->whereIn('nom_role', [
            'RH National',
            'RH Provincial',
            'Section ressources humaines',
            'Chef Section RH',
        ]));

        if ($holiday->agent?->province_id) {
            $provinceId = $holiday->agent->province_id;
            $query->where(function ($scope) use ($provinceId) {
                $scope->whereHas('role', fn ($role) => $role->where('nom_role', 'RH National'))
                    ->orWhereHas('agent', fn ($agent) => $agent->where('province_id', $provinceId));
            });
        }

        return $query->pluck('id')->all();
    }
}