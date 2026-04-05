<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\InstitutionCategorie;
use App\Models\Organe;
use App\Models\Province;
use App\Models\Section;
use App\Services\SpreadsheetImportReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentController extends ApiController
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
                  ->orWhere('email_prive', 'like', $term)
                  ->orWhere('email_professionnel', 'like', $term)
                  ->orWhere('matricule_etat', 'like', $term)
                  ->orWhere('telephone', 'like', $term)
                  ->orWhere('fonction', 'like', $term)
                  ->orWhere('grade_etat', 'like', $term)
                  ->orWhere('niveau_etudes', 'like', $term)
                  ->orWhere('annee_engagement_programme', 'like', $term)
                  ->orWhere('poste_actuel', 'like', $term)
                  ->orWhereHas('province', fn($q) => $q->where('nom', 'like', $term))
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

        // Status filter — default to 'actif' only
        $statut = $request->query('statut');
        if ($statut) {
            $query->where('statut', $statut);
        } else {
            $query->where('statut', 'actif');
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
            ->orderByRaw("CASE
                WHEN LOWER(fonction) LIKE '%adjoint%' THEN 2
                ELSE 1
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
            $agentsByOrgane[$organeKey]['agents'][] = AgentResource::make($agent)->resolve();
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

        return $this->success(
            array_values($ordered),
            ['stats' => $stats],
            [
                'agentsByOrgane' => array_values($ordered),
                'stats' => $stats,
            ]
        );
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

        $validated['situation_familiale'] = $this->defaultSituationFamiliale($validated['situation_familiale'] ?? null);
        $validated['nombre_enfants'] = $this->defaultNombreEnfants($validated['nombre_enfants'] ?? null);

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

        $resource = AgentResource::make($agent);

        return $this->resource($resource, [], [
            'message' => 'Agent cree avec succes',
            'agent' => $resource->resolve(),
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

        $resource = AgentResource::make($agent);

        return $this->resource($resource, [], [
            'agent' => $resource->resolve(),
        ]);
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
                'celibataire' => 'célibataire',
                'marie' => 'marié', 'marie(e)' => 'marié',
                'divorce' => 'divorcé', 'divorce(e)' => 'divorcé',
                'veuf' => 'veuf', 'veuf/veuve' => 'veuf', 'veuve' => 'veuf',
            ];
            $key = mb_strtolower(trim($validated['situation_familiale']));
            $validated['situation_familiale'] = $map[$key] ?? $validated['situation_familiale'];
        }

        $validated['situation_familiale'] = $this->defaultSituationFamiliale($validated['situation_familiale'] ?? null);
        $validated['nombre_enfants'] = $this->defaultNombreEnfants($validated['nombre_enfants'] ?? null);

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

        $resource = AgentResource::make($agent);

        return $this->resource($resource, [], [
            'message' => 'Agent modifie avec succes',
            'agent' => $resource->resolve(),
        ]);
    }

    /**
     * Remove the specified agent.
     */
    public function destroy(Agent $agent): JsonResponse
    {
        $agent->delete();

        return $this->success(null, [], [
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

    public function import(Request $request, SpreadsheetImportReader $reader): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:xlsx,csv,txt',
        ]);

        $rows = $reader->read($request->file('file'));
        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'Le fichier doit contenir une ligne d\'en-tete et au moins une ligne de donnees.',
            ]);
        }

        $headerIndexes = $this->resolveImportHeaderIndexes(array_shift($rows));
        $requiredHeaders = ['nom', 'prenom', 'sexe', 'lieu_naissance', 'organe', 'fonction', 'niveau_etudes', 'annee_engagement_programme'];
        $missingHeaders = array_values(array_filter($requiredHeaders, fn($header) => !array_key_exists($header, $headerIndexes)));

        if ($missingHeaders) {
            throw ValidationException::withMessages([
                'file' => 'Colonnes obligatoires manquantes: ' . implode(', ', $missingHeaders) . '.',
            ]);
        }

        if (!array_key_exists('annee_naissance', $headerIndexes) && !array_key_exists('date_naissance', $headerIndexes)) {
            throw ValidationException::withMessages([
                'file' => 'Le fichier doit contenir soit la colonne annee_naissance, soit la colonne date_naissance.',
            ]);
        }

        $lookups = $this->buildImportLookups();
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isImportRowEmpty($row)) {
                continue;
            }

            try {
                $payload = $this->buildImportPayload($row, $headerIndexes, $lookups);
                $validated = validator($payload, $this->importValidationRules())->validate();
                $validated = $this->finalizeImportedPayload($validated);

                DB::transaction(function () use ($validated) {
                    Agent::create($validated);
                });

                $imported++;
            } catch (ValidationException $exception) {
                $skipped++;
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'field' => $field,
                            'message' => $message,
                        ];
                    }
                }
            } catch (\Throwable $exception) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'ligne',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => $imported > 0
                ? 'Import des agents termine.'
                : 'Aucun agent n\'a pu etre importe.',
            'summary' => [
                'total_rows' => count($rows),
                'imported' => $imported,
                'skipped' => $skipped,
            ],
            'errors' => $errors,
        ]);
    }

    public function importTemplate(): StreamedResponse
    {
        $filename = 'modele_import_agents.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'matricule_etat',
                'nom',
                'postnom',
                'prenom',
                'sexe',
                'annee_naissance',
                'date_naissance',
                'lieu_naissance',
                'situation_familiale',
                'nombre_enfants',
                'telephone',
                'adresse',
                'email_prive',
                'email_professionnel',
                'organe',
                'fonction',
                'province',
                'departement',
                'grade',
                'institution',
                'niveau_etudes',
                'domaine_etudes',
                'annee_engagement_programme',
                'statut',
            ], ';');

            fputcsv($handle, [
                'A12345',
                'Mukendi',
                'Kabeya',
                'Jean',
                'M',
                '1988',
                '1988-06-15',
                'Kinshasa',
                'marie',
                '3',
                '0812345678',
                'Commune de la Gombe',
                'jean@gmail.com',
                'jean.mukendi@pnmls.cd',
                'Secrétariat Exécutif National',
                'Chef de division',
                '',
                'Administration',
                'Directeur',
                '',
                'Licence',
                'Gestion',
                '2018',
                'actif',
            ], ';');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function resolveImportHeaderIndexes(array $headers): array
    {
        $indexes = [];

        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeImportHeader((string) $header);
            $canonical = $this->headerAliases()[$normalized] ?? null;

            if ($canonical && !array_key_exists($canonical, $indexes)) {
                $indexes[$canonical] = $index;
            }
        }

        return $indexes;
    }

    private function buildImportPayload(array $row, array $headerIndexes, array $lookups): array
    {
        $provinceValue = $this->importValue($row, $headerIndexes, 'province');
        $fonctionValue = $this->importValue($row, $headerIndexes, 'fonction');
        $institutionValue = $this->importValue($row, $headerIndexes, 'institution');
        $niveauEtudesValue = $this->defaultImportedNiveauEtudes($this->importValue($row, $headerIndexes, 'niveau_etudes'));
        $lieuNaissanceValue = $this->importValue($row, $headerIndexes, 'lieu_naissance');

        $payload = [
            'matricule_etat' => $this->normalizeImportedMatricule($this->importValue($row, $headerIndexes, 'matricule_etat')),
            'nom' => $this->importValue($row, $headerIndexes, 'nom'),
            'postnom' => $this->importValue($row, $headerIndexes, 'postnom'),
            'prenom' => $this->importValue($row, $headerIndexes, 'prenom'),
            'sexe' => $this->normalizeImportedSexe($this->importValue($row, $headerIndexes, 'sexe')),
            'annee_naissance' => $this->normalizeImportedBirthYear($this->importValue($row, $headerIndexes, 'annee_naissance')),
            'date_naissance' => $this->normalizeImportedDate($this->importValue($row, $headerIndexes, 'date_naissance')),
            'lieu_naissance' => $lieuNaissanceValue ?? $provinceValue ?? 'Non renseigne',
            'situation_familiale' => $this->normalizeSituationFamiliale($this->importValue($row, $headerIndexes, 'situation_familiale')),
            'nombre_enfants' => $this->normalizeImportedInteger($this->importValue($row, $headerIndexes, 'nombre_enfants')),
            'telephone' => $this->importValue($row, $headerIndexes, 'telephone'),
            'adresse' => $this->importValue($row, $headerIndexes, 'adresse'),
            'email_prive' => $this->normalizeImportedEmail($this->importValue($row, $headerIndexes, 'email_prive')),
            'email_professionnel' => $this->normalizeImportedEmail($this->importValue($row, $headerIndexes, 'email_professionnel')),
            'organe' => $this->normalizeImportedOrgane($this->importValue($row, $headerIndexes, 'organe')),
            'fonction' => $this->normalizeImportedFonction($fonctionValue, $lookups['fonctions']),
            'province_id' => $this->resolveLookupValue($provinceValue, $lookups['provinces'], 'province'),
            'departement_id' => $this->resolveLookupValue($this->importValue($row, $headerIndexes, 'departement'), $lookups['departements'], 'departement'),
            'grade_id' => $this->resolveLookupValue($this->importValue($row, $headerIndexes, 'grade'), $lookups['grades'], 'grade'),
            'institution_id' => $this->resolveOptionalLookupValue($institutionValue, $lookups['institutions']),
            'niveau_etudes' => $this->resolveLookupValue($niveauEtudesValue, $lookups['niveaux_etudes'], 'niveau_etudes'),
            'domaine_etudes' => $this->importValue($row, $headerIndexes, 'domaine_etudes'),
            'annee_engagement_programme' => $this->defaultImportedEngagementYear($this->normalizeImportedYear($this->importValue($row, $headerIndexes, 'annee_engagement_programme'))),
            'statut' => $this->normalizeImportedStatut($this->importValue($row, $headerIndexes, 'statut')),
        ];

        if ($payload['matricule_etat'] === '') {
            $payload['matricule_etat'] = null;
        }

        if ($payload['matricule_etat'] !== null && Agent::query()->where('matricule_etat', $payload['matricule_etat'])->exists()) {
            $payload['matricule_etat'] = null;
        }

        return $payload;
    }

    private function finalizeImportedPayload(array $validated): array
    {
        if (empty($validated['date_naissance']) && !empty($validated['annee_naissance'])) {
            $validated['date_naissance'] = $validated['annee_naissance'] . '-01-01';
        }

        if (empty($validated['date_embauche']) && !empty($validated['annee_engagement_programme'])) {
            $validated['date_embauche'] = $validated['annee_engagement_programme'] . '-01-01';
        }

        $validated['situation_familiale'] = $this->defaultSituationFamiliale($validated['situation_familiale'] ?? null);
        $validated['nombre_enfants'] = $this->defaultNombreEnfants($validated['nombre_enfants'] ?? null);

        $validated['poste_actuel'] = $validated['fonction'] ?? null;

        if (Schema::hasColumn('agents', 'email')) {
            $validated['email'] = $validated['email_professionnel']
                ?? $validated['email_prive']
                ?? null;
        }

        if (!Schema::hasColumn('agents', 'domaine_etudes')) {
            unset($validated['domaine_etudes']);
        }

        return $validated;
    }

    private function importValidationRules(): array
    {
        return [
            'matricule_etat' => 'nullable|unique:agents,matricule_etat',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'postnom' => 'nullable|string',
            'email_prive' => 'nullable|email',
            'email_professionnel' => 'nullable|email',
            'annee_naissance' => 'required_without:date_naissance|nullable|integer|min:1945|max:2100',
            'date_naissance' => 'required_without:annee_naissance|nullable|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'situation_familiale' => 'nullable|string',
            'nombre_enfants' => 'nullable|integer|min:0',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'organe' => 'required|string|max:255',
            'fonction' => 'required|string|max:255',
            'grade_id' => 'nullable|exists:grades,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'niveau_etudes' => ['required', 'string', Rule::in(Agent::NIVEAUX_ETUDES)],
            'domaine_etudes' => 'nullable|string|max:255',
            'annee_engagement_programme' => 'required|integer|min:1950|max:2100',
            'departement_id' => 'nullable|exists:departments,id',
            'province_id' => 'nullable|exists:provinces,id',
            'statut' => 'nullable|in:actif,suspendu,ancien',
        ];
    }

    private function buildImportLookups(): array
    {
        $fonctions = Fonction::query()->pluck('nom')->all();
        $departements = Department::query()->get(['id', 'nom']);
        $provinceColumns = ['id', 'nom'];
        if (Schema::hasColumn('provinces', 'nom_province')) {
            $provinceColumns[] = 'nom_province';
        }
        $provinces = Province::query()->get($provinceColumns);
        $gradeColumns = ['id'];
        if (Schema::hasTable('grades')) {
            if (Schema::hasColumn('grades', 'libelle')) {
                $gradeColumns[] = 'libelle';
            }
            if (Schema::hasColumn('grades', 'nom')) {
                $gradeColumns[] = 'nom';
            }
        }
        $grades = Schema::hasTable('grades') ? Grade::query()->get($gradeColumns) : collect();
        $institutions = Schema::hasTable('institutions') ? Institution::query()->get(['id', 'nom']) : collect();

        $niveauEtudes = [];
        foreach (Agent::NIVEAUX_ETUDES as $niveau) {
            $niveauEtudes[$this->normalizeImportHeader($niveau)] = $niveau;
        }

        $niveauEtudes['doctorat'] = 'Doctorat (PhD)';
        $niveauEtudes['doctorat_phd'] = 'Doctorat (PhD)';
        $niveauEtudes['diplome_d_etat'] = 'Diplôme d\'État';
        $niveauEtudes['diplome_etat'] = 'Diplôme d\'État';
        $niveauEtudes['diplome_d_etudes_superieures_des'] = 'Diplôme d\'Études Supérieures (DES)';
        $niveauEtudes['des'] = 'Diplôme d\'Études Supérieures (DES)';

        return [
            'fonctions' => collect($fonctions)->mapWithKeys(fn($nom) => [$this->normalizeImportHeader($nom) => $nom])->all(),
            'departements' => $departements->mapWithKeys(fn($department) => [$this->normalizeImportHeader($department->nom) => $department->id])->all(),
            'provinces' => $provinces->mapWithKeys(function ($province) {
                $keys = [$this->normalizeImportHeader($province->nom_province ?? $province->nom) => $province->id];
                if (!empty($province->nom)) {
                    $keys[$this->normalizeImportHeader($province->nom)] = $province->id;
                }
                return $keys;
            })->all(),
            'grades' => collect($grades)->mapWithKeys(function ($grade) {
                $keys = [];
                if (!empty($grade->libelle)) {
                    $keys[$this->normalizeImportHeader($grade->libelle)] = $grade->id;
                }
                if (!empty($grade->nom)) {
                    $keys[$this->normalizeImportHeader($grade->nom)] = $grade->id;
                }
                return $keys;
            })->all(),
            'institutions' => collect($institutions)->mapWithKeys(fn($institution) => [$this->normalizeImportHeader($institution->nom) => $institution->id])->all(),
            'niveaux_etudes' => $niveauEtudes,
        ];
    }

    private function resolveLookupValue(mixed $value, array $lookup, string $field): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = $this->normalizeImportHeader((string) $value);
        if (array_key_exists($key, $lookup)) {
            return $lookup[$key];
        }

        throw ValidationException::withMessages([
            $field => 'Valeur introuvable pour ' . $field . ' : ' . $value . '.',
        ]);
    }

    private function resolveOptionalLookupValue(mixed $value, array $lookup): mixed
    {
        if ($value === null || $value === '' || $lookup === []) {
            return null;
        }

        $key = $this->normalizeImportHeader((string) $value);
        return $lookup[$key] ?? null;
    }

    private function importValue(array $row, array $headerIndexes, string $field): mixed
    {
        if (!array_key_exists($field, $headerIndexes)) {
            return null;
        }

        $index = $headerIndexes[$field];
        if (!array_key_exists($index, $row)) {
            return null;
        }

        $value = $row[$index];
        if (!is_string($value)) {
            return $value;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = Str::ascii($header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        return trim((string) $header, '_');
    }

    private function headerAliases(): array
    {
        return [
            'matricule' => 'matricule_etat',
            'matricule_etat' => 'matricule_etat',
            'nom' => 'nom',
            'postnom' => 'postnom',
            'post_nom' => 'postnom',
            'prenom' => 'prenom',
            'sexe' => 'sexe',
            'annee_naissance' => 'annee_naissance',
            'date_naissance' => 'date_naissance',
            'lieu_naissance' => 'lieu_naissance',
            'situation_familiale' => 'situation_familiale',
            'etat_civil' => 'situation_familiale',
            'nombre_enfants' => 'nombre_enfants',
            'enfants' => 'nombre_enfants',
            'telephone' => 'telephone',
            'adresse' => 'adresse',
            'email_prive' => 'email_prive',
            'email_personnel' => 'email_prive',
            'email_professionnel' => 'email_professionnel',
            'email_pro' => 'email_professionnel',
            'email_institutionnel' => 'email_professionnel',
            'organe' => 'organe',
            'fonction' => 'fonction',
            'province' => 'province',
            'departement' => 'departement',
            'department' => 'departement',
            'grade' => 'grade',
            'grade_etat' => 'grade',
            'institution' => 'institution',
            'niveau_etudes' => 'niveau_etudes',
            'domaine_etudes' => 'domaine_etudes',
            'annee_engagement' => 'annee_engagement_programme',
            'annee_engagement_programme' => 'annee_engagement_programme',
            'statut' => 'statut',
        ];
    }

    private function normalizeImportedSexe(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeImportHeader((string) $value);

        return match ($normalized) {
            'm', 'masculin', 'male', 'homme' => 'M',
            'f', 'feminin', 'female', 'femme' => 'F',
            default => $value,
        };
    }

    private function normalizeImportedOrgane(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeImportHeader((string) $value);

        return match ($normalized) {
            'sen', 'secretariat_executif_national' => 'Secrétariat Exécutif National',
            'sep', 'secretariat_executif_provincial' => 'Secrétariat Exécutif Provincial',
            'sel', 'secretariat_executif_local' => 'Secrétariat Exécutif Local',
            default => $value,
        };
    }

    private function normalizeSituationFamiliale(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeImportHeader((string) $value);

        return match ($normalized) {
            'celibataire' => 'célibataire',
            'marie', 'marie_e' => 'marié',
            'divorce', 'divorce_e' => 'divorcé',
            'veuf', 'veuve', 'veuf_veuve' => 'veuf',
            default => $value,
        };
    }

    private function defaultSituationFamiliale(mixed $value): string
    {
        if ($value === null) {
            return 'célibataire';
        }

        if (is_string($value) && trim($value) === '') {
            return 'célibataire';
        }

        return (string) $value;
    }

    private function defaultNombreEnfants(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    private function defaultImportedNiveauEtudes(mixed $value): string
    {
        if ($value === null) {
            return 'Brevet';
        }

        if (is_string($value) && trim($value) === '') {
            return 'Brevet';
        }

        return (string) $value;
    }

    private function normalizeImportedBirthYear(mixed $value): ?int
    {
        $year = $this->normalizeImportedYear($value);

        if ($year === null) {
            return null;
        }

        return $year < 1945 ? 1945 : $year;
    }

    private function defaultImportedEngagementYear(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 2004;
        }

        return (int) $value;
    }

    private function normalizeImportedStatut(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'actif';
        }

        $normalized = $this->normalizeImportHeader((string) $value);

        return match ($normalized) {
            'actif', 'active' => 'actif',
            'suspendu', 'suspendue' => 'suspendu',
            'ancien', 'ancienne', 'inactif', 'inactive' => 'ancien',
            default => (string) $value,
        };
    }

    private function normalizeImportedYear(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/(19\d{2}|20\d{2}|2100)/', $value, $matches)) {
            return (int) $matches[1];
        }

        $digits = preg_replace('/[^0-9]/', '', $value);
        return $digits === '' ? null : (int) $digits;
    }

    private function normalizeImportedInteger(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }

    private function normalizeImportedDate(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        return $value;
    }

    private function normalizeImportedEmail(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace([',', ';'], ' ', trim((string) $value));
        $parts = preg_split('/\s+/', $value) ?: [];

        foreach ($parts as $part) {
            $candidate = trim($part);
            if (
                $candidate !== ''
                && filter_var($candidate, FILTER_VALIDATE_EMAIL)
                && !preg_match('/^pasdemail\d+@pnmls\.cd$/i', $candidate)
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeImportedMatricule(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $candidate = trim((string) $value);
        $normalized = $this->normalizeImportHeader($candidate);

        if (in_array($normalized, ['nu', 'na', 'n_a', 'vide', 'non_renseigne', 'sans_matricule'], true)) {
            return null;
        }

        return $candidate;
    }

    private function normalizeImportedFonction(mixed $value, array $lookup): mixed
    {
        if ($value === null || $value === '') {
            return 'Chauffeur';
        }

        $key = $this->normalizeImportHeader((string) $value);

        $aliases = [
            'secretaire_executif_provincial' => 'Secrétaire Exécutif Provincial (SEP)',
            'chef_de_cellule_planification_et_suivi_evaluation' => 'Chef de Cellule — Planification, Suivi-Évaluation et Renforcement des Capacités',
            'chef_de_cellule_planification_suivi_evaluation' => 'Chef de Cellule — Planification, Suivi-Évaluation et Renforcement des Capacités',
            'chef_de_cellule_planification_suivi_evaluation_et_renforcement_des_capacites' => 'Chef de Cellule — Planification, Suivi-Évaluation et Renforcement des Capacités',
            'chef_de_cellule_planification' => 'Chef de Cellule — Planification, Suivi-Évaluation et Renforcement des Capacités',
            'chef_de_cellule_suivi_evaluation' => 'Chef de Cellule — Planification, Suivi-Évaluation et Renforcement des Capacités',
            'chef_de_cellule_partenariat_et_appui_aux_secteurs' => 'Chef de Cellule Partenariat et Appui aux Secteurs',
            'chef_de_cellule_administration_et_finances' => 'Chef de Cellule Administration et Finances (CAF)',
            'secretaire_caissiere' => 'Secrétaire Caissier',
            'secretaire_caissier' => 'Secrétaire Caissier',
            'secretaire_econome' => 'Secrétaire Caissier',
            'secretaire_econome_' => 'Secrétaire Caissier',
            'technicienne_de_surface' => 'Technicien de Surface',
            'sentinelle' => 'Sentinelle (Gardien)',
            'gardier' => 'Sentinelle (Gardien)',
        ];

        $canonical = $aliases[$key] ?? ($lookup[$key] ?? trim((string) $value));

        return $canonical;
    }

    private function isImportRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

}
