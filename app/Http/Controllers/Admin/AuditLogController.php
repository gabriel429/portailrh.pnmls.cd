<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::orderByDesc('created_at');

        if ($request->table_name) {
            $query->where('table_name', $request->table_name);
        }
        if ($request->action) {
            $query->where('action', $request->action);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('user_name', 'like', "%{$request->search}%")
                  ->orWhere('table_name', 'like', "%{$request->search}%")
                  ->orWhere('ip_address', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function show(AuditLog $auditLog)
    {
        return response()->json($auditLog);
    }

    public function revert(Request $request, AuditLog $auditLog)
    {
        $tableName = $auditLog->table_name;

        if (!Schema::hasTable($tableName)) {
            return response()->json(['message' => "La table '{$tableName}' n'existe pas."], 422);
        }

        try {
            DB::beginTransaction();

            if ($auditLog->action === 'UPDATE' && $auditLog->donnees_avant) {
                $record = DB::table($tableName)->where('id', $auditLog->record_id)->first();
                if (!$record) {
                    return response()->json(['message' => 'Enregistrement introuvable.'], 404);
                }
                $restoreData = collect($auditLog->donnees_avant)
                    ->except(['id', 'created_at', 'updated_at', 'password', 'remember_token'])
                    ->toArray();
                DB::table($tableName)->where('id', $auditLog->record_id)->update($restoreData);

            } elseif ($auditLog->action === 'DELETE' && $auditLog->donnees_avant) {
                $existing = DB::table($tableName)->where('id', $auditLog->record_id)->first();
                if ($existing) {
                    return response()->json(['message' => 'Enregistrement existe deja (peut-etre deja restaure).'], 422);
                }
                $restoreData = collect($auditLog->donnees_avant)
                    ->except(['password', 'remember_token'])
                    ->toArray();
                DB::table($tableName)->insert($restoreData);

            } elseif ($auditLog->action === 'CREATE') {
                $record = DB::table($tableName)->where('id', $auditLog->record_id)->first();
                if (!$record) {
                    return response()->json(['message' => 'Enregistrement deja supprime.'], 422);
                }
                DB::table($tableName)->where('id', $auditLog->record_id)->delete();

            } else {
                return response()->json(['message' => 'Action non reversible.'], 422);
            }

            // Log the revert itself
            AuditLog::create([
                'user_id'       => $request->user()->id,
                'user_name'     => $request->user()->name,
                'table_name'    => $tableName,
                'record_id'     => $auditLog->record_id,
                'action'        => 'REVERT',
                'donnees_avant' => $auditLog->donnees_apres,
                'donnees_apres' => $auditLog->donnees_avant,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Modification restauree avec succes.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la restauration: ' . $e->getMessage()], 500);
        }
    }

    public function tables()
    {
        $tables = AuditLog::select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name');

        return response()->json($tables);
    }

    public function users()
    {
        $users = AuditLog::select('user_id', 'user_name')
            ->distinct()
            ->orderBy('user_name')
            ->get();

        return response()->json($users);
    }
}
