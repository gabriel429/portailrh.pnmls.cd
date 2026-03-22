<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * SyncService
 *
 * Handles bidirectional data synchronization between desktop clients
 * and the online server. Server-wins conflict resolution strategy.
 */
class SyncService
{
    /**
     * Map of table names to their Model classes.
     */
    protected array $syncableModels = [
        'agents' => \App\Models\Agent::class,
        'users' => \App\Models\User::class,
        'pointages' => \App\Models\Pointage::class,
        'documents' => \App\Models\Document::class,
        'requests' => \App\Models\Request::class,
        'signalements' => \App\Models\Signalement::class,
        'communiques' => \App\Models\Communique::class,
        'taches' => \App\Models\Tache::class,
        'tache_commentaires' => \App\Models\TacheCommentaire::class,
        'activite_plans' => \App\Models\ActivitePlan::class,
        'affectations' => \App\Models\Affectation::class,
        'messages' => \App\Models\Message::class,
        'notifications_portail' => \App\Models\NotificationPortail::class,
        'document_travails' => \App\Models\DocumentTravail::class,
        'historique_modifications' => \App\Models\HistoriqueModification::class,
    ];

    /**
     * Reference tables (server → client only).
     */
    protected array $referenceTables = [
        'roles', 'permissions', 'provinces', 'departments', 'sections',
        'cellules', 'localites', 'organes', 'fonctions', 'grades',
        'institutions', 'institution_categories', 'categorie_documents',
    ];

    /**
     * Get the syncable models map.
     */
    public function getSyncableModels(): array
    {
        return $this->syncableModels;
    }

    /**
     * Pull: Return records modified since last_sync_at.
     *
     * @param string|null $lastSyncAt ISO timestamp of client's last sync
     * @param array $tables Tables to pull (empty = all)
     * @return array
     */
    public function pull(?string $lastSyncAt = null, array $tables = []): array
    {
        $result = [];

        // Syncable tables (full data with soft deletes)
        foreach ($this->syncableModels as $table => $modelClass) {
            if (!empty($tables) && !in_array($table, $tables)) continue;
            if (!Schema::hasTable($table)) continue;

            $query = $modelClass::withTrashed();

            if ($lastSyncAt) {
                $query->where(function ($q) use ($lastSyncAt) {
                    $q->where('updated_at', '>', $lastSyncAt)
                      ->orWhere('created_at', '>', $lastSyncAt);
                });
            }

            $records = $query->get()->map(fn($r) => $r->toSyncArray());
            if ($records->isNotEmpty()) {
                $result[$table] = $records->toArray();
            }
        }

        // Reference tables (all data, no soft deletes)
        foreach ($this->referenceTables as $table) {
            if (!empty($tables) && !in_array($table, $tables)) continue;
            if (!Schema::hasTable($table)) continue;

            $query = DB::table($table);

            if ($lastSyncAt && Schema::hasColumn($table, 'updated_at')) {
                $query->where('updated_at', '>', $lastSyncAt);
            } elseif (!$lastSyncAt) {
                // First sync: send all
            }

            $records = $query->get();
            if ($records->isNotEmpty()) {
                $result[$table] = $records->toArray();
            }
        }

        return [
            'data' => $result,
            'server_time' => now()->toIso8601String(),
        ];
    }

