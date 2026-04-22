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
        if (Schema::hasTable('sent_mail_histories')) {
            return;
        }

        Schema::create('sent_mail_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');
            $table->string('subject', 180);
            $table->text('body');
            $table->string('attachment_name')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['sender_id', 'sent_at']);
            $table->index('recipient_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_mail_histories');
    }
};
