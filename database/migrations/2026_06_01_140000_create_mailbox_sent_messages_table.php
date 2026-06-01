<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mailbox_sent_messages')) {
            return;
        }

        Schema::create('mailbox_sent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_email')->nullable();
            $table->string('sender_name')->nullable();
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject', 180);
            $table->longText('body');
            $table->longText('html_body')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('sent_at');
            $table->boolean('imap_synced')->default(false);
            $table->string('imap_folder')->nullable();
            $table->text('imap_sync_error')->nullable();
            $table->timestamp('imap_synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sent_at']);
            $table->index(['user_id', 'imap_synced']);
            $table->index('account_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_sent_messages');
    }
};
