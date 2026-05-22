<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Document;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\InstitutionCategorie;
use App\Models\Organe;
use App\Models\Province;
use App\Models\Section;
use App\Services\SpreadsheetImportReader;
use App\Services\NotificationService;
use App\Services\RoleService;
use App\Services\UserDataScope;
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
    private array $documentManagerRoles = [
        'Section ressources humaines',
        'Chef Section RH',
        'RH National',
        'RH Provincial',
        'Assistant RH',
        'Assistant ressources humaines',
        'Assistant ressource humaine',
    ];

    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    private function canManageAgentDocuments($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasRole($this->documentManagerRoles) || $this->isAssistantRh($user)) {
            return true;
        }

        $role = Str::lower(Str::ascii(trim((string) ($user->role?->nom_role ?? ''))));

        return in_array($role, ['rh', 'ressources humaines', 'chef de section rh', 'chef section ressources humaines'], true)
            || str_contains($role, 'ressource humaine')
            || str_contains($role, 'ressources humaines');
    }

    private function isAssistantRh($user): bool
    {
        return app(RoleService::class)->isAssistantRh($user);
    }

    private function abortIfAssistantRhWithoutPermission($user, string $permissionCode, string $message): void
    {
        if ($this->isAssistantRh($user) && !$user->hasPermission($permissionCode)) {
            abort(403, $message);
        }
    }

    private function authorizeAgentDocumentManagement(Request $request, Agent $agent): void
    {
        if (!$this->canManageAgentDocuments($request->user())) {
            abort(403, 'Seule la Section Ressources Humaines peut télécharger le dossier complet agent.');
        }

        $this->authorizeAgentAccess($request, $agent);
    }

    private function authorizeAgentAccess(Request $request, Agent $agent): void
    {
        if (!$this->scopeService()->canAccessAgent($request->user(), $agent)) {
            abort(403, 'Vous n\'avez pas accès a cet agent.');
        }
    }

    private function resolveNationalExecutiveSecretaryName(): ?string
    {
        $candidates = Agent::query()
            ->with('role')
            ->where(function ($query) {
                $query
                    ->whereHas('role', fn ($roleQuery) => $roleQuery->where('nom_role', 'SEN'))
                    ->orWhere('fonction', 'like', '%Executif National%')
                    ->orWhere('fonction', 'like', '%Exécutif National%')
                    ->orWhere('poste_actuel', 'like', '%Executif National%')
                    ->orWhere('poste_actuel', 'like', '%Exécutif National%');
            })
            ->orderByRaw("CASE WHEN statut = 'actif' THEN 0 ELSE 1 END")
            ->limit(25)
            ->get();

        $sen = $candidates->first(function (Agent $candidate) {
            $role = $this->normalizeSignatureText($candidate->role?->nom_role);
            $fonction = $this->normalizeSignatureText($candidate->fonction);
            $poste = $this->normalizeSignatureText($candidate->poste_actuel);
            $text = trim($fonction . ' ' . $poste . ' ' . $role);

            $isPrincipalSen = $role === 'sen' || str_contains($text, 'secretaire executif national');
            $isAdjoint = $role === 'sena' || str_contains($text, 'adjoint');

            return $isPrincipalSen && !$isAdjoint;
        });

        if (!$sen) {
            return null;
        }

        return trim(collect([$sen->prenom, $sen->nom, $sen->postnom])->filter()->implode(' ')) ?: null;
    }

    private function normalizeSignatureText(?string $value): string
    {
        return Str::lower(Str::ascii(trim((string) $value)));
    }

    private function pnmlsLogoDataUri(): string
    {
        foreach (['images/logo-pnmls.png', 'images/pnmls.jpeg'] as $relativePath) {
            $path = public_path($relativePath);

            if (!is_file($path)) {
                continue;
            }

            $mime = str_ends_with($relativePath, '.jpeg') ? 'image/jpeg' : 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
        }

        return '';
    }

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
        $scope = $this->scopeService();
        $query = Agent::with(['role', 'province', 'departement', 'grade', 'institution']);

        $scope->applyAgentScope($query, $request->user());

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
                  ->when(Schema::hasColumn('agents', 'telephone_professionnel'), fn ($query) => $query->orWhere('telephone_professionnel', 'like', $term))
                  ->when(Schema::hasColumn('agents', 'telephone_prive'), fn ($query) => $query->orWhere('telephone_prive', 'like', $term))
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

        // Filter: agents without any active affectation
        if ($request->boolean('sans_affectation')) {
            $query->whereDoesntHave('affectations', fn($q) => $q->where('actif', true));
        }

        $allAgents = $query->orderInstitutionally()->get();

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
        $this->abortIfAssistantRhWithoutPermission(
            $request->user(),
            'create_agent',
            'L assistant RH doit avoir la permission du Chef Section RH pour créer un nouvel agent.'
        );

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
            'telephone_professionnel' => 'nullable|string',
            'telephone_prive' => 'nullable|string',
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

        $validated = $this->scopeService()->enforceAgentPayloadScope($validated, $request->user());

        // SEN agents must not have a province
        $organe = mb_strtolower(trim($validated['organe'] ?? ''));
        if (str_contains($organe, 'national')) {
            $validated['province_id'] = null;
        }

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
        $validated = $this->normalizeAgentPhoneFields($validated);

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
            'message' => 'Agent créé avec succès.',
            'agent' => $resource->resolve(),
        ], 201);
    }

    /**
     * Display the specified agent with all relations.
     */
    public function show(Agent $agent): JsonResponse
    {
        $this->authorizeAgentAccess(request(), $agent);

        $agent->load([
            'role', 'province', 'departement', 'grade', 'institution',
            'documents', 'requests',
            'affectations.fonction', 'affectations.department', 'affectations.province',
            'messages.sender',
        ]);

        $resource = AgentResource::make($agent);
        $agentPayload = $resource->resolve();
        $agentPayload['permissions'] = [
            'can_manage_documents' => $this->canManageAgentDocuments(request()->user()),
        ];
        $agentPayload['signature'] = [
            'sen_name' => $this->resolveNationalExecutiveSecretaryName(),
            'sen_title' => 'Secrétaire Exécutif National (SEN)',
        ];

        return $this->resource($resource, [], [
            'agent' => $agentPayload,
        ]);
    }

    public function downloadDossier(Request $request, Agent $agent)
    {
        $this->authorizeAgentDocumentManagement($request, $agent);

        if (!class_exists(\ZipArchive::class)) {
            abort(500, 'Le module ZIP PHP est indisponible sur le serveur.');
        }

        $agent->load([
            'role', 'province', 'departement', 'grade', 'institution',
            'documents',
            'affectations.fonction', 'affectations.department', 'affectations.province',
        ]);

        $directory = storage_path('app/tmp');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $zipPath = $directory . DIRECTORY_SEPARATOR . 'dossier-agent-' . $agent->id . '-' . Str::uuid() . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de preparer le dossier agent.');
        }

        $zip->addFromString('fiche-agent.html', $this->buildAgentFicheHtml($agent));

        $missingFiles = [];
        foreach ($agent->documents as $index => $document) {
            $filePath = public_path($document->fichier);
            if (!is_file($filePath)) {
                $missingFiles[] = $this->documentDisplayName($document) . ' (' . $document->fichier . ')';
                continue;
            }

            $zip->addFile($filePath, 'documents/' . $this->documentArchiveName($document, $index + 1));
        }

        if ($missingFiles !== []) {
            $zip->addFromString(
                'documents-manquants.txt',
                "Documents references mais introuvables sur le serveur:\n- " . implode("\n- ", $missingFiles)
            );
        }

        $zip->close();

        return response()
            ->download($zipPath, $this->agentDossierFilename($agent), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function buildAgentFicheHtml(Agent $agent): string
    {
        $documentsRows = $agent->documents->map(function (Document $document) {
            return '<tr>'
                . '<td>' . e($this->documentDisplayName($document)) . '</td>'
                . '<td>' . e($this->documentTypeLabel($document->type)) . '</td>'
                . '<td>' . e($document->statut ?? 'Non renseigné') . '</td>'
                . '<td>' . e($this->dateLabel($document->created_at)) . '</td>'
                . '</tr>';
        })->implode('');

        if ($documentsRows === '') {
            $documentsRows = '<tr><td colspan="4" class="muted">Aucun document associe.</td></tr>';
        }

        $affectationRows = $agent->affectations->sortByDesc('date_debut')->map(function ($affectation) {
            return '<tr>'
                . '<td>' . e($affectation->fonction?->nom ?? 'Non renseigné') . '</td>'
                . '<td>' . e($affectation->department?->nom ?? '-') . '</td>'
                . '<td>' . e($affectation->province?->nom_province ?? $affectation->province?->nom ?? '-') . '</td>'
                . '<td>' . e($this->dateLabel($affectation->date_debut)) . '</td>'
                . '<td>' . e($affectation->date_fin ? $this->dateLabel($affectation->date_fin) : 'En cours') . '</td>'
                . '</tr>';
        })->implode('');

        if ($affectationRows === '') {
            $affectationRows = '<tr><td colspan="5" class="muted">Aucune affectation renseignée.</td></tr>';
        }

        $rows = fn (array $items) => collect($items)->map(fn ($item) =>
            '<div class="info"><span>' . e($item[0]) . '</span><strong>' . e($item[1] ?: 'Non renseigné') . '</strong></div>'
        )->implode('');

        $senSignatureName = $this->resolveNationalExecutiveSecretaryName() ?: 'Nom du SEN non renseigne';
        $logoDataUri = $this->pnmlsLogoDataUri();
        $logoHtml = $logoDataUri !== ''
            ? '<img src="' . e($logoDataUri) . '" alt="Logo PNMLS">'
            : '<div class="brand-fallback">PNMLS</div>';

        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Fiche agent</title>'
            . '<style>body{font-family:Arial,sans-serif;color:#1f2937;margin:32px}h1,h2{margin:0}h1{font-size:26px;color:#075985}.letterhead{display:flex;align-items:center;gap:14px;border-bottom:1px solid #dbeafe;padding-bottom:14px;margin-bottom:18px}.letterhead img{width:74px;height:74px;object-fit:contain}.letterhead small{display:block;color:#64748b;text-transform:uppercase;letter-spacing:.04em;font-size:11px;font-weight:700}.letterhead strong{display:block;color:#075985;font-size:18px;margin-top:4px}.brand-fallback{width:74px;height:74px;border-radius:12px;background:#075985;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800}.sub{color:#64748b;margin-top:6px}.hero{border-bottom:4px solid #0ea5e9;padding-bottom:18px;margin-bottom:24px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.section{margin-top:24px}.section h2{font-size:16px;color:#0369a1;margin-bottom:10px}.info{border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px}.info span{display:block;color:#64748b;font-size:12px;margin-bottom:4px}.info strong{font-size:14px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #e5e7eb;padding:9px;text-align:left;font-size:13px}th{background:#f1f5f9;color:#334155}.muted{color:#64748b}.signature{display:flex;justify-content:flex-end;margin-top:46px;break-inside:avoid}.signature-box{width:300px;text-align:center}.signature-title{font-size:13px;color:#334155;font-weight:700}.signature-line{border-top:1px solid #111827;margin:52px 0 10px}.signature-name{font-size:15px;color:#111827;font-weight:800;text-transform:uppercase}.signature-note{font-size:12px;color:#64748b;margin-top:4px}.footer{margin-top:28px;color:#64748b;font-size:12px}</style>'
            . '</head><body>'
            . '<div class="letterhead">' . $logoHtml . '<div><small>Programme National Multisectoriel de Lutte contre le Sida</small><strong>Fiche complete de l agent</strong></div></div>'
            . '<div class="hero"><h1>' . e($agent->nom_complet) . '</h1><div class="sub">' . e($agent->matricule_etat ?? 'N/A') . ' - ' . e($agent->fonction ?? $agent->poste_actuel ?? 'Fonction non renseignée') . '</div></div>'
            . '<div class="section"><h2>Informations essentielles</h2><div class="grid">' . $rows([
                ['Matricule Etat', $agent->matricule_etat],
                ['Statut', $agent->statut],
                ['Sexe', $agent->sexe],
                ['Date et lieu de naissance', trim($this->dateLabel($agent->date_naissance) . ' - ' . ($agent->lieu_naissance ?? ''))],
                ['Situation familiale', $agent->situation_familiale],
                ['Nombre d enfants', (string) ($agent->nombre_enfants ?? '')],
                ['Téléphone professionnel', $agent->telephone_professionnel ?: $agent->telephone],
                ['Téléphone privé', $agent->telephone_prive],
                ['Email institutionnel', $agent->email_professionnel ?? $agent->email],
                ['Email prive', $agent->email_prive],
                ['Adresse', $agent->adresse],
            ]) . '</div></div>'
            . '<div class="section"><h2>Donnees administratives et affectation</h2><div class="grid">' . $rows([
                ['Organe', $agent->organe],
                ['Département', $agent->departement?->nom],
                ['Province', $agent->province?->nom_province ?? $agent->province?->nom],
                ['Fonction actuelle', $agent->fonction],
                ['Grade Etat', $agent->grade?->libelle ?? $agent->grade_etat],
                ['Institution origine', $agent->institution?->nom],
                ['Niveau d etudes', $agent->niveau_etudes],
                ['Domaine d etudes', $agent->domaine_etudes],
                ['Annee engagement', (string) ($agent->annee_engagement_programme ?? '')],
                ['Date embauche', $this->dateLabel($agent->date_embauche)],
            ]) . '</div></div>'
            . '<div class="section"><h2>Parcours</h2><table><thead><tr><th>Fonction</th><th>Département</th><th>Province</th><th>Début</th><th>Fin</th></tr></thead><tbody>' . $affectationRows . '</tbody></table></div>'
            . '<div class="section"><h2>Documents disponibles</h2><table><thead><tr><th>Document</th><th>Categorie</th><th>Statut</th><th>Date</th></tr></thead><tbody>' . $documentsRows . '</tbody></table></div>'
            . '<div class="signature"><div class="signature-box"><div class="signature-title">Le Secrétaire Exécutif National (SEN)</div><div class="signature-line"></div><div class="signature-name">' . e($senSignatureName) . '</div><div class="signature-note">Signature et cachet</div></div></div>'
            . '<div class="footer">Dossier genere depuis E-PNMLS le ' . e(now()->format('d/m/Y H:i')) . '.</div>'
            . '</body></html>';
    }

    private function documentDisplayName(Document $document): string
    {
        $parts = explode(' | ', (string) $document->description);

        return trim($parts[0] ?? '') ?: 'Document ' . $document->id;
    }

    private function documentTypeLabel(?string $type): string
    {
        return [
            'identite' => 'Identité',
            'parcours' => 'Parcours académique et formation',
            'carriere' => 'Carrière administrative',
            'gestion_rh' => 'Gestion RH',
            'documents_legaux' => 'Documents légaux',
            'autres' => 'Autres',
            'mission' => 'Autres',
        ][$type] ?? ($type ?: 'Non renseigné');
    }

    private function documentArchiveName(Document $document, int $index): string
    {
        $extension = pathinfo((string) $document->fichier, PATHINFO_EXTENSION) ?: 'bin';
        $name = Str::slug(Str::ascii($this->documentDisplayName($document))) ?: 'document';

        return sprintf('%02d-%s-%d.%s', $index, $name, $document->id, $extension);
    }

    private function agentDossierFilename(Agent $agent): string
    {
        $name = Str::slug(Str::ascii($agent->nom_complet)) ?: 'agent';

        return 'dossier-agent-' . $name . '-' . $agent->id . '.zip';
    }

    private function dateLabel($value): string
    {
        if (!$value) {
            return 'Non renseigné';
        }

        if (is_object($value) && method_exists($value, 'format')) {
            return $value->format('d/m/Y');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('d/m/Y', $timestamp) : (string) $value;
    }

    /**
     * Update the specified agent.
     */
    public function update(Request $request, Agent $agent): JsonResponse
    {
        $this->abortIfAssistantRhWithoutPermission(
            $request->user(),
            'edit_agent',
            'L assistant RH doit avoir la permission du Chef Section RH pour modifier un agent.'
        );

        $this->authorizeAgentAccess($request, $agent);

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
            'telephone_professionnel' => 'nullable|string',
            'telephone_prive' => 'nullable|string',
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

        $validated = $this->scopeService()->enforceAgentPayloadScope($validated, $request->user());

        // SEN agents must not have a province (otherwise they appear in provincial RH scope)
        $organe = mb_strtolower(trim($validated['organe'] ?? ''));
        if (str_contains($organe, 'national')) {
            $validated['province_id'] = null;
        }

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
        $validated = $this->normalizeAgentPhoneFields($validated);

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

        NotificationService::notifierAgent(
            $agent,
            'message',
            'Votre fiche agent a été mise à jour',
            'Des informations de votre dossier agent ont ete modifiees dans E-PNMLS.',
            '/profile',
            $request->user()->id
        );

        $resource = AgentResource::make($agent);

        return $this->resource($resource, [], [
            'message' => 'Agent modifié avec succès',
            'agent' => $resource->resolve(),
        ]);
    }

    /**
     * Remove the specified agent.
     */
    public function destroy(Agent $agent): JsonResponse
    {
        $this->abortIfAssistantRhWithoutPermission(
            request()->user(),
            'delete_agent',
            'L assistant RH doit avoir la permission du Chef Section RH pour supprimer un agent.'
        );

        $this->authorizeAgentAccess(request(), $agent);

        $agent->delete();

        return $this->success(null, [], [
            'message' => 'Agent supprimé avec succès',
        ]);
    }

    /**
     * Export agents as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $scope = $this->scopeService();
        $organe = $request->input('organe');
        $province_id = $request->input('province_id');
        $departement_id = $request->input('departement_id');

        $organeMap = [
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
        ];

        $query = Agent::with(['province', 'departement', 'grade', 'institution']);

        $scope->applyAgentScope($query, $request->user());

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

        $agents = $query->orderInstitutionally()->get();

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
                'Matricule Etat', 'Nom', 'Postnom', 'Prenom', 'Sexe',
                'Annee naissance', 'Lieu naissance', 'Etat civil', 'Enfants',
                'Téléphone principal', 'Téléphone professionnel', 'Téléphone privé', 'Email prive', 'Email institutionnel',
                'Organe', 'Fonction', 'Poste actuel', 'Département', 'Province',
                'Grade Etat', 'Institution origine', 'Niveau etudes', 'Domaine etudes',
                'Annee engagement', 'Anciennete (ans)', 'Date embauche', 'Statut',
            ], ';');

            foreach ($agents as $agent) {
                $anciennete = $agent->annee_engagement_programme
                    ? (now()->year - $agent->annee_engagement_programme)
                    : '';

                fputcsv($file, [
                    $agent->matricule_etat ?? 'N/A',
                    $agent->nom,
                    $agent->postnom ?? '',
                    $agent->prenom,
                    $agent->sexe,
                    $agent->annee_naissance ?? '',
                    $agent->lieu_naissance ?? '',
                    $agent->situation_familiale ?? '',
                    $agent->nombre_enfants ?? '',
                    $agent->telephone ?? '',
                    ($agent->telephone_professionnel ?: $agent->telephone) ?? '',
                    $agent->telephone_prive ?? '',
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
        $scope = $this->scopeService();
        $user = request()->user();
        $organeOptions = Schema::hasTable('organes') ? Organe::where('actif', true)->orderBy('nom')->pluck('nom') : collect();
        $departmentsCollection = $scope->filterDepartments(Department::query(), $user)->orderBy('nom')->get(['id', 'nom']);
        // Ajouter l'option SEN (rattachement direct) si des agents actifs SEN sans département existent
        $hasSenDirect = Agent::actifs()
            ->where('organe', 'Secrétariat Exécutif National')
            ->whereNull('departement_id')
            ->exists();
        if ($hasSenDirect) {
            $senDirect = new \stdClass();
            $senDirect->id = 0;
            $senDirect->nom = 'SEN (rattachement direct)';
            $departmentsCollection = $departmentsCollection->prepend($senDirect);
        }
        $departments = $departmentsCollection;
        $provinces = $scope->filterProvinces(Province::query(), $user)->orderBy('nom')->get();
        $grades = Schema::hasTable('grades') ? Grade::orderBy('ordre')->get() : collect();
        $institutionCategories = Schema::hasTable('institution_categories')
            ? InstitutionCategorie::with('institutions')->orderBy('ordre')->get()
            : collect();
        $sections = Schema::hasTable('sections')
            ? Section::with('department:id,nom')
                ->when($scope->isProvincialRh($user), function ($query) use ($scope, $user) {
                    $provinceId = $scope->provinceId($user);

                    if (!$provinceId) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereHas('department', function ($departmentQuery) use ($provinceId) {
                        $departmentQuery->where('province_id', $provinceId);
                    });
                })
                ->orderBy('type')
                ->orderBy('nom')
                ->get()
            : collect();
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
                'telephone_professionnel',
                'telephone_prive',
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
                '0998765432',
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
            'lieu_naissance' => $lieuNaissanceValue ?? $provinceValue ?? 'Non renseigné',
            'situation_familiale' => $this->normalizeSituationFamiliale($this->importValue($row, $headerIndexes, 'situation_familiale')),
            'nombre_enfants' => $this->normalizeImportedInteger($this->importValue($row, $headerIndexes, 'nombre_enfants')),
            'telephone' => $this->importValue($row, $headerIndexes, 'telephone'),
            'telephone_professionnel' => $this->importValue($row, $headerIndexes, 'telephone_professionnel'),
            'telephone_prive' => $this->importValue($row, $headerIndexes, 'telephone_prive'),
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

        $payload = $this->removeImportedEmailDuplicates($payload);

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

        $validated = $this->normalizeAgentPhoneFields($validated);

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
            'telephone_professionnel' => 'nullable|string',
            'telephone_prive' => 'nullable|string',
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
            'telephone_professionnel' => 'telephone_professionnel',
            'telephone_pro' => 'telephone_professionnel',
            'tel_professionnel' => 'telephone_professionnel',
            'tel_pro' => 'telephone_professionnel',
            'telephone_prive' => 'telephone_prive',
            'telephone_personnel' => 'telephone_prive',
            'tel_prive' => 'telephone_prive',
            'tel_personnel' => 'telephone_prive',
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

    private function normalizeAgentPhoneFields(array $validated): array
    {
        $legacy = $this->nullableTrim($validated['telephone'] ?? null);
        $professional = $this->nullableTrim($validated['telephone_professionnel'] ?? null) ?: $legacy;
        $private = $this->nullableTrim($validated['telephone_prive'] ?? null);

        $validated['telephone'] = $professional ?: $private;

        if (Schema::hasColumn('agents', 'telephone_professionnel')) {
            $validated['telephone_professionnel'] = $professional;
        } else {
            unset($validated['telephone_professionnel']);
        }

        if (Schema::hasColumn('agents', 'telephone_prive')) {
            $validated['telephone_prive'] = $private;
        } else {
            unset($validated['telephone_prive']);
        }

        return $validated;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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

    private function removeImportedEmailDuplicates(array $payload): array
    {
        foreach (['email_professionnel', 'email_prive'] as $field) {
            $email = $payload[$field] ?? null;

            if ($email === null || $email === '') {
                continue;
            }

            if ($this->importedEmailAlreadyExists((string) $email)) {
                $payload[$field] = null;
            }
        }

        return $payload;
    }

    private function importedEmailAlreadyExists(string $email): bool
    {
        $query = Agent::query()->where('email', $email);

        if (Schema::hasColumn('agents', 'email_prive')) {
            $query->orWhere('email_prive', $email);
        }

        if (Schema::hasColumn('agents', 'email_professionnel')) {
            $query->orWhere('email_professionnel', $email);
        }

        return $query->exists();
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
