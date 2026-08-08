<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holiday_plannings', function (Blueprint $table) {
            $table->string('niveau_administratif', 20)->default('national')->after('nom_structure');
            $table->string('statut', 20)->default('brouillon')->after('notes');
            $table->timestamp('submitted_at')->nullable()->after('created_by');
        });

        DB::table('holiday_plannings')->where('valide', true)->update(['statut' => 'valide']);

        Schema::create('holiday_modification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained('holidays')->cascadeOnDelete();
            $table->date('date_debut_proposee');
            $table->date('date_fin_proposee');
            $table->unsignedInteger('nombre_jours_proposes');
            $table->text('motif');
            $table->string('statut', 20)->default('en_attente');
            $table->foreignId('requested_by')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('agents')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['statut', 'created_at']);
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->timestamp('departure_alert_sent_at')->nullable()->after('date_retour_prevu');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn('departure_alert_sent_at');
        });

        Schema::dropIfExists('holiday_modification_requests');

        Schema::table('holiday_plannings', function (Blueprint $table) {
            $table->dropColumn(['niveau_administratif', 'statut', 'submitted_at']);
        });
    }
};