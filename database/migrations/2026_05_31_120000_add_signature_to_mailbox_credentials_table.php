<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mailbox_credentials', 'signature')) {
            Schema::table('mailbox_credentials', function (Blueprint $table) {
                $table->text('signature')->nullable()->after('smtp_encryption');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mailbox_credentials', 'signature')) {
            Schema::table('mailbox_credentials', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
