<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('holidays') ||
            !Schema::hasColumn('holidays', 'date_fin') ||
            !Schema::hasColumn('holidays', 'date_retour_prevu')
        ) {
            return;
        }

        DB::table('holidays')
            ->whereNotNull('date_fin')
            ->orderBy('id')
            ->chunkById(200, function ($holidays) {
                foreach ($holidays as $holiday) {
                    DB::table('holidays')
                        ->where('id', $holiday->id)
                        ->update([
                            'date_retour_prevu' => $this->nextWorkingDayAfter($holiday->date_fin),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data recalculation only.
    }

    private function nextWorkingDayAfter(string $date): string
    {
        $next = Carbon::parse($date)->startOfDay()->addDay();

        while (!$next->isWeekday()) {
            $next->addDay();
        }

        return $next->toDateString();
    }
};
