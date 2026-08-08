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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code du département');
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->foreign('departement_id')->references('id')->on('departments');
            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['departement_id']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['role_id']);
        });

        Schema::dropIfExists('departments');
    }
};