    /**
     * Push: Accept a batch of records from the client.
     * Uses server-wins conflict resolution.
     *
     * @param array $payload { table_name: [{ uuid, data, action, updated_at }] }
     * @return array { accepted: [], conflicts: [], errors: [] }
     */
    public function push(array $payload): array
    {
        $accepted = [];
        $conflicts = [];
        $errors = [];

        foreach ($payload as $table => $records) {
            if (!isset($this->syncableModels[$table])) {
                $errors[] = ['table' => $table, 'error' => 'Table not syncable'];
                continue;
            }

            $modelClass = $this->syncableModels[$table];

            foreach ($records as $record) {
                try {
                    $result = $this->processRecord($modelClass, $table, $record);
                    if ($result['status'] === 'conflict') {
                        $conflicts[] = $result;
                    } else {
                        $accepted[] = $result;
                    }
                } catch (\Throwable $e) {
                    $errors[] = [
                        'table' => $table,
                        'uuid' => $record['uuid'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        // Log sync operation
        $this->logSync($accepted, $conflicts, $errors);

        return compact('accepted', 'conflicts', 'errors');
    }

    /**
     * Process a single record from the client push.
     */
    protected function processRecord(string $modelClass, string $table, array $record): array
    {
        $uuid = $record['uuid'] ?? null;
        $action = $record['action'] ?? 'update';
        $data = $record['data'] ?? $record;
        $clientUpdatedAt = $record['updated_at'] ?? null;

        // Remove internal fields from data
        unset($data['id'], $data['is_dirty'], $data['syncing']);

        if ($action === 'delete' && $uuid) {
            $existing = $modelClass::withTrashed()->where('uuid', $uuid)->first();
            if ($existing) {
                $existing->delete(); // soft delete
                return ['table' => $table, 'uuid' => $uuid, 'status' => 'deleted'];
            }
            return ['table' => $table, 'uuid' => $uuid, 'status' => 'not_found'];
        }

        if ($action === 'create') {
            // Check if UUID already exists (duplicate push)
            $existing = $modelClass::withTrashed()->where('uuid', $uuid)->first();
            if ($existing) {
                // Already exists — treat as update
                return $this->updateExisting($existing, $data, $table, $uuid, $clientUpdatedAt);
            }

            // Create new record
            $data['uuid'] = $uuid ?: (string) Str::uuid();
            $data['is_dirty'] = false;
            $data['synced_at'] = now();

            $model = new $modelClass();
            $model->setSyncing(true);
            $model->fill($data);
            $model->save();

            return ['table' => $table, 'uuid' => $model->uuid, 'status' => 'created', 'id' => $model->id];
        }

        // Update
        if (!$uuid) {
            return ['table' => $table, 'uuid' => null, 'status' => 'error', 'error' => 'No UUID provided'];
        }

        $existing = $modelClass::withTrashed()->where('uuid', $uuid)->first();
        if (!$existing) {
            // Record doesn't exist on server — create it
            $data['uuid'] = $uuid;
            $data['is_dirty'] = false;
            $data['synced_at'] = now();

            $model = new $modelClass();
            $model->setSyncing(true);
            $model->fill($data);
            $model->save();

            return ['table' => $table, 'uuid' => $model->uuid, 'status' => 'created', 'id' => $model->id];
        }

        return $this->updateExisting($existing, $data, $table, $uuid, $clientUpdatedAt);
    }

    /**
     * Update an existing record with conflict detection.
     */
    protected function updateExisting($existing, array $data, string $table, string $uuid, ?string $clientUpdatedAt): array
    {
        // Server-wins conflict detection:
        // If server record was updated after the client's version, it's a conflict.
        if ($clientUpdatedAt && $existing->updated_at && $existing->updated_at->gt($clientUpdatedAt)) {
            return [
                'table' => $table,
                'uuid' => $uuid,
                'status' => 'conflict',
                'server_data' => $existing->toSyncArray(),
                'client_updated_at' => $clientUpdatedAt,
                'server_updated_at' => $existing->updated_at->toIso8601String(),
            ];
        }

        // No conflict — apply the update
        $data['is_dirty'] = false;
        $data['synced_at'] = now();
        unset($data['uuid'], $data['created_at']); // Don't overwrite these

        $existing->setSyncing(true);
        $existing->fill($data);
        $existing->save();

        return ['table' => $table, 'uuid' => $uuid, 'status' => 'updated'];
    }

    /**
     * Log sync operations to sync_log table.
     */
    protected function logSync(array $accepted, array $conflicts, array $errors): void
    {
        $now = now();
        $logs = [];

        foreach ($accepted as $item) {
            $logs[] = [
                'table_name' => $item['table'],
                'record_uuid' => $item['uuid'] ?? '',
                'action' => $item['status'] === 'created' ? 'create' : ($item['status'] === 'deleted' ? 'delete' : 'update'),
                'direction' => 'up',
                'status' => 'synced',
                'data' => null,
                'error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($conflicts as $item) {
            $logs[] = [
                'table_name' => $item['table'],
                'record_uuid' => $item['uuid'] ?? '',
                'action' => 'update',
                'direction' => 'up',
                'status' => 'conflict',
                'data' => json_encode($item['server_data'] ?? null),
                'error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($errors as $item) {
            $logs[] = [
                'table_name' => $item['table'] ?? 'unknown',
                'record_uuid' => $item['uuid'] ?? '',
                'action' => 'update',
                'direction' => 'up',
                'status' => 'failed',
                'data' => null,
                'error' => $item['error'] ?? 'Unknown error',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($logs)) {
            DB::table('sync_log')->insert($logs);
        }
    }
}
