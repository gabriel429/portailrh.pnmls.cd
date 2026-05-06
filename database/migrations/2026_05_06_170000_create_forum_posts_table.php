<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('forum_posts')) {
            return;
        }

        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('titre')->nullable();
            $table->text('contenu');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['created_at', 'user_id']);
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
