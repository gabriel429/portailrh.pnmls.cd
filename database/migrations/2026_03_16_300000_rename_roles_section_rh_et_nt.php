<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('nom_role', 'Chef Section RH')
            ->update([
                'nom_role' => 'Section ressources humaines',
                'description' => 'Section ressources humaines',
            ]);

        DB::table('roles')
            ->where('nom_role', 'Chef Section Nouvelle Technologie')
            ->update([
                'nom_role' => 'Section Nouvelle Technologie',
                'description' => 'Section Nouvelle Technologie',
            ]);

        // Also handle variant name
        DB::table('roles')
            ->where('nom_role', 'Chef de Section Nouvelle Technologie')
            ->update([
                'nom_role' => 'Section Nouvelle Technologie',
                'description' => 'Section Nouvelle Technologie',
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('nom_role', 'Section ressources humaines')
            ->update([
                'nom_role' => 'Chef Section RH',
                'description' => 'Chef de Section RH',
            ]);

        DB::table('roles')
            ->where('nom_role', 'Section Nouvelle Technologie')
            ->update([
                'nom_role' => 'Chef Section Nouvelle Technologie',
                'description' => 'Chef de Section Nouvelle Technologie',
            ]);
    }
};
