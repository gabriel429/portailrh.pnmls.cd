<?php

namespace App\Http\Controllers\Api;

use App\Events\SignalementCreated;
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
        $query = Signalement::with(['agent', 'traitePar']);

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
     * Supports anonymous submissions (is_anonymous=true, no agent_id required).
     */
    public function store(Request $request): JsonResponse
    {
        $isAnonymous = $request->boolean('is_anonymous', false);

        $rules = [
            'type' => 'required|string',
            'description' => 'required|string',
            'observations' => 'nullable|string',
            'severite' => 'required|in:basse,moyenne,haute',
            'is_anonymous' => 'nullable|boolean',
        ];

        if (!$isAnonymous) {
            $rules['agent_id'] = 'required|exists:agents,id';
        }

        $validated = $request->validate($rules);

        if ($isAnonymous) {
            $validated['is_anonymous'] = true;
            $validated['agent_id'] = null;
        } else {
            $validated['is_anonymous'] = false;
            $agent = Agent::find($validated['agent_id']);
            if (!$this->scopeService()->canAccessAgent($request->user(), $agent)) {
                return response()->json([
                    'message' => 'Acces refuse pour cet agent.',
                ], 403);
            }
        }

        $signalement = Signalement::create($validated);

        SignalementCreated::dispatch($signalement);

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

    /**
     * Monthly signalement report.
     */
    public function reportMonthly(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasAdminAccess() && !$user->hasPermission('signalement.report.monthly')) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $mois = $request->input('mois', now()->month);
        $annee = $request->input('annee', now()->year);

        $signalements = Signalement::whereMonth('created_at', $mois)
            ->whereYear('created_at', $annee)
            ->get();

        return $this->success([
            'mois' => $mois,
            'annee' => $annee,
            'stats' => [
                'total' => $signalements->count(),
                'ouverts' => $signalements->where('statut', 'ouvert')->count(),
                'en_cours' => $signalements->where('statut', 'en_cours')->count(),
                'resolus' => $signalements->where('statut', 'résolu')->count(),
                'fermes' => $signalements->where('statut', 'fermé')->count(),
                'anonymes' => $signalements->where('is_anonymous', true)->count(),
                'par_severite' => [
                    'haute' => $signalements->where('severite', 'haute')->count(),
                    'moyenne' => $signalements->where('severite', 'moyenne')->count(),
                    'basse' => $signalements->where('severite', 'basse')->count(),
                ],
                'par_type' => $signalements->groupBy('type')->map->count(),
            ],
        ]);
    }

    /**
     * Annual signalement report.
     */
    public function reportAnnual(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasAdminAccess() && !$user->hasPermission('signalement.report.annual')) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $annee = $request->input('annee', now()->year);

        $signalements = Signalement::whereYear('created_at', $annee)->get();

        $parMois = [];
        for ($m = 1; $m <= 12; $m++) {
            $moisData = $signalements->filter(fn ($s) => $s->created_at->month === $m);
            $parMois[$m] = [
                'total' => $moisData->count(),
                'resolus' => $moisData->where('statut', 'résolu')->count(),
                'haute_severite' => $moisData->where('severite', 'haute')->count(),
            ];
        }

        $delaiMoyen = $signalements->where('statut', 'résolu')
            ->filter(fn ($s) => $s->traite_le)
            ->map(fn ($s) => $s->created_at->diffInDays($s->traite_le))
            ->avg();

        return $this->success([
            'annee' => $annee,
            'stats' => [
                'total' => $signalements->count(),
                'resolus' => $signalements->where('statut', 'résolu')->count(),
                'taux_resolution' => $signalements->count() > 0
                    ? round($signalements->where('statut', 'résolu')->count() / $signalements->count() * 100, 1)
                    : 0,
                'delai_moyen_resolution_jours' => $delaiMoyen ? round($delaiMoyen, 1) : null,
                'par_type' => $signalements->groupBy('type')->map->count(),
            ],
            'par_mois' => $parMois,
        ]);
    }
}
