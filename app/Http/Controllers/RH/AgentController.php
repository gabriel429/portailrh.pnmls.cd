<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Role;
use App\Models\Department;
use App\Models\Province;
use App\Models\Organe;
use App\Models\Grade;
use App\Models\Fonction;
use App\Models\InstitutionCategorie;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

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
        return Schema::hasTable('grades') ? Grade::orderBy('ordre')->get() : collect();
    }

    /**
     * Fonctions groupées par niveau_administratif pour affichage.
     */
    private function getFonctionGroupedOptions(): array
    {
        $fonctions = Fonction::orderBy('niveau_administratif')->orderBy('nom')->get();
        $grouped = [];

        $niveauLabels = [
            'SEN'  => 'Secrétariat Exécutif National',
            'SEP'  => 'Secrétariat Exécutif Provincial',
            'SEL'  => 'Secrétariat Exécutif Local',
            'TOUS' => 'Tous niveaux',
        ];

        foreach ($fonctions as $fonction) {
            $niveau = $fonction->niveau_administratif;
            $label = isset($niveauLabels[$niveau]) ? $niveauLabels[$niveau] : $niveau;

            if (!isset($grouped[$label])) {
                $grouped[$label] = [];
            }
            $grouped[$label][] = $fonction;
        }

        return $grouped;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        // Get search query
        $search = $request->query('search');

        // Get all agents with relations
        $query = Agent::with(['role', 'province', 'departement', 'grade', 'institution']);

        // Apply search filter if provided
        if ($search) {
            $search = '%' . $search . '%';
            $query->where(function($q) use ($search) {
                // Direct fields on agents table
                $q->where('nom', 'like', $search)
                  ->orWhere('prenom', 'like', $search)
                  ->orWhere('postnom', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('matricule_etat', 'like', $search)
                  ->orWhere('telephone', 'like', $search)
                  ->orWhere('fonction', 'like', $search)
                  ->orWhere('grade_etat', 'like', $search)
                  ->orWhere('niveau_etudes', 'like', $search)
                  ->orWhere('annee_engagement_programme', 'like', $search)
                  ->orWhere('poste_actuel', 'like', $search)
                  // Related fields via relationships
                  ->orWhereHas('province', fn($q) => $q->where('nom', 'like', $search))
                  ->orWhereHas('departement', fn($q) => $q->where('nom', 'like', $search))
                  ->orWhereHas('grade', fn($q) => $q->where('nom', 'like', $search))
                  ->orWhereHas('institution', fn($q) => $q->where('nom', 'like', $search));
            });
        }

        $allAgents = $query->orderBy('organe')
            ->orderBy('nom')
            ->get();

        // Group agents by organe
        $agentsByOrgane = [];
        $organeLabels = [
            'Secrétariat Exécutif National' => [
                'code' => 'SEN',
                'icon' => 'fa-flag',
                'color' => '#0077B5',
                'bg' => '#e8f4fd',
            ],
            'Secrétariat Exécutif Provincial' => [
                'code' => 'SEP',
                'icon' => 'fa-map-marked-alt',
                'color' => '#0ea5e9',
                'bg' => '#e0f2fe',
            ],
            'Secrétariat Exécutif Local' => [
                'code' => 'SEL',
                'icon' => 'fa-map-pin',
                'color' => '#0d9488',
                'bg' => '#ccfbf1',
            ],
        ];

        foreach ($allAgents as $agent) {
            $organe = $agent->organe ?? 'Non assigné';
            if (!isset($agentsByOrgane[$organe])) {
                $agentsByOrgane[$organe] = [
                    'label' => $organe,
                    'agents' => [],
                    'icon' => $organeLabels[$organe]['icon'] ?? 'fa-sitemap',
                    'color' => $organeLabels[$organe]['color'] ?? '#6b7280',
                    'bg' => $organeLabels[$organe]['bg'] ?? '#f3f4f6',
                ];
            }
            $agentsByOrgane[$organe]['agents'][] = $agent;
        }

        // Reorder to put organes first, then non-assigned
        $ordered = [];
        foreach (['Secrétariat Exécutif National', 'Secrétariat Exécutif Provincial', 'Secrétariat Exécutif Local'] as $organe) {
            if (isset($agentsByOrgane[$organe])) {
                $ordered[$organe] = $agentsByOrgane[$organe];
            }
        }
        if (isset($agentsByOrgane['Non assigné'])) {
            $ordered['Non assigné'] = $agentsByOrgane['Non assigné'];
        }

        $provinces = Province::orderBy('nom')->get();
        $departments = Department::orderBy('nom')->get();

        return view('rh.agents.index', [
            'agentsByOrgane' => $ordered,
            'totalAgents' => $allAgents->count(),
            'searchTerm' => $search,
            'provinces' => $provinces,
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $departments = Department::all();
        $provinces = Province::all();
        $organeOptions = $this->getOrganeOptions();
        $fonctionOptions = $this->getFonctionGroupedOptions();
        $grades = $this->getGradeOptions();
        $institutionCategories = InstitutionCategorie::with('institutions')->orderBy('ordre')->get();
        $sections = Section::with('department')->orderBy('type')->orderBy('nom')->get();
        $fonctionsAll = Fonction::orderBy('niveau_administratif')->orderBy('type_poste')->orderBy('nom')->get();

        return view('rh.agents.create', compact('departments', 'provinces', 'organeOptions', 'fonctionOptions', 'grades', 'institutionCategories', 'sections', 'fonctionsAll'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_etat' => 'nullable|unique:agents,matricule_etat',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required|integer|min:1950|max:2100',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'situation_familiale' => 'nullable|string',
            'nombre_enfants' => 'nullable|integer|min:0',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'organe' => 'required|string|max:255',
            'fonction' => 'required|exists:fonctions,nom',
            'grade_id' => 'nullable|exists:grades,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'niveau_etudes' => ['required', 'string', Rule::in(Agent::NIVEAUX_ETUDES)],
            'domaine_etudes' => 'nullable|string|max:255',
            'annee_engagement_programme' => 'required|integer|min:1950|max:2100',
            'poste_actuel' => 'nullable|string',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
            'date_embauche' => 'nullable|date',
        ]);

        if (empty($validated['date_naissance']) && !empty($validated['annee_naissance'])) {
            $validated['date_naissance'] = $validated['annee_naissance'] . '-01-01';
        }

        if (empty($validated['date_embauche']) && !empty($validated['annee_engagement_programme'])) {
            $validated['date_embauche'] = $validated['annee_engagement_programme'] . '-01-01';
        }

        $validated['poste_actuel'] = $validated['fonction'];

        // Convert empty matricule values to null to avoid unique constraint issues
        if (empty($validated['matricule_etat'])) {
            $validated['matricule_etat'] = null;
        }

        // Remove domaine_etudes if column doesn't exist yet
        if (!Schema::hasColumn('agents', 'domaine_etudes')) {
            unset($validated['domaine_etudes']);
        }

        Agent::create($validated);

        return redirect()->route('rh.agents.index')
            ->with('success', 'Agent créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Agent $agent): View
    {
        $agent->load([
            'role', 'province', 'departement', 'documents', 'requests',
            'affectations.fonction', 'affectations.department', 'affectations.province',
            'messages.sender',
        ]);

        $tab = $request->query('tab', 'informations');

        return view('rh.agents.show', compact('agent', 'tab'));
    }

    /**
     * Store a message from DRH to an agent.
     */
    public function storeMessage(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'sujet' => 'required|string|max:255',
            'contenu' => 'required|string',
        ]);

        $agent->messages()->create([
            'sender_id' => auth()->user()->id,
            'sujet' => $validated['sujet'],
            'contenu' => $validated['contenu'],
        ]);

        return redirect()->route('rh.agents.show', ['agent' => $agent, 'tab' => 'messages'])
            ->with('success', 'Message envoyé avec succès');
    }

    /**
     * Print agent fiche.
     */
    public function printFiche(Agent $agent): View
    {
        $agent->load([
            'role', 'province', 'departement', 'grade', 'institution',
            'affectations.fonction', 'affectations.department', 'affectations.province',
        ]);

        return view('rh.agents.print', compact('agent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agent $agent): View
    {
        $departments = Department::all();
        $provinces = Province::all();
        $organeOptions = $this->getOrganeOptions();
        $fonctionOptions = $this->getFonctionGroupedOptions();
        $grades = $this->getGradeOptions();
        $institutionCategories = InstitutionCategorie::with('institutions')->orderBy('ordre')->get();
        $sections = Section::with('department')->orderBy('type')->orderBy('nom')->get();
        $fonctionsAll = Fonction::orderBy('niveau_administratif')->orderBy('type_poste')->orderBy('nom')->get();

        return view('rh.agents.edit', compact('agent', 'departments', 'provinces', 'organeOptions', 'fonctionOptions', 'grades', 'institutionCategories', 'sections', 'fonctionsAll'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'matricule_etat' => 'nullable|unique:agents,matricule_etat,' . $agent->id,
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'postnom' => 'required|string',
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required|integer|min:1950|max:2100',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'situation_familiale' => 'nullable|string',
            'nombre_enfants' => 'nullable|integer|min:0',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'organe' => 'required|string|max:255',
            'fonction' => 'required|exists:fonctions,nom',
            'grade_id' => 'nullable|exists:grades,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'niveau_etudes' => ['required', 'string', Rule::in(Agent::NIVEAUX_ETUDES)],
            'domaine_etudes' => 'nullable|string|max:255',
            'annee_engagement_programme' => 'required|integer|min:1950|max:2100',
            'poste_actuel' => 'nullable|string',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
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

        // Convert empty matricule values to null to avoid unique constraint issues
        if (empty($validated['matricule_etat'])) {
            $validated['matricule_etat'] = null;
        }

        // Normalize situation_familiale to match enum values
        if (!empty($validated['situation_familiale'])) {
            $map = [
                'célibataire' => 'célibataire', 'celibataire' => 'célibataire',
                'marié' => 'marié', 'marié(e)' => 'marié', 'marie' => 'marié',
                'divorcé' => 'divorcé', 'divorcé(e)' => 'divorcé', 'divorce' => 'divorcé',
                'veuf' => 'veuf', 'veuf/veuve' => 'veuf', 'veuve' => 'veuf',
            ];
            $key = mb_strtolower(trim($validated['situation_familiale']));
            $validated['situation_familiale'] = $map[$key] ?? $validated['situation_familiale'];
        }

        // Remove domaine_etudes if column doesn't exist yet
        if (!Schema::hasColumn('agents', 'domaine_etudes')) {
            unset($validated['domaine_etudes']);
        }

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
     * Export agents list as CSV with filters.
     */
    public function export(Request $request)
    {
        $organe = $request->input('organe');         // SEN, SEP, SEL or 'tous'
        $province_id = $request->input('province_id');
        $departement_id = $request->input('departement_id');

        $organeMap = [
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
        ];

        $query = Agent::with(['province', 'departement', 'grade', 'institution']);

        // Filter by organe
        if ($organe && $organe !== 'tous') {
            $organeNom = $organeMap[$organe] ?? null;
            if ($organeNom) {
                $query->where('organe', $organeNom);
            }
        }

        // Filter by province
        if ($province_id) {
            $query->where('province_id', $province_id);
        }

        // Filter by department (SEN only)
        if ($departement_id) {
            $query->where('departement_id', $departement_id);
        }

        $agents = $query->orderBy('organe')->orderBy('nom')->get();

        // Build filename
        $parts = ['agents'];
        if ($organe && $organe !== 'tous') {
            $parts[] = $organe;
        } else {
            $parts[] = 'tous';
        }
        if ($province_id) {
            $prov = Province::find($province_id);
            if ($prov) $parts[] = str_replace(' ', '_', $prov->nom_province ?? $prov->nom ?? 'province');
        }
        if ($departement_id) {
            $dept = Department::find($departement_id);
            if ($dept) $parts[] = str_replace(' ', '_', $dept->nom ?? 'dept');
        }
        $filename = implode('_', $parts) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($agents) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 for Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Header row
            fputcsv($file, [
                'ID Agent', 'Matricule État', 'Nom', 'Postnom', 'Prénom', 'Sexe',
                'Année naissance', 'Lieu naissance', 'État civil', 'Enfants',
                'Téléphone', 'Email privé', 'Email institutionnel',
                'Organe', 'Fonction', 'Poste actuel', 'Département', 'Province',
                'Grade État', 'Institution origine', 'Niveau études', 'Domaine études',
                'Année engagement', 'Ancienneté (ans)', 'Date embauche', 'Statut',
            ], ';');

            foreach ($agents as $agent) {
                $anciennete = $agent->annee_engagement_programme
                    ? (now()->year - $agent->annee_engagement_programme)
                    : '';

                fputcsv($file, [
                    $agent->id_agent,
                    $agent->matricule_etat ?? '',
                    $agent->nom,
                    $agent->postnom ?? '',
                    $agent->prenom,
                    $agent->sexe,
                    $agent->annee_naissance ?? '',
                    $agent->lieu_naissance ?? '',
                    $agent->situation_familiale ?? '',
                    $agent->nombre_enfants ?? '',
                    $agent->telephone ?? '',
                    $agent->email_prive ?? '',
                    $agent->email_professionnel ?? '',
                    $agent->organe ?? '',
                    $agent->fonction ?? '',
                    $agent->poste_actuel ?? '',
                    $agent->departement?->nom ?? '',
                    $agent->province?->nom_province ?? $agent->province?->nom ?? '',
                    $agent->grade?->libelle ?? '',
                    $agent->institution?->nom ?? '',
                    $agent->niveau_etudes ?? '',
                    $agent->domaine_etudes ?? '',
                    $agent->annee_engagement_programme ?? '',
                    $anciennete,
                    $agent->date_embauche?->format('d/m/Y') ?? '',
                    $agent->statut ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
                'id_agent' => $agent->id_agent,
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
                'domaine_etudes' => $agent->domaine_etudes,
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
