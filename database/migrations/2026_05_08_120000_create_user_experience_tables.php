<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_guided_tours')) {
            Schema::create('user_guided_tours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('version')->default('2026-05-ux-v1');
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('skipped_at')->nullable();
                $table->timestamps();

                $table->unique('user_id');
            });
        }

        if (! Schema::hasTable('communique_reads')) {
            Schema::create('communique_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('communique_id')->constrained('communiques')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('read_at')->useCurrent();
                $table->timestamps();

                $table->unique(['communique_id', 'user_id']);
                $table->index('read_at');
            });
        }

        if (! Schema::hasTable('forum_post_reads')) {
            Schema::create('forum_post_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('forum_post_id')->constrained('forum_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('seen_at')->useCurrent();
                $table->timestamps();

                $table->unique(['forum_post_id', 'user_id']);
                $table->index('seen_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_reads');
        Schema::dropIfExists('communique_reads');
        Schema::dropIfExists('user_guided_tours');
    }
};
