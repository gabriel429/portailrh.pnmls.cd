<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TacheResource;
use App\Models\ActivitePlan;
use App\Models\Tache;
use App\Models\TacheCommentaire;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TacheController extends ApiController
{
    /**
     * Display listing of taches.
     * - Regular agents see taches assigned to them.
     * - Directeurs also see taches they created.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;
        $isDirecteur = $user->hasRole('Directeur');

        $mesTaches = $agent
            ? Tache::with('createur')
                ->with('activitePlan')
                ->parAgent($agent->id)
                ->latest()
                ->get()
            : collect();

        $tachesCreees = collect();
        if ($isDirecteur && $agent) {
            $tachesCreees = Tache::with(['agent', 'activitePlan'])
                ->parCreateur($agent->id)
                ->latest()
                ->get();
        }

        $mesTachesResource = TacheResource::collection($mesTaches)->resolve();
        $tachesCreeesResource = TacheResource::collection($tachesCreees)->resolve();

        return $this->success([
            'mes_taches' => $mesTachesResource,
            'taches_creees' => $tachesCreeesResource,
        ], [
            'isDirecteur' => $isDirecteur,
        ], [
            'mesTaches' => $mesTachesResource,
            'tachesCreees' => $tachesCreeesResource,
            'isDirecteur' => $isDirecteur,
        ]);
    }

    /**
     * Return agents in the same department (for task creation).
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Directeur')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $user->agent;
        if (!$agent || !$agent->departement_id) {
            return response()->json([
                'message' => 'Vous devez etre affecte a un departement pour creer des taches.',
            ], 422);
        }

        $agentsDuDepartement = Agent::actifs()
            ->where('departement_id', $agent->departement_id)
            ->where('id', '!=', $agent->id)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom'])
            ->map(fn($a) => [
                'id' => $a->id,
                'nom' => $a->nom,
                'prenom' => $a->prenom,
                'id_agent' => $a->id_agent,
            ]);

        $activitesPta = ActivitePlan::query()
            ->where('annee', now()->year)
            ->when($agent->departement_id, fn($query) => $query->where('departement_id', $agent->departement_id))
            ->when(!$agent->departement_id && $agent->province_id, fn($query) => $query->where('province_id', $agent->province_id))
            ->orderBy('titre')
            ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
            ->map(function ($activite) {
                return [
                    'id' => $activite->id,
                    'titre' => $activite->titre,
                    'annee' => $activite->annee,
                    'trimestre' => $activite->trimestre,
                    'niveau_administratif' => $activite->niveau_administratif,
                ];
            });

        return $this->success([
            'agents' => $agentsDuDepartement,
            'activites_pta' => $activitesPta,
            'source_emetteurs' => [
                ['value' => 'directeur', 'label' => 'Directeur'],
                ['value' => 'assistant_departement', 'label' => 'Assistant du departement'],
                ['value' => 'sen', 'label' => 'SEN / Coordination'],
                ['value' => 'autre', 'label' => 'Autre'],
            ],
        ]);
    }

    /**
     * Store a newly created tache.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Directeur')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'agent_id'      => 'required|exists:agents,id',
            'titre'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'source_type'   => 'required|in:pta,hors_pta',
            'source_emetteur' => 'required|in:directeur,assistant_departement,sen,autre',
            'activite_plan_id' => 'nullable|required_if:source_type,pta|exists:activite_plans,id',
            'priorite'      => 'required|in:normale,haute,urgente',
            'date_echeance' => 'nullable|date',
            'date_tache'    => 'nullable|date',
        ]);

        $agent = $user->agent;
        $targetAgent = Agent::findOrFail($validated['agent_id']);

        if ($targetAgent->departement_id !== $agent->departement_id) {
            return response()->json([
                'message' => 'Vous ne pouvez assigner des taches qu\'aux agents de votre departement.',
            ], 403);
        }

        $validated['createur_id'] = $agent->id;
        $validated['statut'] = 'nouvelle';
        if ($validated['source_type'] !== 'pta') {
            $validated['activite_plan_id'] = null;
        }

        $tache = Tache::create($validated);

        $resource = TacheResource::make($tache->load(['createur', 'agent', 'activitePlan']));

        return $this->resource($resource, [], [
            'message' => 'Tache creee avec succes.',
        ], 201);
    }

    /**
     * Display the specified tache.
     */
    public function show(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $tache->load(['createur', 'agent', 'activitePlan', 'commentaires.agent']);

        return $this->resource(TacheResource::make($tache), [
            'isCreateur' => $tache->createur_id === $agent->id,
            'isAssigne' => $tache->agent_id === $agent->id,
        ], [
            'isCreateur' => $tache->createur_id === $agent->id,
            'isAssigne' => $tache->agent_id === $agent->id,
        ]);
    }

    /**
     * Update the status of a tache (assigned agent only).
     */
    public function updateStatut(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || $tache->agent_id !== $agent->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'statut'  => 'required|in:en_cours,terminee',
            'contenu' => 'required|string|max:1000',
        ]);

        $ancienStatut = $tache->statut;

        TacheCommentaire::create([
            'tache_id'       => $tache->id,
            'agent_id'       => $agent->id,
            'contenu'        => $validated['contenu'],
            'ancien_statut'  => $ancienStatut,
            'nouveau_statut' => $validated['statut'],
        ]);

        $tache->update(['statut' => $validated['statut']]);

        $resource = TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent']));

        return $this->resource($resource, [], [
            'message' => 'Statut mis a jour avec succes.',
        ]);
    }

    /**
     * Add a comment to a tache.
     */
    public function addCommentaire(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|max:1000',
        ]);

        TacheCommentaire::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'contenu'  => $validated['contenu'],
        ]);

        $resource = TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent']));

        return $this->resource($resource, [], [
            'message' => 'Commentaire ajoute.',
        ]);
    }
}
