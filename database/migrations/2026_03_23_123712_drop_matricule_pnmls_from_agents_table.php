<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('agents', 'matricule_pnmls')) {
            return;
        }

        $uniqueIndex = collect(Schema::getIndexes('agents'))
            ->first(fn (array $index) => $index['unique'] && $index['columns'] === ['matricule_pnmls']);

        Schema::table('agents', function (Blueprint $table) use ($uniqueIndex) {
            if ($uniqueIndex) {
                $table->dropUnique($uniqueIndex['name']);
            }
            $table->dropColumn('matricule_pnmls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('matricule_pnmls')->unique()->after('id');
        });
    }
};
