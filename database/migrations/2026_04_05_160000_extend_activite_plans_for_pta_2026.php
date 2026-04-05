<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activite_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('activite_plans', 'categorie')) {
                $table->string('categorie', 120)->nullable()->after('titre');
            }

            if (!Schema::hasColumn('activite_plans', 'responsable_code')) {
                $table->string('responsable_code', 30)->nullable()->after('validation_niveau');
            }

            if (!Schema::hasColumn('activite_plans', 'cout_cdf')) {
                $table->decimal('cout_cdf', 18, 2)->nullable()->after('responsable_code');
            }

            if (!Schema::hasColumn('activite_plans', 'trimestre_1')) {
                $table->boolean('trimestre_1')->default(false)->after('trimestre');
            }

            if (!Schema::hasColumn('activite_plans', 'trimestre_2')) {
                $table->boolean('trimestre_2')->default(false)->after('trimestre_1');
            }

            if (!Schema::hasColumn('activite_plans', 'trimestre_3')) {
                $table->boolean('trimestre_3')->default(false)->after('trimestre_2');
            }

            if (!Schema::hasColumn('activite_plans', 'trimestre_4')) {
                $table->boolean('trimestre_4')->default(false)->after('trimestre_3');
            }
        });

        if (!Schema::hasTable('activite_plan_province')) {
            Schema::create('activite_plan_province', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activite_plan_id')->constrained('activite_plans')->cascadeOnDelete();
                $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['activite_plan_id', 'province_id'], 'app_activite_plan_province_unique');
                $table->index('province_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('activite_plan_province')) {
            Schema::dropIfExists('activite_plan_province');
        }

        Schema::table('activite_plans', function (Blueprint $table) {
            foreach (['trimestre_4', 'trimestre_3', 'trimestre_2', 'trimestre_1', 'cout_cdf', 'responsable_code', 'categorie'] as $column) {
                if (Schema::hasColumn('activite_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};