<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_card_settings')) {
            Schema::create('agent_card_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('agent_id_cards')) {
            Schema::create('agent_id_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('serial')->unique();
                $table->string('token', 80)->unique();
                $table->date('issued_at');
                $table->date('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index(['agent_id', 'revoked_at', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_id_cards');
        Schema::dropIfExists('agent_card_settings');
    }
};
