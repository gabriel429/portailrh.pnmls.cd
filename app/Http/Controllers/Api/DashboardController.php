<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return response()->json([
                'documents' => 0,
                'requests_pending' => 0,
                'requests_approved' => 0,
                'absences' => 0,
            ]);
        }

        return response()->json([
            'documents' => $agent->documents()->count(),
            'requests_pending' => $agent->requests()->where('statut', 'en_attente')->count(),
            'requests_approved' => $agent->requests()->where('statut', 'approuve')->count(),
            'absences' => \App\Models\Pointage::where('agent_id', $agent->id)
                ->whereNull('heure_entree')
                ->whereNull('heure_sortie')
                ->count(),
        ]);
    }
}
