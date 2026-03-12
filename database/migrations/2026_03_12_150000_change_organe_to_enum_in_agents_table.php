<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->enum('organe', [
                'Secrétariat Exécutif National',
                'Secrétariat Exécutif Provincial',
                'Secrétariat Exécutif Local',
            ])->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('organe')->nullable()->change();
        });
    }
};
