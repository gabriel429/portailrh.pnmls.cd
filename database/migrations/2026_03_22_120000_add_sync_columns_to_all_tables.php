<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that participate in bidirectional sync (local ↔ server).
     */
    protected array $syncTables = [
        'agents',
        'users',
        'pointages',
        'documents',
        'requests',
        'signalements',
        'communiques',
        'taches',
        'tache_commentaires',
        'activite_plans',
        'affectations',
        'messages',
        'notifications_portail',
        'document_travails',
        'historique_modifications',
    ];

    /**
     * Reference tables (server → local only, read-only sync).
     */
    protected array $refTables = [
        'roles',
        'permissions',
        'provinces',
        'departments',
        'sections',
        'cellules',
        'localites',
        'organes',
        'fonctions',
        'grades',
        'institutions',
        'institution_categories',
        'categorie_documents',
    ];

    public function up(): void
    {
        // Add sync columns to all syncable tables
        foreach ($this->syncTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (!Schema::hasColumn($table, 'uuid')) {
                        $t->uuid('uuid')->nullable()->unique()->after('id');
                    }
                    if (!Schema::hasColumn($table, 'synced_at')) {
                        $t->timestamp('synced_at')->nullable();
                    }
                    if (!Schema::hasColumn($table, 'is_dirty')) {
                        $t->boolean('is_dirty')->default(false);
                    }
                    if (!Schema::hasColumn($table, 'deleted_at')) {
                        $t->softDeletes();
                    }
                });
            }
        }

        // Add uuid + synced_at to reference tables (for tracking sync state)
        foreach ($this->refTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (!Schema::hasColumn($table, 'uuid')) {
                        $t->uuid('uuid')->nullable()->unique()->after('id');
                    }
                    if (!Schema::hasColumn($table, 'synced_at')) {
                        $t->timestamp('synced_at')->nullable();
                    }
                });
            }
        }

        // Create sync_log table for tracking sync operations
        Schema::create('sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100);
            $table->uuid('record_uuid');
            $table->enum('action', ['create', 'update', 'delete']);
            $table->enum('direction', ['up', 'down']);
            $table->enum('status', ['pending', 'synced', 'conflict', 'failed'])->default('pending');
            $table->json('data')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['table_name', 'record_uuid']);
            $table->index(['status', 'direction']);
        });

        // Create sync_meta table for tracking last sync timestamps
        Schema::create('sync_meta', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_meta');
        Schema::dropIfExists('sync_log');

        foreach ($this->syncTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $columns = [];
                    if (Schema::hasColumn($table, 'uuid')) $columns[] = 'uuid';
                    if (Schema::hasColumn($table, 'synced_at')) $columns[] = 'synced_at';
                    if (Schema::hasColumn($table, 'is_dirty')) $columns[] = 'is_dirty';
                    if (Schema::hasColumn($table, 'deleted_at')) $columns[] = 'deleted_at';
                    if ($columns) $t->dropColumn($columns);
                });
            }
        }

        foreach ($this->refTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $columns = [];
                    if (Schema::hasColumn($table, 'uuid')) $columns[] = 'uuid';
                    if (Schema::hasColumn($table, 'synced_at')) $columns[] = 'synced_at';
                    if ($columns) $t->dropColumn($columns);
                });
            }
        }
    }
};
