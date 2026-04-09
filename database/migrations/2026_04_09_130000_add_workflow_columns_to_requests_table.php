<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $columns = [
                'current_step'           => fn() => $table->string('current_step')->nullable()->after('remarques'),
                'validated_by_director'  => fn() => $table->unsignedBigInteger('validated_by_director')->nullable()->after('current_step'),
                'validated_at_director'  => fn() => $table->timestamp('validated_at_director')->nullable()->after('validated_by_director'),
                'validated_by_rh'        => fn() => $table->unsignedBigInteger('validated_by_rh')->nullable()->after('validated_at_director'),
                'validated_at_rh'        => fn() => $table->timestamp('validated_at_rh')->nullable()->after('validated_by_rh'),
                'validated_by_sep'       => fn() => $table->unsignedBigInteger('validated_by_sep')->nullable()->after('validated_at_rh'),
                'validated_at_sep'       => fn() => $table->timestamp('validated_at_sep')->nullable()->after('validated_by_sep'),
                'validated_by_sen'       => fn() => $table->unsignedBigInteger('validated_by_sen')->nullable()->after('validated_at_sep'),
                'validated_at_sen'       => fn() => $table->timestamp('validated_at_sen')->nullable()->after('validated_by_sen'),
            ];

            foreach ($columns as $col => $addFn) {
                if (!Schema::hasColumn('requests', $col)) {
                    $addFn();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'current_step',
                'validated_by_director',
                'validated_at_director',
                'validated_by_rh',
                'validated_at_rh',
                'validated_by_sep',
                'validated_at_sep',
                'validated_by_sen',
                'validated_at_sen',
            ]);
        });
    }
};
