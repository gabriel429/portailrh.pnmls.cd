<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('forum_comment_reactions')) {
            return;
        }

        Schema::create('forum_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_comment_id')->constrained('forum_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 16);
            $table->timestamps();

            $table->unique(['forum_comment_id', 'user_id'], 'forum_comment_reactions_unique_user');
            $table->index(['forum_comment_id', 'reaction'], 'forum_comment_reactions_count_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comment_reactions');
    }
};
