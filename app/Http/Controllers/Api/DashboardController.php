<?php

namespace App\Http\Controllers\Api;

use App\Models\Communique;
use App\Models\Document;
use App\Models\Message;
use App\Models\Request as AgentRequest;
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

        // Récupère les activités récentes (demandes + documents)
        $recentRequests = AgentRequest::where('agent_id', $agent->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'type', 'statut', 'updated_at'])
            ->map(fn($r) => [
                'type'        => 'request',
                'description' => 'Demande de ' . $r->type . ' — ' . $r->statut,
                'link'        => '/requests/' . $r->id,
                'created_at'  => $r->updated_at,
            ]);

        $recentDocuments = Document::where('agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'name', 'type', 'created_at'])
            ->map(fn($d) => [
                'type'        => 'document',
                'description' => 'Document ajouté : ' . $d->name,
                'link'        => '/documents',
                'created_at'  => $d->created_at,
            ]);

        $activities = $recentRequests->concat($recentDocuments)
            ->sortByDesc('created_at')
            ->values()
            ->take(6);

        return $this->success($stats, [], [
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }
}
