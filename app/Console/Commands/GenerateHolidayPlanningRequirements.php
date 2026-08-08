<?php

namespace App\Console\Commands;

use App\Services\HolidayPlanningRequirementService;
use Illuminate\Console\Command;

class GenerateHolidayPlanningRequirements extends Command
{
    protected $signature = 'holidays:generate-planning-requirements {--year=} {--force}';

    protected $description = 'Génère les exigences T1 de soumission des plannings annuels de congés';

    public function handle(HolidayPlanningRequirementService $requirements): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        if (!$this->option('force') && ($year !== now()->year || now()->quarter !== 1)) {
            $this->info('Aucune génération : la commande automatique ne s’exécute que pendant le T1 courant.');

            return self::SUCCESS;
        }

        $result = $requirements->generateForYear($year);
        $this->info(sprintf(
            '%d tâche(s) et %d notification(s) créée(s) pour %d.',
            $result['tasks_created'],
            $result['notifications_created'],
            $year,
        ));

        return self::SUCCESS;
    }
}