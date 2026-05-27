<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_card_settings')) {
            return;
        }

        $existing = DB::table('agent_card_settings')
            ->where('key', 'card_title')
            ->first();

        if ($existing && $existing->value !== "CARTE D'IDENTITE PROFESSIONNELLE") {
            return;
        }

        DB::table('agent_card_settings')->updateOrInsert(
            ['key' => 'card_title'],
            [
                'value' => "CARTE DE L'AGENT PNMLS",
                'updated_at' => now(),
                'created_at' => $existing ? $existing->created_at : now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_card_settings')) {
            return;
        }

        DB::table('agent_card_settings')
            ->where('key', 'card_title')
            ->where('value', "CARTE DE L'AGENT PNMLS")
            ->update([
                'value' => "CARTE D'IDENTITE PROFESSIONNELLE",
                'updated_at' => now(),
            ]);
    }
};
