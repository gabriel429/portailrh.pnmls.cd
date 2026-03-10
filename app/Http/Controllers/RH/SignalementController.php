<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SignalementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $signalements = Signalement::with(['agent'])
            ->paginate(15);

        return view('rh.signalements.index', compact('signalements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.signalements.create', compact('agents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'type' => 'required|string',
            'description' => 'required|string',
            'observations' => 'nullable|string',
            'severite' => 'required|in:basse,moyenne,haute',
        ]);

        Signalement::create($validated);

        return redirect()->route('signalements.index')
            ->with('success', 'Signalement créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Signalement $signalement): View
    {
        return view('rh.signalements.show', compact('signalement'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Signalement $signalement): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.signalements.edit', compact('signalement', 'agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Signalement $signalement): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
            'observations' => 'nullable|string',
            'severite' => 'required|in:basse,moyenne,haute',
            'statut' => 'required|in:ouvert,en_cours,résolu,fermé',
        ]);

        $signalement->update($validated);

        return redirect()->route('signalements.show', $signalement)
            ->with('success', 'Signalement modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Signalement $signalement): RedirectResponse
    {
        $signalement->delete();

        return redirect()->route('signalements.index')
            ->with('success', 'Signalement supprimé avec succès');
    }
}
