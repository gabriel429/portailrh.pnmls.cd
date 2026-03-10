<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $agents = Agent::with(['role', 'province', 'departement'])
            ->paginate(15);

        return view('rh.agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('rh.agents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_pnmls' => 'required|unique:agents',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:agents',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'poste_actuel' => 'nullable|string',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
            'role_id' => 'nullable|exists:roles,id',
            'date_embauche' => 'required|date',
        ]);

        Agent::create($validated);

        return redirect()->route('agents.index')
            ->with('success', 'Agent créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Agent $agent): View
    {
        $agent->load(['role', 'province', 'departement', 'documents', 'requests']);

        return view('rh.agents.show', compact('agent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agent $agent): View
    {
        return view('rh.agents.edit', compact('agent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:agents,email,' . $agent->id,
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'poste_actuel' => 'nullable|string',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
            'role_id' => 'nullable|exists:roles,id',
            'statut' => 'required|in:actif,suspendu,ancien',
        ]);

        $agent->update($validated);

        return redirect()->route('agents.show', $agent)
            ->with('success', 'Agent modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return redirect()->route('agents.index')
            ->with('success', 'Agent supprimé avec succès');
    }
}
