<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    /**
     * Display a paginated listing of signalements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Signalement::with(['agent']);

        if ($request->filled('severite')) {
            $query->where('severite', $request->input('severite'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $signalements = $query->latest()->paginate(15);

        return response()->json([
            'data' => $signalements->items(),
            'meta' => [
                'current_page' => $signalements->currentPage(),
                'last_page' => $signalements->lastPage(),
                'per_page' => $signalements->perPage(),
                'total' => $signalements->total(),
                'from' => $signalements->firstItem(),
                'to' => $signalements->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created signalement.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'type' => 'required|string',
            'description' => 'required|string',
            'observations' => 'nullable|string',
            'severite' => 'required|in:basse,moyenne,haute',
        ]);

        $signalement = Signalement::create($validated);

        return response()->json([
            'message' => 'Signalement cree avec succes.',
            'data' => $signalement->load('agent'),
        ], 201);
    }

    /**
     * Display the specified signalement.
     */
    public function show(Signalement $signalement): JsonResponse
    {
        $signalement->load('agent');

        return response()->json([
            'data' => $signalement,
        ]);
    }

    /**
     * Update the specified signalement.
     */
    public function update(Request $request, Signalement $signalement): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
            'observations' => 'nullable|string',
            'severite' => 'required|in:basse,moyenne,haute',
            'statut' => 'required|in:ouvert,en_cours,résolu,fermé',
        ]);

        $signalement->update($validated);

        return response()->json([
            'message' => 'Signalement modifie avec succes.',
            'data' => $signalement->fresh()->load('agent'),
        ]);
    }

    /**
     * Remove the specified signalement.
     */
    public function destroy(Signalement $signalement): JsonResponse
    {
        $signalement->delete();

        return response()->json([
            'message' => 'Signalement supprime avec succes.',
        ]);
    }

    /**
     * Return active agents for the create form.
     */
    public function agents(): JsonResponse
    {
        $agents = Agent::actifs()->orderBy('nom')->get(['id', 'nom', 'prenom'])
            ->map(fn($a) => array_merge($a->toArray(), ['id_agent' => $a->id_agent]));

        return response()->json(['data' => $agents]);
    }
}
