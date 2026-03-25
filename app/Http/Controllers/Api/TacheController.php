<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tache;
use App\Models\TacheCommentaire;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TacheController extends Controller
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
                ->parAgent($agent->id)
                ->latest()
                ->get()
            : collect();

        $tachesCreees = collect();
        if ($isDirecteur && $agent) {
            $tachesCreees = Tache::with('agent')
                ->parCreateur($agent->id)
                ->latest()
                ->get();
        }

        return response()->json([
            'mesTaches' => $mesTaches,
            'tachesCreees' => $tachesCreees,
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
            ->map(fn($a) => array_merge($a->toArray(), ['id_agent' => $a->id_agent]));

        return response()->json([
            'data' => $agentsDuDepartement,
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
            'priorite'      => 'required|in:normale,haute,urgente',
            'date_echeance' => 'nullable|date',
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

        $tache = Tache::create($validated);

        return response()->json([
            'message' => 'Tache creee avec succes.',
            'data' => $tache->load(['createur', 'agent']),
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

        $tache->load(['createur', 'agent', 'commentaires.agent']);

        return response()->json([
            'data' => $tache,
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

        return response()->json([
            'message' => 'Statut mis a jour avec succes.',
            'data' => $tache->fresh()->load(['createur', 'agent', 'commentaires.agent']),
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

        return response()->json([
            'message' => 'Commentaire ajoute.',
            'data' => $tache->fresh()->load(['createur', 'agent', 'commentaires.agent']),
        ]);
    }
}
