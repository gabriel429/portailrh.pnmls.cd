<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailbox_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->text('imap_username');
            $table->text('imap_password');
            $table->string('imap_host')->default('camulus.o2switch.net');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('imap_encryption', 16)->default('ssl');
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 16)->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_credentials');
    }
};
