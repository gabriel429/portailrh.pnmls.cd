<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Role;
use App\Models\Department;
use App\Models\Province;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
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
        $roles = Role::all();
        $departments = Department::all();
        $provinces = Province::all();

        return view('rh.agents.create', compact('roles', 'departments', 'provinces'));
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

        return redirect()->route('rh.agents.index')
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
        $roles = Role::all();
        $departments = Department::all();
        $provinces = Province::all();

        return view('rh.agents.edit', compact('agent', 'roles', 'departments', 'provinces'));
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
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profiles', 'public');
            $validated['photo'] = $path;
        }

        $agent->update($validated);

        return redirect()->route('rh.agents.show', $agent)
            ->with('success', 'Agent modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return redirect()->route('rh.agents.index')
            ->with('success', 'Agent supprimé avec succès');
    }

    /**
     * Display the RH dashboard with statistics
     */
    public function dashboard(): View
    {
        // Agent statistics
        $totalAgents = Agent::count();
        $activeAgents = Agent::where('statut', 'actif')->count();
        $suspendedAgents = Agent::where('statut', 'suspendu')->count();
        $formerAgents = Agent::where('statut', 'ancien')->count();

        // Request statistics
        $totalRequests = RequestModel::count();
        $pendingRequests = RequestModel::where('statut', 'en attente')->count();
        $approvedRequests = RequestModel::where('statut', 'approuvé')->count();
        $rejectedRequests = RequestModel::where('statut', 'rejeté')->count();

        // Attendance statistics
        $totalAttendance = Pointage::count();
        $recentAttendance = Pointage::with('agent')
            ->orderBy('date_pointage', 'desc')
            ->limit(10)
            ->get();

        // Recent requests
        $recentRequests = RequestModel::with('agent')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('rh.dashboard', compact(
            'totalAgents',
            'activeAgents',
            'suspendedAgents',
            'formerAgents',
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'totalAttendance',
            'recentAttendance',
            'recentRequests'
        ));
    }

    /**
     * Get agent details as JSON for modal
     */
    public function apiShow(Agent $agent)
    {
        $agent->load(['role', 'province', 'departement']);

        return response()->json([
            'agent' => [
                'id' => $agent->id,
                'matricule_pnmls' => $agent->matricule_pnmls,
                'prenom' => $agent->prenom,
                'nom' => $agent->nom,
                'email' => $agent->email,
                'telephone' => $agent->telephone,
                'poste_actuel' => $agent->poste_actuel,
                'role' => $agent->role,
                'departement' => $agent->departement,
                'province' => $agent->province,
                'date_naissance' => $agent->date_naissance?->format('d/m/Y'),
                'lieu_naissance' => $agent->lieu_naissance,
                'date_embauche' => $agent->date_embauche?->format('d/m/Y'),
                'adresse' => $agent->adresse,
                'statut' => $agent->statut,
            ]
        ]);
    }
}
