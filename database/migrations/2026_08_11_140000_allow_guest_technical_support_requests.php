<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('technical_support_tickets', 'requester_user_id')) {
            Schema::table('technical_support_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('requester_user_id')->nullable()->change();
            });
        }

        Schema::table('technical_support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('technical_support_tickets', 'requester_name')) {
                $table->string('requester_name', 160)->nullable()->after('requester_user_id');
            }

            if (!Schema::hasColumn('technical_support_tickets', 'requester_email')) {
                $table->string('requester_email', 190)->nullable()->after('requester_name');
            }

            if (!Schema::hasColumn('technical_support_tickets', 'requester_phone')) {
                $table->string('requester_phone', 60)->nullable()->after('requester_email');
            }

            if (!Schema::hasColumn('technical_support_tickets', 'requester_ip')) {
                $table->string('requester_ip', 45)->nullable()->after('requester_phone');
            }

            if (!Schema::hasColumn('technical_support_tickets', 'requester_user_agent')) {
                $table->text('requester_user_agent')->nullable()->after('requester_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('technical_support_tickets', function (Blueprint $table) {
            $columns = [
                'requester_user_agent',
                'requester_ip',
                'requester_phone',
                'requester_email',
                'requester_name',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('technical_support_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (
            Schema::hasColumn('technical_support_tickets', 'requester_user_id')
            && ! DB::table('technical_support_tickets')->whereNull('requester_user_id')->exists()
        ) {
            Schema::table('technical_support_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('requester_user_id')->nullable(false)->change();
            });
        }
    }
};
