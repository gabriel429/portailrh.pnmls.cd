<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentPresenceService
{
    public function onlineMap(array $agentIds): array
    {
        $agentIds = array_values(array_unique(array_filter(array_map('intval', $agentIds))));
        if ($agentIds === [] || !Schema::hasTable('sessions')) {
            return [];
        }

        try {
            $threshold = now()->subMinutes(30)->timestamp;
            $rows = DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->join('agents', 'users.agent_id', '=', 'agents.id')
                ->whereIn('agents.id', $agentIds)
                ->where('sessions.last_activity', '>=', $threshold)
                ->where('users.is_super_admin', false)
                ->select([
                    'agents.id',
                    'agents.nom',
                    'agents.prenom',
                    'agents.photo',
                    'agents.fonction',
                    'agents.poste_actuel',
                    DB::raw('MAX(sessions.last_activity) as last_activity'),
                ])
                ->groupBy('agents.id', 'agents.nom', 'agents.prenom', 'agents.photo', 'agents.fonction', 'agents.poste_actuel')
                ->orderByDesc('last_activity')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        return $rows->mapWithKeys(function ($row) {
            $lastActivity = Carbon::createFromTimestamp((int) $row->last_activity);
            $seconds = max(0, now()->diffInSeconds($lastActivity));

            return [(int) $row->id => [
                'id' => (int) $row->id,
                'nom' => $row->nom,
                'prenom' => $row->prenom,
                'photo' => $row->photo,
                'fonction' => $row->poste_actuel ?: $row->fonction,
                'last_activity' => $lastActivity->toIso8601String(),
                'connected_seconds' => $seconds,
                'label' => $seconds < 60 ? 'En ligne maintenant' : 'Actif il y a ' . max(1, (int) floor($seconds / 60)) . ' min',
            ]];
        })->all();
    }

    public function recent(array $onlineMap, int $seconds = 120): array
    {
        return collect($onlineMap)
            ->filter(fn($agent) => ($agent['connected_seconds'] ?? 999999) <= $seconds)
            ->values()
            ->all();
    }
}
