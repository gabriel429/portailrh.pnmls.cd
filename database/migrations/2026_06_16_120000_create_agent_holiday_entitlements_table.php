<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_holiday_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->year('annee');
            $table->unsignedSmallInteger('jours_autorises')->default(30);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('agents')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agent_id', 'annee'], 'unique_agent_holiday_entitlement');
            $table->index(['annee', 'jours_autorises']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_holiday_entitlements');
    }
};
