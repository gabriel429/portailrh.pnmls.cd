<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->text('observation')->nullable()->after('motif');
            $table->unsignedBigInteger('interim_assure_par')->nullable()->after('observation');

            $table->foreign('interim_assure_par')->references('id')->on('agents')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['interim_assure_par']);
            $table->dropColumn(['observation', 'interim_assure_par']);
        });
    }
};
