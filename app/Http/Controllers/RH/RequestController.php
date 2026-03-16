<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
        {
            $user = auth()->user();
            $agent = $user->agent ?? null;
            $requestsQuery = RequestModel::with(['agent']);

            // Si l'utilisateur appartient à la section "nouvelle technologie"
            if ($agent && $agent->departement && strtolower($agent->departement->nom) === 'nouvelle technologie') {
                $requestsQuery->where('agent_id', $agent->id);
            }

            $requests = $requestsQuery->paginate(15);
            return view('rh.requests.index', compact('requests'));
        }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.requests.create', compact('agents'));
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
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
        ]);

        RequestModel::create($validated);

        return redirect()->route('requests.index')
            ->with('success', 'Demande créée avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestModel $request): View
    {
        return view('rh.requests.show', compact('request'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequestModel $request): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.requests.edit', compact('request', 'agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $httpRequest, RequestModel $request): RedirectResponse
    {
        $validated = $httpRequest->validate([
            'statut' => 'required|in:en_attente,approuvé,rejeté,annulé',
            'remarques' => 'nullable|string',
        ]);

        $request->update($validated);

        return redirect()->route('requests.show', $request)
            ->with('success', 'Demande modifiée avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequestModel $request): RedirectResponse
    {
        $request->delete();

        return redirect()->route('requests.index')
            ->with('success', 'Demande supprimée avec succès');
    }
}
