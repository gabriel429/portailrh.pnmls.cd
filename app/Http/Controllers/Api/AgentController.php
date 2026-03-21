<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\InstitutionCategorie;
use App\Models\Organe;
use App\Models\Province;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentController extends Controller
{
    /**
     * Organe labels used for grouping.
     */
    private array $organeLabels = [
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

    /**
     * Display a listing of agents, grouped by organe.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Agent::with(['role', 'province', 'departement', 'grade', 'institution']);

        // Search filter
        $search = $request->query('search');
        if ($search) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                  ->orWhere('prenom', 'like', $term)
                  ->orWhere('postnom', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('matricule_etat', 'like', $term)
                  ->orWhere('telephone', 'like', $term)
                  ->orWhere('fonction', 'like', $term)
                  ->orWhere('grade_etat', 'like', $term)
                  ->orWhere('niveau_etudes', 'like', $term)
                  ->orWhere('annee_engagement_programme', 'like', $term)
                  ->orWhere('poste_actuel', 'like', $term)
                  ->orWhereHas('province', fn($q) => $q->where('nom', 'like', $term)->orWhere('nom_province', 'like', $term))
                  ->orWhereHas('departement', fn($q) => $q->where('nom', 'like', $term))
                  ->orWhereHas('grade', fn($q) => $q->where('nom', 'like', $term))
                  ->orWhereHas('institution', fn($q) => $q->where('nom', 'like', $term));
            });
        }

        // Organe filter (SEN, SEP, SEL code or full name)
        $organe = $request->query('organe');
        if ($organe && $organe !== 'tous') {
            $organeMap = [
                'SEN' => 'Secrétariat Exécutif National',
                'SEP' => 'Secrétariat Exécutif Provincial',
                'SEL' => 'Secrétariat Exécutif Local',
            ];
            $organeNom = $organeMap[$organe] ?? $organe;
            $query->where('organe', $organeNom);
        }

        // Province filter
        if ($request->query('province_id')) {
            $query->where('province_id', $request->query('province_id'));
        }

        // Department filter
        if ($request->query('department_id')) {
            $query->where('departement_id', $request->query('department_id'));
        }

        // Status filter
        if ($request->query('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        // Sort by hierarchical position (grade_etat maps to Fonction Publique ranks)
        $allAgents = $query->orderBy('organe')
            ->orderByRaw("CASE
                WHEN LOWER(grade_etat) LIKE '%secrétaire général%' OR LOWER(grade_etat) LIKE '%secretaire general%' THEN 1
                WHEN LOWER(grade_etat) LIKE '%directeur%' THEN 2
                WHEN LOWER(grade_etat) LIKE '%chef de division%' THEN 3
                WHEN LOWER(grade_etat) LIKE '%chef de bureau%' THEN 4
                WHEN LOWER(grade_etat) LIKE '%attaché%1ère%' OR LOWER(grade_etat) LIKE '%attache%1ere%' THEN 5
                WHEN LOWER(grade_etat) LIKE '%attaché%2ème%' OR LOWER(grade_etat) LIKE '%attache%2eme%' THEN 6
                WHEN LOWER(grade_etat) LIKE '%agent%1ère%' OR LOWER(grade_etat) LIKE '%agent%1ere%' THEN 7
                WHEN LOWER(grade_etat) LIKE '%agent%2ème%' OR LOWER(grade_etat) LIKE '%agent%2eme%' THEN 8
                WHEN LOWER(grade_etat) LIKE '%auxiliaire%1ère%' OR LOWER(grade_etat) LIKE '%auxiliaire%1ere%' THEN 9
                WHEN LOWER(grade_etat) LIKE '%auxiliaire%2ème%' OR LOWER(grade_etat) LIKE '%auxiliaire%2eme%' THEN 10
                WHEN LOWER(grade_etat) LIKE '%huissier%' THEN 11
                ELSE 12
            END")
            ->orderBy('nom')
            ->get();

        // Group by organe
        $agentsByOrgane = [];
        foreach ($allAgents as $agent) {
            $organeKey = $agent->organe ?? 'Non assigne';
            if (!isset($agentsByOrgane[$organeKey])) {
                $agentsByOrgane[$organeKey] = [
                    'label' => $organeKey,
                    'agents' => [],
                    'icon' => $this->organeLabels[$organeKey]['icon'] ?? 'fa-sitemap',
                    'color' => $this->organeLabels[$organeKey]['color'] ?? '#6b7280',
                    'bg' => $this->organeLabels[$organeKey]['bg'] ?? '#f3f4f6',
                    'code' => $this->organeLabels[$organeKey]['code'] ?? '',
                ];
            }
            $agentsByOrgane[$organeKey]['agents'][] = $this->formatAgentForList($agent);
        }

        // Reorder: SEN, SEP, SEL first, then others
        $ordered = [];
        foreach (['Secrétariat Exécutif National', 'Secrétariat Exécutif Provincial', 'Secrétariat Exécutif Local'] as $key) {
            if (isset($agentsByOrgane[$key])) {
                $ordered[$key] = $agentsByOrgane[$key];
            }
        }
        if (isset($agentsByOrgane['Non assigne'])) {
            $ordered['Non assigne'] = $agentsByOrgane['Non assigne'];
        }
        // Add any remaining organes not yet included
        foreach ($agentsByOrgane as $key => $data) {
            if (!isset($ordered[$key])) {
                $ordered[$key] = $data;
            }
        }

        // Stats
        $stats = [
            'total' => $allAgents->count(),
            'sen' => $allAgents->where('organe', 'Secrétariat Exécutif National')->count(),
            'sep' => $allAgents->where('organe', 'Secrétariat Exécutif Provincial')->count(),
            'sel' => $allAgents->where('organe', 'Secrétariat Exécutif Local')->count(),
        ];

        return response()->json([
            'agentsByOrgane' => array_values($ordered),
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created agent.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matricule_etat' => 'nullable|unique:agents,matricule_etat',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'postnom' => 'nullable|string',
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required|integer|min:1945|max:2100',
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
            'photo' => 'nullable|image|max:2048',
        ]);

        // Default date_naissance from year
        if (empty($validated['date_naissance']) && !empty($validated['annee_naissance'])) {
            $validated['date_naissance'] = $validated['annee_naissance'] . '-01-01';
        }

        // Default date_embauche from year
        if (empty($validated['date_embauche']) && !empty($validated['annee_engagement_programme'])) {
            $validated['date_embauche'] = $validated['annee_engagement_programme'] . '-01-01';
        }

        $validated['poste_actuel'] = $validated['fonction'];

        // Convert empty matricule values to null
        if (empty($validated['matricule_etat'])) {
            $validated['matricule_etat'] = null;
        }

        // Remove domaine_etudes if column doesn't exist
        if (!Schema::hasColumn('agents', 'domaine_etudes')) {
            unset($validated['domaine_etudes']);
        }

        // Populate email column
        if (Schema::hasColumn('agents', 'email')) {
            $validated['email'] = $validated['email_professionnel']
                ?? $validated['email_prive']
                ?? null;
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/profiles'), $filename);
            $validated['photo'] = 'uploads/profiles/' . $filename;
        }

        $agent = Agent::create($validated);
        $agent->load(['role', 'province', 'departement', 'grade', 'institution']);

        return response()->json([
            'message' => 'Agent cree avec succes',
            'agent' => $agent,
        ], 201);
    }

    /**
     * Display the specified agent with all relations.
     */
    public function show(Agent $agent): JsonResponse
    {
        $agent->load([
            'role', 'province', 'departement', 'grade', 'institution',
            'documents', 'requests',
            'affectations.fonction', 'affectations.department', 'affectations.province',
            'messages.sender',
        ]);

        // Add computed fields
        $agentData = $agent->toArray();
        $agentData['id_agent'] = $agent->id_agent;
        $agentData['nom_complet'] = $agent->nom_complet;
        $agentData['anciennete'] = $agent->annee_engagement_programme
            ? (now()->year - $agent->annee_engagement_programme)
            : null;

        return response()->json(['agent' => $agentData]);
    }

    /**
     * Update the specified agent.
     */
    public function update(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'matricule_etat' => 'nullable|unique:agents,matricule_etat,' . $agent->id,
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'postnom' => 'nullable|string',
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required|integer|min:1945|max:2100',
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

        // Default date_naissance from year
        if (empty($validated['date_naissance']) && !empty($validated['annee_naissance'])) {
            $validated['date_naissance'] = $validated['annee_naissance'] . '-01-01';
        }

        // Default date_embauche from year
        if (empty($validated['date_embauche']) && !empty($validated['annee_engagement_programme'])) {
            $validated['date_embauche'] = $validated['annee_engagement_programme'] . '-01-01';
        }

        $validated['poste_actuel'] = $validated['fonction'];

        // Convert empty matricule values to null
        if (empty($validated['matricule_etat'])) {
            $validated['matricule_etat'] = null;
        }

        // Normalize situation_familiale
        if (!empty($validated['situation_familiale'])) {
            $map = [
                'celibataire' => 'celibataire', 'celibataire' => 'celibataire',
                'marie' => 'marie', 'marie(e)' => 'marie',
                'divorce' => 'divorce', 'divorce(e)' => 'divorce',
                'veuf' => 'veuf', 'veuf/veuve' => 'veuf', 'veuve' => 'veuf',
            ];
            $key = mb_strtolower(trim($validated['situation_familiale']));
            $validated['situation_familiale'] = $map[$key] ?? $validated['situation_familiale'];
        }

        // Remove domaine_etudes if column doesn't exist
        if (!Schema::hasColumn('agents', 'domaine_etudes')) {
            unset($validated['domaine_etudes']);
        }

        // Populate email column
        if (Schema::hasColumn('agents', 'email')) {
            $validated['email'] = $validated['email_professionnel']
                ?? $validated['email_prive']
                ?? $agent->email;
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/profiles'), $filename);
            $validated['photo'] = 'uploads/profiles/' . $filename;
        }

        $agent->update($validated);
        $agent->load(['role', 'province', 'departement', 'grade', 'institution']);

        return response()->json([
            'message' => 'Agent modifie avec succes',
            'agent' => $agent,
        ]);
    }

    /**
     * Remove the specified agent.
     */
    public function destroy(Agent $agent): JsonResponse
    {
        $agent->delete();

        return response()->json([
            'message' => 'Agent supprime avec succes',
        ]);
    }

    /**
     * Export agents as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $organe = $request->input('organe');
        $province_id = $request->input('province_id');
        $departement_id = $request->input('departement_id');

        $organeMap = [
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
        ];

        $query = Agent::with(['province', 'departement', 'grade', 'institution']);

        if ($organe && $organe !== 'tous') {
            $organeNom = $organeMap[$organe] ?? null;
            if ($organeNom) {
                $query->where('organe', $organeNom);
            }
        }

        if ($province_id) {
            $query->where('province_id', $province_id);
        }

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

            fputcsv($file, [
                'ID Agent', 'Matricule Etat', 'Nom', 'Postnom', 'Prenom', 'Sexe',
                'Annee naissance', 'Lieu naissance', 'Etat civil', 'Enfants',
                'Telephone', 'Email prive', 'Email institutionnel',
                'Organe', 'Fonction', 'Poste actuel', 'Departement', 'Province',
                'Grade Etat', 'Institution origine', 'Niveau etudes', 'Domaine etudes',
                'Annee engagement', 'Anciennete (ans)', 'Date embauche', 'Statut',
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
     * Return form options for create/edit (organes, departments, provinces, grades, etc.)
     */
    public function formOptions(): JsonResponse
    {
        $organeOptions = Schema::hasTable('organes') ? Organe::where('actif', true)->orderBy('nom')->pluck('nom') : collect();
        $departments = Department::orderBy('nom')->get(['id', 'nom']);
        $provinces = Province::orderBy('nom')->get();
        $grades = Schema::hasTable('grades') ? Grade::orderBy('ordre')->get() : collect();
        $institutionCategories = Schema::hasTable('institution_categories')
            ? InstitutionCategorie::with('institutions')->orderBy('ordre')->get()
            : collect();
        $sections = Schema::hasTable('sections') ? Section::with('department:id,nom')->orderBy('type')->orderBy('nom')->get() : collect();
        $fonctions = Schema::hasTable('fonctions') ? Fonction::orderBy('niveau_administratif')->orderBy('type_poste')->orderBy('nom')->get() : collect();
        $niveauxEtudes = Agent::NIVEAUX_ETUDES;

        return response()->json([
            'organeOptions' => $organeOptions,
            'departments' => $departments,
            'provinces' => $provinces,
            'grades' => $grades,
            'institutionCategories' => $institutionCategories,
            'sections' => $sections,
            'fonctions' => $fonctions,
            'niveauxEtudes' => $niveauxEtudes,
        ]);
    }

    /**
     * Format an agent for the list view.
     */
    private function formatAgentForList(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'id_agent' => $agent->id_agent,
            'nom' => $agent->nom,
            'prenom' => $agent->prenom,
            'postnom' => $agent->postnom,
            'nom_complet' => $agent->nom_complet,
            'email_prive' => $agent->email_prive,
            'email_professionnel' => $agent->email_professionnel,
            'telephone' => $agent->telephone,
            'photo' => $agent->photo,
            'organe' => $agent->organe,
            'fonction' => $agent->fonction,
            'poste_actuel' => $agent->poste_actuel,
            'matricule_etat' => $agent->matricule_etat,
            'annee_engagement_programme' => $agent->annee_engagement_programme,
            'anciennete' => $agent->annee_engagement_programme
                ? (now()->year - $agent->annee_engagement_programme)
                : null,
            'statut' => $agent->statut,
            'province' => $agent->province ? [
                'id' => $agent->province->id,
                'nom' => $agent->province->nom_province ?? $agent->province->nom,
            ] : null,
            'departement' => $agent->departement ? [
                'id' => $agent->departement->id,
                'nom' => $agent->departement->nom,
            ] : null,
            'grade' => $agent->grade ? [
                'id' => $agent->grade->id,
                'libelle' => $agent->grade->libelle,
            ] : null,
        ];
    }
}
