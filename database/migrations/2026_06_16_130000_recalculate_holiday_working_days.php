<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        DB::table('holidays')
            ->whereNotNull('date_debut')
            ->whereNotNull('date_fin')
            ->orderBy('id')
            ->chunkById(200, function ($holidays) {
                foreach ($holidays as $holiday) {
                    DB::table('holidays')
                        ->where('id', $holiday->id)
                        ->update([
                            'nombre_jours' => $this->workingDays($holiday->date_debut, $holiday->date_fin),
                        ]);
                }
            });

        $this->recalculatePlanningUsage();
    }

    public function down(): void
    {
        // Data recalculation only.
    }

    private function recalculatePlanningUsage(): void
    {
        if (
            !Schema::hasTable('holiday_plannings') ||
            !Schema::hasColumn('holiday_plannings', 'jours_utilises') ||
            !Schema::hasColumn('holidays', 'holiday_planning_id')
        ) {
            return;
        }

        DB::table('holiday_plannings')->update(['jours_utilises' => 0]);

        $totals = DB::table('holidays')
            ->select('holiday_planning_id', DB::raw('COALESCE(SUM(nombre_jours), 0) as total_jours'))
            ->where('statut_demande', 'approuve')
            ->whereNotNull('holiday_planning_id')
            ->groupBy('holiday_planning_id')
            ->orderBy('holiday_planning_id')
            ->get();

        foreach ($totals as $total) {
            DB::table('holiday_plannings')
                ->where('id', $total->holiday_planning_id)
                ->update(['jours_utilises' => (int) $total->total_jours]);
        }
    }

    private function workingDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }

            $current->addDay();
        }

        return $days;
    }
};
