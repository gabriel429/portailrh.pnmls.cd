<?php

namespace App\Http\Controllers;

use App\Models\Tache;
use App\Models\TacheCommentaire;
use App\Models\Agent;
use Illuminate\Http\Request;

class TacheController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $agent = $user->agent;

        $mesTaches = $agent ? Tache::with('createur')
            ->parAgent($agent->id)
            ->latest()
            ->get() : collect();

        $tachesCreees = collect();
        $isDirecteur = $user->hasRole('Directeur');
        if ($isDirecteur && $agent) {
            $tachesCreees = Tache::with('agent')
                ->parCreateur($agent->id)
                ->latest()
                ->get();
        }

        return view('taches.index', compact('mesTaches', 'tachesCreees', 'isDirecteur'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->hasRole('Directeur')) {
            abort(403);
        }

        $agent = $user->agent;

        if (!$agent || !$agent->departement_id) {
            return redirect()->route('taches.index')
                ->with('error', 'Vous devez etre affecte a un departement pour creer des taches.');
        }

        $agentsDuDepartement = Agent::actifs()
            ->where('departement_id', $agent->departement_id)
            ->where('id', '!=', $agent->id)
            ->orderBy('nom')
            ->get();

        return view('taches.create', compact('agentsDuDepartement'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('Directeur')) {
            abort(403);
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
            abort(403, 'Vous ne pouvez assigner des taches qu\'aux agents de votre departement.');
        }

        $validated['createur_id'] = $agent->id;
        $validated['statut'] = 'nouvelle';

        Tache::create($validated);

        return redirect()->route('taches.index')
            ->with('success', 'Tache creee avec succes.');
    }

    public function show(Tache $tache)
    {
        $user = auth()->user();
        $agent = $user->agent;

        if ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id) {
            abort(403);
        }

        $tache->load(['createur', 'agent', 'commentaires.agent']);

        $isCreateur = ($tache->createur_id === $agent->id);
        $isAssigne = ($tache->agent_id === $agent->id);

        return view('taches.show', compact('tache', 'isCreateur', 'isAssigne'));
    }

    public function updateStatut(Request $request, Tache $tache)
    {
        $user = auth()->user();
        $agent = $user->agent;

        if ($tache->agent_id !== $agent->id) {
            abort(403);
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

        return redirect()->route('taches.show', $tache)
            ->with('success', 'Statut mis a jour avec succes.');
    }

    public function addCommentaire(Request $request, Tache $tache)
    {
        $user = auth()->user();
        $agent = $user->agent;

        if ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id) {
            abort(403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|max:1000',
        ]);

        TacheCommentaire::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'contenu'  => $validated['contenu'],
        ]);

        return redirect()->route('taches.show', $tache)
            ->with('success', 'Commentaire ajoute.');
    }
}
