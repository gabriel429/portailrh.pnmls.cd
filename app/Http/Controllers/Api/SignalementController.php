<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SignalementResource;
use App\Models\Signalement;
use App\Models\Agent;
use App\Services\UserDataScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalementController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Display a paginated listing of signalements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Signalement::with(['agent']);

        $this->scopeService()->applySignalementScope($query, $request->user());

        if ($request->filled('severite')) {
            $query->where('severite', $request->input('severite'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $signalements = $query->latest()->paginate(15);

        return $this->paginated($signalements, SignalementResource::class);
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

        $agent = Agent::find($validated['agent_id']);
        if (!$this->scopeService()->canAccessAgent($request->user(), $agent)) {
            return response()->json([
                'message' => 'Acces refuse pour cet agent.',
            ], 403);
        }

        $signalement = Signalement::create($validated);

        $signalement->load('agent');
        $resource = SignalementResource::make($signalement);

        return $this->resource($resource, [], [
            'message' => 'Signalement cree avec succes.',
        ], 201);
    }

    /**
     * Display the specified signalement.
     */
    public function show(Signalement $signalement): JsonResponse
    {
        if (!$this->scopeService()->canAccessSignalement(request()->user(), $signalement)) {
            abort(403, 'Vous n\'avez pas acces a ce signalement.');
        }

        $signalement->load('agent');

        return $this->resource(SignalementResource::make($signalement));
    }

    /**
     * Update the specified signalement.
     */
    public function update(Request $request, Signalement $signalement): JsonResponse
    {
        if (!$this->scopeService()->canAccessSignalement($request->user(), $signalement)) {
            return response()->json([
                'message' => 'Vous n\'avez pas acces a ce signalement.',
            ], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
            'observations' => 'nullable|string',
            'severite' => 'required|in:basse,moyenne,haute',
            'statut' => 'required|in:ouvert,en_cours,résolu,fermé',
        ]);

        $signalement->update($validated);

        $resource = SignalementResource::make($signalement->fresh()->load('agent'));

        return $this->resource($resource, [], [
            'message' => 'Signalement modifie avec succes.',
        ]);
    }

    /**
     * Remove the specified signalement.
     */
    public function destroy(Signalement $signalement): JsonResponse
    {
        if (!$this->scopeService()->canAccessSignalement(request()->user(), $signalement)) {
            abort(403, 'Vous n\'avez pas acces a ce signalement.');
        }

        $signalement->delete();

        return $this->success(null, [], [
            'message' => 'Signalement supprime avec succes.',
        ]);
    }

    /**
     * Return active agents for the create form.
     */
    public function agents(): JsonResponse
    {
        $query = Agent::actifs()->orderBy('nom');
        $this->scopeService()->applyAgentScope($query, request()->user());

        $agents = $query->get(['id', 'nom', 'prenom'])
            ->map(fn($a) => [
                'id' => $a->id,
                'nom' => $a->nom,
                'prenom' => $a->prenom,
                'id_agent' => $a->id_agent,
            ]);

        return $this->success($agents);
    }
}
