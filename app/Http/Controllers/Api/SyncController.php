<?php

namespace App\Http\Controllers\Api;

use App\Services\SyncService;
use Illuminate\Http\Request;

class SyncController extends ApiController
{
    public function __construct(
        protected SyncService $syncService
    ) {}

    /**
     * GET /api/sync/status
     * Check if server is reachable and ready for sync.
     */
    public function status()
    {
        $payload = [
            'status' => 'online',
            'server_time' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ];

        return $this->success($payload, [], $payload);
    }

    /**
     * POST /api/sync/pull
     * Client requests records modified since last_sync_at.
     *
     * Body: { last_sync_at: "2026-03-22T10:00:00Z", tables: ["agents", "pointages"] }
     */
    public function pull(Request $request)
    {
        $request->validate([
            'last_sync_at' => 'nullable|date',
            'tables' => 'nullable|array',
            'tables.*' => 'string',
        ]);

        $result = $this->syncService->pull(
            $request->input('last_sync_at'),
            $request->input('tables', [])
        );

        return $this->success($result, [], $result);
    }

    /**
     * POST /api/sync/push
     * Client sends its modified records to the server.
     *
     * Body: { agents: [{ uuid, data, action, updated_at }], pointages: [...] }
     */
    public function push(Request $request)
    {
        $request->validate([
            '*' => 'array',
        ]);

        $result = $this->syncService->push($request->all());

        return $this->success($result, [], $result);
    }

    /**
     * POST /api/sync/files/upload
     * Client uploads a file attachment (document, photo).
     *
     * Body: multipart/form-data with 'file', 'uuid', 'table', 'field'
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'uuid' => 'required|uuid',
            'table' => 'required|string',
            'field' => 'required|string',
        ]);

        $file = $request->file('file');
        $table = $request->input('table');
        $uuid = $request->input('uuid');
        $field = $request->input('field');

        // Store the file
        $directory = match ($table) {
            'agents' => 'photos',
            'documents' => 'documents',
            'requests' => 'lettres',
            'document_travails' => 'documents_travail',
            default => 'sync_uploads',
        };

        $path = $file->store($directory, 'public');

        // Update the record's file field
        $modelMap = [
            'agents' => \App\Models\Agent::class,
            'documents' => \App\Models\Document::class,
            'requests' => \App\Models\Request::class,
            'document_travails' => \App\Models\DocumentTravail::class,
        ];

        if (isset($modelMap[$table])) {
            $model = $modelMap[$table]::where('uuid', $uuid)->first();
            if ($model) {
                $model->setSyncing(true);
                $model->update([$field => $path, 'synced_at' => now()]);
            }
        }

        $payload = [
            'status' => 'uploaded',
            'uuid' => $uuid,
            'path' => $path,
        ];

        return $this->success($payload, [], $payload);
    }

    /**
     * GET /api/sync/dirty
     * Returns all locally dirty records (for desktop push).
     */
    public function dirty()
    {
        $data = [];

        foreach ($this->syncService->getSyncableModels() as $table => $modelClass) {
            if (!class_exists($modelClass)) continue;

            $dirty = $modelClass::where('is_dirty', true)->get();
            if ($dirty->isNotEmpty()) {
                $data[$table] = $dirty->map(fn($r) => [
                    'uuid' => $r->uuid,
                    'data' => $r->toSyncArray(),
                    'action' => $r->wasRecentlyCreated ? 'create' : 'update',
                    'updated_at' => $r->updated_at?->toIso8601String(),
                ])->toArray();
            }
        }

        return $this->success($data);
    }

    /**
     * POST /api/sync/mark-synced
     * Mark records as synced after a successful push.
     *
     * Body: { records: [{ table, uuid, status }] }
     */
    public function markSynced(Request $request)
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.table' => 'required|string',
            'records.*.uuid' => 'required|string',
        ]);

        $models = $this->syncService->getSyncableModels();
        $marked = 0;

        foreach ($request->input('records') as $record) {
            $table = $record['table'];
            $uuid = $record['uuid'];

            if (!isset($models[$table])) continue;
            $modelClass = $models[$table];
            if (!class_exists($modelClass)) continue;

            $model = $modelClass::where('uuid', $uuid)->first();
            if ($model) {
                $model->markSynced();
                $marked++;
            }
        }

        return $this->success(['marked' => $marked], [], ['marked' => $marked]);
    }

    /**
     * GET /api/sync/files/download/{uuid}
     * Client downloads a file by record UUID.
     */
    public function downloadFile(Request $request, string $uuid)
    {
        $table = $request->query('table', 'documents');
        $field = $request->query('field', 'fichier');

        $modelMap = [
            'agents' => \App\Models\Agent::class,
            'documents' => \App\Models\Document::class,
            'requests' => \App\Models\Request::class,
            'document_travails' => \App\Models\DocumentTravail::class,
        ];

        if (!isset($modelMap[$table])) {
            return response()->json(['error' => 'Invalid table'], 400);
        }

        $model = $modelMap[$table]::where('uuid', $uuid)->first();
        if (!$model || empty($model->$field)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $filePath = storage_path('app/public/' . $model->$field);
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File missing from storage'], 404);
        }

        return response()->download($filePath);
    }
}
