<?php

namespace App\Http\Controllers\Api;

use App\Models\Communique;
use App\Models\Message;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;

        $communiques = Communique::visibles()->count();

        if (!$agent) {
            $stats = [
                'documents' => 0,
                'requests_pending' => 0,
                'requests_approved' => 0,
                'absences' => 0,
                'messages_non_lus' => 0,
                'communiques' => $communiques,
            ];

            return $this->success($stats, [], [
                'stats' => $stats,
                'activities' => [],
            ]);
        }

        $stats = [
            'documents' => $agent->documents()->count(),
            'requests_pending' => $agent->requests()->where('statut', 'en_attente')->count(),
            'requests_approved' => $agent->requests()->where('statut', 'approuvé')->count(),
            'absences' => \App\Models\Pointage::where('agent_id', $agent->id)
                ->whereNull('heure_entree')
                ->whereNull('heure_sortie')
                ->count(),
            'messages_non_lus' => Message::where('agent_id', $agent->id)->nonLus()->count(),
            'communiques' => $communiques,
        ];

        return $this->success($stats, [], [
            'stats' => $stats,
            'activities' => [],
        ]);
    }
}
