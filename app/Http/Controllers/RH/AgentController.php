<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Role;
use App\Models\Department;
use App\Models\Province;
use App\Models\Organe;
use App\Models\Grade;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    /**
     * Organes disponibles depuis la table organes.
     */
    private function getOrganeOptions(): array
    {
        return Organe::where('actif', true)
            ->orderBy('nom')
            ->pluck('nom')
            ->toArray();
    }

    /**
     * Grades disponibles depuis la table grades.
     */
    private function getGradeOptions()
    {
        return Grade::orderBy('ordre')->get();
    }

    /**
     * Fonctions PNMLS structurées par catégorie.
     */
    private function getFonctionOptions(): array
    {
        return [
            'Secrétariat Exécutif National' => [
                'Secrétaire Exécutif National (SEN)',
                'Secrétaire Exécutif National Adjoint (SENA)',
            ],
            'Secrétariat de Direction' => [
                'Assistant(e) de Direction (ADIR)',
                'Secrétaire de Direction',
                'Chef de cellule chargé du protocole, courriers et relations publiques',
                'Chef d’équipe chargé des relations publiques',
                'Chef d’équipe chargé de la réception',
            ],
            'Section Juridique' => [
                'Chef de Section Juridique',
            ],
            'Section Communication' => [
                'Chef de Section Communication',
                'Chef de Cellule Communication',
                'Chef d’équipe Communication',
            ],
            'Section Audit Interne' => [
                'Chef de Section chargé de l’Audit Interne',
                'Chef de Cellule chargé de l’Audit Interne',
            ],
            'Organisation des Départements' => [
                'Directeur - Chef de Département',
                'Chef de Section',
                'Chef de Cellule',
                'Assistant de Section',
                'Assistant ou Secrétaire de Département',
            ],
            'Département Administration et Finances' => [
                'Caissier / Caissière',
                'Chargé de fonction spécifique',
                'Mécanicien en chef',
                'Commis logistique',
                'Commis magasin',
                'Chauffeur',
                'Technicien de surface',
            ],
        ];
    }

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
        $organeOptions = $this->getOrganeOptions();
        $fonctionOptions = $this->getFonctionOptions();
        $grades = $this->getGradeOptions();

        return view('rh.agents.create', compact('roles', 'departments', 'provinces', 'organeOptions', 'fonctionOptions', 'grades'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_pnmls' => 'required|unique:agents',
            'matricule_etat' => 'required|unique:agents,matricule_etat',
            'provenance_matricule' => 'required|string|max:255',
            'nom' => 'required|string',
            'postnom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:agents',
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required|integer|min:1950|max:2100',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'organe' => 'required|string|max:255',
            'fonction' => 'required|string|max:255',
            'grade_id' => 'required|exists:grades,id',
            'niveau_etudes' => 'required|string|max:255',
            'annee_engagement_programme' => 'required|integer|min:1950|max:2100',
            'poste_actuel' => 'nullable|string',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
            'role_id' => 'nullable|exists:roles,id',
            'date_embauche' => 'nullable|date',
        ]);

        if (empty($validated['date_naissance']) && !empty($validated['annee_naissance'])) {
            $validated['date_naissance'] = $validated['annee_naissance'] . '-01-01';
        }

        if (empty($validated['date_embauche']) && !empty($validated['annee_engagement_programme'])) {
            $validated['date_embauche'] = $validated['annee_engagement_programme'] . '-01-01';
        }

        $validated['poste_actuel'] = $validated['fonction'];

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
        $organeOptions = $this->getOrganeOptions();
        $fonctionOptions = $this->getFonctionOptions();
        $grades = $this->getGradeOptions();

        return view('rh.agents.edit', compact('agent', 'roles', 'departments', 'provinces', 'organeOptions', 'fonctionOptions', 'grades'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_pnmls' => 'required|unique:agents,matricule_pnmls,' . $agent->id,
            'matricule_etat' => 'required|unique:agents,matricule_etat,' . $agent->id,
            'provenance_matricule' => 'required|string|max:255',
            'nom' => 'required|string',
            'postnom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:agents,email,' . $agent->id,
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required|integer|min:1950|max:2100',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'organe' => 'required|string|max:255',
            'fonction' => 'required|string|max:255',
            'grade_id' => 'required|exists:grades,id',
            'niveau_etudes' => 'required|string|max:255',
            'annee_engagement_programme' => 'required|integer|min:1950|max:2100',
            'poste_actuel' => 'nullable|string',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
            'role_id' => 'nullable|exists:roles,id',
            'date_embauche' => 'nullable|date',
            'statut' => 'required|in:actif,suspendu,ancien',
            'photo' => 'nullable|image|max:2048',
        ]);

        if (empty($validated['date_naissance']) && !empty($validated['annee_naissance'])) {
            $validated['date_naissance'] = $validated['annee_naissance'] . '-01-01';
        }

        if (empty($validated['date_embauche']) && !empty($validated['annee_engagement_programme'])) {
            $validated['date_embauche'] = $validated['annee_engagement_programme'] . '-01-01';
        }

        $validated['poste_actuel'] = $validated['fonction'];

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles'), $filename);
            $validated['photo'] = 'uploads/profiles/' . $filename;
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
                'postnom' => $agent->postnom,
                'email' => $agent->email,
                'email_prive' => $agent->email_prive,
                'email_professionnel' => $agent->email_professionnel,
                'telephone' => $agent->telephone,
                'poste_actuel' => $agent->poste_actuel,
                'organe' => $agent->organe,
                'fonction' => $agent->fonction,
                'grade_etat' => $agent->grade_etat,
                'sexe' => $agent->sexe,
                'matricule_etat' => $agent->matricule_etat,
                'provenance_matricule' => $agent->provenance_matricule,
                'niveau_etudes' => $agent->niveau_etudes,
                'annee_engagement_programme' => $agent->annee_engagement_programme,
                'annee_naissance' => $agent->annee_naissance,
                'role' => $agent->role,
                'departement' => $agent->departement,
                'province' => $agent->province,
                'date_naissance' => optional($agent->date_naissance)->format('d/m/Y'),
                'lieu_naissance' => $agent->lieu_naissance,
                'date_embauche' => optional($agent->date_embauche)->format('d/m/Y'),
                'adresse' => $agent->adresse,
                'statut' => $agent->statut,
            ]
        ]);
    }
}
