<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            if (!Schema::hasColumn('provinces', 'ville_secretariat')) {
                $table->string('ville_secretariat')->nullable()->after('description')->comment('Ville où se trouve le secrétariat provincial/local');
            }
            if (!Schema::hasColumn('provinces', 'adresse')) {
                $table->string('adresse')->nullable()->after('ville_secretariat')->comment('Adresse du secrétariat');
            }
            if (!Schema::hasColumn('provinces', 'nom_gouverneur')) {
                $table->string('nom_gouverneur')->nullable()->after('adresse')->comment('Nom du gouverneur');
            }
            if (!Schema::hasColumn('provinces', 'nom_secretariat_executif')) {
                $table->string('nom_secretariat_executif')->nullable()->after('nom_gouverneur')->comment('Nom du secrétariat exécutif provincial/local');
            }
            if (!Schema::hasColumn('provinces', 'email_officiel')) {
                $table->string('email_officiel')->nullable()->after('nom_secretariat_executif')->comment('Mail officiel du secrétariat');
            }
            if (!Schema::hasColumn('provinces', 'telephone_officiel')) {
                $table->string('telephone_officiel')->nullable()->after('email_officiel')->comment('Numéro officiel du secrétariat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn([
                'ville_secretariat',
                'adresse',
                'nom_gouverneur',
                'nom_secretariat_executif',
                'email_officiel',
                'telephone_officiel',
            ]);
        });
    }
};
