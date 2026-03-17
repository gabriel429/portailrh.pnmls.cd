<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications_portail')) {
            return;
        }

        Schema::create('notifications_portail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type'); // demande, demande_modifiee, demande_approuvee, demande_rejetee, plan_travail, communique, message, document_travail
            $table->string('titre');
            $table->text('message');
            $table->string('icone')->default('fa-bell');
            $table->string('couleur')->default('#0077B5');
            $table->string('lien')->nullable();
            $table->unsignedBigInteger('emetteur_id')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('emetteur_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_portail');
    }
};
