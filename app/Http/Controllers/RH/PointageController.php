<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Pointage;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PointageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $pointages = Pointage::with(['agent'])
            ->paginate(15);

        return view('rh.pointages.index', compact('pointages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.pointages.create', compact('agents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date_pointage' => 'required|date|unique:pointages,date_pointage,NULL,id,agent_id,' . $request->agent_id,
            'heure_entree' => 'nullable|date_format:H:i',
            'heure_sortie' => 'nullable|date_format:H:i',
            'heures_travaillees' => 'nullable|numeric',
            'observations' => 'nullable|string',
        ]);

        Pointage::create($validated);

        return redirect()->route('pointages.index')
            ->with('success', 'Pointage créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pointage $pointage): View
    {
        return view('rh.pointages.show', compact('pointage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pointage $pointage): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.pointages.edit', compact('pointage', 'agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pointage $pointage): RedirectResponse
    {
        $validated = $request->validate([
            'heure_entree' => 'nullable|date_format:H:i',
            'heure_sortie' => 'nullable|date_format:H:i',
            'heures_travaillees' => 'nullable|numeric',
            'observations' => 'nullable|string',
        ]);

        $pointage->update($validated);

        return redirect()->route('pointages.show', $pointage)
            ->with('success', 'Pointage modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pointage $pointage): RedirectResponse
    {
        $pointage->delete();

        return redirect()->route('pointages.index')
            ->with('success', 'Pointage supprimé avec succès');
    }
}
