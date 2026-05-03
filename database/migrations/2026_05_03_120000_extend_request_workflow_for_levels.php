<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'current_step')) {
                $table->string('current_step', 30)->nullable()->after('remarques');
            }

            if (!Schema::hasColumn('requests', 'workflow_level')) {
                $table->string('workflow_level', 30)->nullable();
            }

            if (!Schema::hasColumn('requests', 'validated_by_director')) {
                $table->unsignedBigInteger('validated_by_director')->nullable();
                $table->timestamp('validated_at_director')->nullable();
            }

            if (!Schema::hasColumn('requests', 'validated_by_rh')) {
                $table->unsignedBigInteger('validated_by_rh')->nullable();
                $table->timestamp('validated_at_rh')->nullable();
            }

            if (!Schema::hasColumn('requests', 'validated_by_caf')) {
                $table->unsignedBigInteger('validated_by_caf')->nullable();
                $table->timestamp('validated_at_caf')->nullable();
            }

            if (!Schema::hasColumn('requests', 'validated_by_aaf')) {
                $table->unsignedBigInteger('validated_by_aaf')->nullable();
                $table->timestamp('validated_at_aaf')->nullable();
            }

            if (!Schema::hasColumn('requests', 'validated_by_sep')) {
                $table->unsignedBigInteger('validated_by_sep')->nullable();
                $table->timestamp('validated_at_sep')->nullable();
            }

            if (!Schema::hasColumn('requests', 'validated_by_sen')) {
                $table->unsignedBigInteger('validated_by_sen')->nullable();
                $table->timestamp('validated_at_sen')->nullable();
            }
        });

        if (!Schema::hasTable('request_validation_histories')) {
            Schema::create('request_validation_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
                $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('step', 30);
                $table->string('action', 30);
                $table->string('role_label', 100)->nullable();
                $table->text('commentaire')->nullable();
                $table->timestamp('acted_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('request_validation_histories')) {
            Schema::dropIfExists('request_validation_histories');
        }

        Schema::table('requests', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('requests', 'workflow_level') ? 'workflow_level' : null,
                Schema::hasColumn('requests', 'validated_by_caf') ? 'validated_by_caf' : null,
                Schema::hasColumn('requests', 'validated_at_caf') ? 'validated_at_caf' : null,
                Schema::hasColumn('requests', 'validated_by_aaf') ? 'validated_by_aaf' : null,
                Schema::hasColumn('requests', 'validated_at_aaf') ? 'validated_at_aaf' : null,
            ]));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
