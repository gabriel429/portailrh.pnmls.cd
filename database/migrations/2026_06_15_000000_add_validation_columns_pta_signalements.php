<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PTA : colonnes de validation Section + Cellule
        Schema::table('activite_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('activite_plans', 'validated_by_section')) {
                $table->unsignedBigInteger('validated_by_section')->nullable()->after('statut');
            }
            if (!Schema::hasColumn('activite_plans', 'validated_at_section')) {
                $table->timestamp('validated_at_section')->nullable()->after('validated_by_section');
            }
            if (!Schema::hasColumn('activite_plans', 'validated_by_cellule')) {
                $table->unsignedBigInteger('validated_by_cellule')->nullable()->after('validated_at_section');
            }
            if (!Schema::hasColumn('activite_plans', 'validated_at_cellule')) {
                $table->timestamp('validated_at_cellule')->nullable()->after('validated_by_cellule');
            }
            if (!Schema::hasColumn('activite_plans', 'observations')) {
                $table->text('observations')->nullable()->after('validated_at_cellule');
            }
        });

        // Signalements : notes d'analyse
        Schema::table('signalements', function (Blueprint $table) {
            if (!Schema::hasColumn('signalements', 'analyse_notes')) {
                $table->text('analyse_notes')->nullable()->after('observations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activite_plans', function (Blueprint $table) {
            $table->dropColumn([
                'validated_by_section',
                'validated_at_section',
                'validated_by_cellule',
                'validated_at_cellule',
                'observations',
            ]);
        });

        Schema::table('signalements', function (Blueprint $table) {
            $table->dropColumn('analyse_notes');
        });
    }
};
