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
        if (!Schema::hasTable('sent_mail_histories')) {
            return;
        }

        Schema::table('sent_mail_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('sent_mail_histories', 'inbound_uid')) {
                $table->string('inbound_uid')->nullable()->after('sent_at');
                $table->index('inbound_uid');
            }

            if (!Schema::hasColumn('sent_mail_histories', 'response_from_email')) {
                $table->string('response_from_email')->nullable()->after('inbound_uid');
            }

            if (!Schema::hasColumn('sent_mail_histories', 'response_subject')) {
                $table->string('response_subject')->nullable()->after('response_from_email');
            }

            if (!Schema::hasColumn('sent_mail_histories', 'response_body_preview')) {
                $table->text('response_body_preview')->nullable()->after('response_subject');
            }

            if (!Schema::hasColumn('sent_mail_histories', 'response_received_at')) {
                $table->timestamp('response_received_at')->nullable()->after('response_body_preview');
                $table->index('response_received_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sent_mail_histories')) {
            return;
        }

        Schema::table('sent_mail_histories', function (Blueprint $table) {
            $dropIndexes = [
                'sent_mail_histories_inbound_uid_index',
                'sent_mail_histories_response_received_at_index',
            ];

            foreach ($dropIndexes as $index) {
                try {
                    $table->dropIndex($index);
                } catch (Throwable $e) {
                }
            }

            $columns = [
                'inbound_uid',
                'response_from_email',
                'response_subject',
                'response_body_preview',
                'response_received_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sent_mail_histories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
