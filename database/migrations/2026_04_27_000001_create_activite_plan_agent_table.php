<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activite_plan_agent')) {
            Schema::create('activite_plan_agent', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activite_plan_id')->constrained('activite_plans')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['activite_plan_id', 'agent_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_plan_agent');
    }
};
