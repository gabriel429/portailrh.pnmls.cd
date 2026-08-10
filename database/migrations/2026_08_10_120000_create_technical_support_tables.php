<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_user_id');
            $table->string('subject', 180);
            $table->text('description');
            $table->string('module', 80);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('nouveau');
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['requester_user_id', 'status']);
            $table->index(['status', 'priority']);
        });

        Schema::create('technical_support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('technical_support_tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 20)->default('message');
            $table->text('body')->nullable();
            $table->string('status_from', 20)->nullable();
            $table->string('status_to', 20)->nullable();
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_support_messages');
        Schema::dropIfExists('technical_support_tickets');
    }
};