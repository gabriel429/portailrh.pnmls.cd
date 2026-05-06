<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('forum_comments')) {
            return;
        }

        Schema::create('forum_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_post_id')->constrained('forum_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->text('contenu');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['forum_post_id', 'created_at']);
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comments');
    }
};
