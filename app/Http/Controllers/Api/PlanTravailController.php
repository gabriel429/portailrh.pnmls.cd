<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ActivitePlanResource;
use App\Models\ActivitePlan;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Department;
use App\Models\Province;
use App\Models\Localite;
use App\Services\NotificationService;
use App\Services\UserDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PlanTravailController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    private function getScopedAgent(): ?Agent
    {
        $user = auth()->user();

        if (!$user || $this->scopeService()->hasGlobalAdminAccess($user)) {
            return null;
        }

        return $user->agent;
    }

    private function normalizeScopeText(?string $value): string
    {
        $value = trim((string) $value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return mb_strtolower($ascii !== false ? $ascii : $value);
    }

    private function isProvincialAgent(?Agent $agent): bool
    {
        return str_contains($this->normalizeScopeText($agent?->organe), 'provincial');
    }

    private function applyProvinceScope(Builder $query, Agent $agent): Builder
    {
        if (!$agent->province_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('niveau_administratif', 'SEP')
            ->where(function (Builder $provinceQuery) use ($agent) {
                $provinceQuery
                    ->where('province_id', $agent->province_id)
                    ->orWhereHas('provinces', function (Builder $relationQuery) use ($agent) {
                        $relationQuery->where('provinces.id', $agent->province_id);
                    });
            });
    }

    private function applyScopedAccess(Builder $query, ?Agent $agent): Builder
    {
        if (!$agent) {
            return $query;
        }

        if ($this->isProvincialAgent($agent)) {
            return $this->applyProvinceScope($query, $agent);
        }

        return $this->applyDepartmentScope($query, $agent);
    }

    private function applyDepartmentScope(Builder $query, ?Agent $agent): Builder
    {
        if (!$agent) {
            return $query;
        }

        if (!$agent->departement_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('departement_id', $agent->departement_id);
    }

    private function canAccessActivity(ActivitePlan $activite): bool
    {
        $agent = $this->getScopedAgent();

        if (!$agent) {
            return true;
        }

        if ($this->isProvincialAgent($agent)) {
            return (bool) $agent->province_id
                && $activite->niveau_administratif === 'SEP'
                && $this->activityTargetsProvince($activite, (int) $agent->province_id);
        }

        return (int) $activite->departement_id === (int) $agent->departement_id;
    }

    private function enforceManagedScope(array $validated, ?Agent $agent): array
    {
        if (!$agent) {
            return $validated;
        }

        if ($this->isProvincialAgent($agent)) {
            if (!$agent->province_id) {
                abort(403, 'Aucune province associee a cet agent.');
            }

            if (($validated['niveau_administratif'] ?? null) !== 'SEP') {
                abort(403, 'Acces refuse pour ce niveau administratif.');
            }

            $validated['departement_id'] = null;
            $validated['localite_id'] = null;
            $validated['province_id'] = (int) $agent->province_id;
            $validated['province_ids'] = [(int) $agent->province_id];

            return $validated;
        }

        if (!$agent->departement_id) {
            abort(403, 'Aucun departement associe a cet agent.');
        }

        if (!empty($validated['departement_id']) && (int) $validated['departement_id'] !== (int) $agent->departement_id) {
            abort(403, 'Acces refuse pour ce departement.');
        }

        $validated['departement_id'] = (int) $agent->departement_id;

        return $validated;
    }

    /**
     * Check if user can manage PTA (create, edit, delete).
     */
    private function canManage(): bool
    {
        $user = auth()->user();
        if ($this->scopeService()->hasGlobalAdminAccess($user)) {
            return true;
        }

        $agent = $user->agent;
        if (!$agent) {
            return false;
        }

        $nomFonction = $this->getNomFonctionAgent($agent);
        $organe = $agent->organe ?? '';

        if (str_contains($organe, 'National') && str_contains($nomFonction, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Provincial') && str_contains($nomFonction, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Local') && str_contains($nomFonction, 'assistant technique')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update the status of a PTA activity.
     */
    private function canUpdateStatut(ActivitePlan $activite): bool
    {
        if (!$this->canAccessActivity($activite)) {
            return false;
        }

        $user = auth()->user();
        if ($this->scopeService()->hasGlobalAdminAccess($user)) {
            return true;
        }
        if ($this->canManage()) {
            return true;
        }

        $agent = $user->agent;
        if (!$agent) {
            return false;
        }

        $nomFonction = $this->getNomFonctionAgent($agent);
        $organe = $agent->organe ?? '';

        // SEN level
        if (str_contains($organe, 'National') && $activite->niveau_administratif === 'SEN') {
            if (str_contains($nomFonction, 'directeur') || str_contains($nomFonction, 'chef de département')) {
                if ($agent->departement_id && $activite->departement_id === $agent->departement_id) {
                    return true;
                }
            }
            if (str_contains($nomFonction, 'assistant de département') || str_contains($nomFonction, 'secrétaire de département')) {
                if ($agent->departement_id && $activite->departement_id === $agent->departement_id) {
                    return true;
                }
            }
            if ((str_contains($nomFonction, 'assistant') && str_contains($nomFonction, 'direction'))
                || str_contains($nomFonction, 'secrétaire de direction')) {
                if (!$activite->departement_id) {
                    return true;
                }
            }
        }

        // SEP level
        if (str_contains($organe, 'Provincial') && $activite->niveau_administratif === 'SEP') {
            if ($agent->province_id && $this->activityTargetsProvince($activite, (int) $agent->province_id)) {
                if (str_contains($nomFonction, 'secrétaire exécutif provincial') || str_contains($nomFonction, 'sep')) {
                    return true;
                }
                if (str_contains($nomFonction, 'planification')) {
                    return true;
                }
            }
        }

        // SEL level
        if (str_contains($organe, 'Local') && $activite->niveau_administratif === 'SEL') {
            if (str_contains($nomFonction, 'assistant technique')) {
                return true;
            }
        }

        return false;
    }

    private function getNomFonctionAgent(Agent $agent): string
    {
        if (Schema::hasTable('affectations') && Schema::hasTable('fonctions')) {
            $affectationActive = Affectation::where('agent_id', $agent->id)
                ->where('actif', true)
                ->with('fonction')
                ->first();

            if ($affectationActive && $affectationActive->fonction) {
                return mb_strtolower($affectationActive->fonction->nom);
            }
        }

        return mb_strtolower($agent->fonction ?? '');
    }
    /**
     * Display listing of PTA activities.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $agent = $this->getScopedAgent();
        $annee = $request->input('annee', now()->year);
        $trimestre = $request->input('trimestre');
        $statut = $request->input('statut');

        $query = ActivitePlan::with('createur', 'departement', 'province', 'localite')
            ->with('provinces')
            ->parAnnee($annee);

        $this->applyScopedAccess($query, $agent);

        if ($trimestre) {
            $query->parTrimestre($trimestre);
        }

        if ($statut) {
            $query->where('statut', $statut);
        }

        $activites = $query->orderByRaw("FIELD(trimestre, 'T1','T2','T3','T4')")->latest()->get();

        $totalCount = $activites->count();
        $planifieeCount = $activites->where('statut', 'planifiee')->count();
        $enCoursCount = $activites->where('statut', 'en_cours')->count();
        $termineeCount = $activites->where('statut', 'terminee')->count();
        $avgPourcentage = $totalCount > 0 ? round($activites->avg('pourcentage')) : 0;

        $resources = ActivitePlanResource::collection($activites)->resolve();
        $activitesGroupees = collect($resources)->groupBy(fn($a) => $a['trimestre'] ?? 'Annuel')->toArray();

        return $this->success($resources, [
            'stats' => [
                'total' => $totalCount,
                'planifiee' => $planifieeCount,
                'en_cours' => $enCoursCount,
                'terminee' => $termineeCount,
                'avg_pourcentage' => $avgPourcentage,
            ],
            'filters' => [
                'annee' => (int) $annee,
                'trimestre' => $trimestre,
                'statut' => $statut,
            ],
            'canEdit' => $this->canManage(),
        ], [
            'groupees' => $activitesGroupees,
            'stats' => [
                'total' => $totalCount,
                'planifiee' => $planifieeCount,
                'en_cours' => $enCoursCount,
                'terminee' => $termineeCount,
                'avg_pourcentage' => $avgPourcentage,
            ],
            'filters' => [
                'annee' => (int) $annee,
                'trimestre' => $trimestre,
                'statut' => $statut,
            ],
            'canEdit' => $this->canManage(),
        ]);
    }

    /**
     * Return data for the create form.
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $this->getScopedAgent();

        $departments = Department::query()
            ->when($agent?->departement_id, fn (Builder $query) => $query->where('id', $agent->departement_id))
            ->when(!$agent?->departement_id, fn (Builder $query) => $query->operational())
            ->orderBy('nom')
            ->get(['id', 'nom']);
        $provinces = Province::query()
            ->when($this->isProvincialAgent($agent) && $agent?->province_id, fn (Builder $query) => $query->where('id', $agent->province_id))
            ->orderBy('nom')
            ->get(['id', 'nom']);
        $localites = class_exists(Localite::class) ? Localite::orderBy('nom')->get(['id', 'nom']) : collect();

        $payload = [
            'departments' => $departments,
            'provinces' => $provinces,
            'localites' => $localites,
            'categories' => $this->ptaCategories(),
            'responsables' => $this->ptaResponsables(),
            'annee' => now()->year,
            'validation_options' => [
                ['value' => 'direction', 'label' => 'Direction'],
                ['value' => 'coordination_nationale', 'label' => 'Coordination nationale'],
                ['value' => 'coordination_provinciale', 'label' => 'Coordination provinciale'],
            ],
        ];

        return $this->success($payload, [], $payload);
    }

    /**
     * Store a new PTA activity.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $this->getScopedAgent();

        $validated = $request->validate([
            'titre'                => 'required|string|max:1000',
            'categorie'            => 'nullable|string|max:120',
            'objectif'             => 'nullable|string',
            'description'          => 'nullable|string',
            'resultat_attendu'     => 'nullable|string',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'validation_niveau'    => 'nullable|in:direction,coordination_nationale,coordination_provinciale',
            'responsable_code'     => 'nullable|string|max:30',
            'cout_cdf'             => 'nullable|numeric|min:0',
            'departement_id'       => 'nullable|exists:departments,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'province_ids'         => 'nullable|array',
            'province_ids.*'       => 'integer|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'annee'                => 'required|integer|min:2020|max:2040',
            'trimestre'            => 'nullable|in:T1,T2,T3,T4',
            'trimestre_1'          => 'nullable|boolean',
            'trimestre_2'          => 'nullable|boolean',
            'trimestre_3'          => 'nullable|boolean',
            'trimestre_4'          => 'nullable|boolean',
            'statut'               => 'required|in:planifiee,en_cours,terminee',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date',
            'pourcentage'          => 'integer|min:0|max:100',
            'observations'         => 'nullable|string',
        ]);

        $validated = $this->normalizeTrimestreFlags($validated);
        $validated = $this->enforceManagedScope($validated, $agent);
        $provinceIds = $this->normalizeProvinceIds($validated);
        $validated['province_id'] = $provinceIds[0] ?? ($validated['province_id'] ?? null);

        $validated['createur_id'] = auth()->user()->agent->id;

        $activite = ActivitePlan::create($validated);
        $this->syncActiviteProvinces($activite, $provinceIds);

        NotificationService::notifierTous(
            'plan_travail',
            'Nouvelle activite PTA : ' . $activite->titre,
            'Une nouvelle activite a ete ajoutee au Plan de Travail Annuel (' . $activite->annee . ' ' . ($activite->trimestre ?? '') . ').',
            '/plan-travail/' . $activite->id,
            auth()->id()
        );

        $resource = ActivitePlanResource::make($activite->load('createur', 'departement', 'province', 'provinces', 'localite'));

        return $this->resource($resource, [], [
            'message' => 'Activite creee avec succes.',
        ], 201);
    }

    /**
     * Display a single PTA activity.
     */
    public function show(ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canAccessActivity($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $activitePlan->load(['createur', 'departement', 'province', 'provinces', 'localite', 'taches.agent']);

        return $this->resource(ActivitePlanResource::make($activitePlan), [
            'canEdit' => $this->canManage(),
            'canUpdateStatut' => $this->canUpdateStatut($activitePlan),
        ], [
            'canEdit' => $this->canManage(),
            'canUpdateStatut' => $this->canUpdateStatut($activitePlan),
        ]);
    }

    /**
     * Update a PTA activity.
     */
    public function update(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!$this->canAccessActivity($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $this->getScopedAgent();

        $validated = $request->validate([
            'titre'                => 'required|string|max:1000',
            'categorie'            => 'nullable|string|max:120',
            'objectif'             => 'nullable|string',
            'description'          => 'nullable|string',
            'resultat_attendu'     => 'nullable|string',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'validation_niveau'    => 'nullable|in:direction,coordination_nationale,coordination_provinciale',
            'responsable_code'     => 'nullable|string|max:30',
            'cout_cdf'             => 'nullable|numeric|min:0',
            'departement_id'       => 'nullable|exists:departments,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'province_ids'         => 'nullable|array',
            'province_ids.*'       => 'integer|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'annee'                => 'required|integer|min:2020|max:2040',
            'trimestre'            => 'nullable|in:T1,T2,T3,T4',
            'trimestre_1'          => 'nullable|boolean',
            'trimestre_2'          => 'nullable|boolean',
            'trimestre_3'          => 'nullable|boolean',
            'trimestre_4'          => 'nullable|boolean',
            'statut'               => 'required|in:planifiee,en_cours,terminee',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date',
            'pourcentage'          => 'integer|min:0|max:100',
            'observations'         => 'nullable|string',
        ]);

        $validated = $this->normalizeTrimestreFlags($validated);
        $validated = $this->enforceManagedScope($validated, $agent);
        $provinceIds = $this->normalizeProvinceIds($validated);
        $validated['province_id'] = $provinceIds[0] ?? ($validated['province_id'] ?? null);

        $activitePlan->update($validated);
        $this->syncActiviteProvinces($activitePlan, $provinceIds);

        NotificationService::notifierTous(
            'plan_travail',
            'PTA mis a jour : ' . $activitePlan->titre,
            'L\'activite "' . $activitePlan->titre . '" a ete mise a jour (' . $activitePlan->statut . ', ' . $activitePlan->pourcentage . '%).',
            '/plan-travail/' . $activitePlan->id,
            auth()->id()
        );

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'provinces', 'localite'));

        return $this->resource($resource, [], [
            'message' => 'Activite mise a jour.',
        ]);
    }

    /**
     * Remove a PTA activity.
     */
    public function destroy(ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!$this->canAccessActivity($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $activitePlan->delete();

        return $this->success(null, [], [
            'message' => 'Activite supprimee.',
        ]);
    }

    /**
     * Quick status update for a PTA activity.
     */
    public function updateStatut(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canUpdateStatut($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'statut'       => 'required|in:planifiee,en_cours,terminee',
            'pourcentage'  => 'integer|min:0|max:100',
            'observations' => 'nullable|string',
        ]);

        $activitePlan->update($validated);

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'provinces', 'localite', 'taches.agent'));

        return $this->resource($resource, [], [
            'message' => 'Statut mis a jour.',
        ]);
    }

    public function importParsed(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$this->scopeService()->hasGlobalAdminAccess($user)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'createur_id' => 'nullable|integer|exists:agents,id',
            'records' => 'required|array|min:1',
            'records.*.titre' => 'required|string|max:1000',
            'records.*.categorie' => 'nullable|string|max:120',
            'records.*.resultat_attendu' => 'nullable|string',
            'records.*.niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'records.*.validation_niveau' => 'nullable|in:direction,coordination_nationale,coordination_provinciale',
            'records.*.responsable_code' => 'nullable|string|max:30',
            'records.*.cout_cdf' => 'nullable|numeric|min:0',
            'records.*.province_ids' => 'nullable|array',
            'records.*.province_ids.*' => 'integer|exists:provinces,id',
            'records.*.province_names' => 'nullable|array',
            'records.*.province_names.*' => 'string|max:255',
            'records.*.annee' => 'required|integer|min:2020|max:2040',
            'records.*.trimestre' => 'nullable|in:T1,T2,T3,T4',
            'records.*.trimestre_1' => 'nullable|boolean',
            'records.*.trimestre_2' => 'nullable|boolean',
            'records.*.trimestre_3' => 'nullable|boolean',
            'records.*.trimestre_4' => 'nullable|boolean',
        ]);

        $created = 0;
        $updated = 0;
        $createurId = (int) ($validated['createur_id'] ?? $user->agent?->id ?? 0);

        if ($createurId <= 0) {
            return response()->json(['message' => 'createur_id est obligatoire pour cet utilisateur.'], 422);
        }

        \DB::transaction(function () use ($validated, $createurId, &$created, &$updated) {
            foreach ($validated['records'] as $record) {
                $match = [
                    'annee' => $record['annee'],
                    'niveau_administratif' => $record['niveau_administratif'],
                    'titre' => $record['titre'],
                    'categorie' => $record['categorie'] ?? null,
                ];

                $activite = ActivitePlan::query()->firstOrNew($match);
                $isNew = !$activite->exists;
                $provinceIds = $this->resolveImportedProvinceIds($record);

                $payload = [
                    'titre' => $record['titre'],
                    'categorie' => $record['categorie'] ?? null,
                    'resultat_attendu' => $record['resultat_attendu'] ?? null,
                    'niveau_administratif' => $record['niveau_administratif'],
                    'validation_niveau' => $record['validation_niveau'] ?? null,
                    'responsable_code' => $record['responsable_code'] ?? null,
                    'cout_cdf' => $record['cout_cdf'] ?? null,
                    'province_id' => $provinceIds[0] ?? null,
                    'annee' => $record['annee'],
                    'trimestre' => $record['trimestre'] ?? null,
                    'trimestre_1' => (bool) ($record['trimestre_1'] ?? false),
                    'trimestre_2' => (bool) ($record['trimestre_2'] ?? false),
                    'trimestre_3' => (bool) ($record['trimestre_3'] ?? false),
                    'trimestre_4' => (bool) ($record['trimestre_4'] ?? false),
                ];

                if ($isNew) {
                    $payload['createur_id'] = $createurId;
                    $payload['statut'] = 'planifiee';
                    $payload['pourcentage'] = 0;
                }

                $activite->fill($payload);
                $activite->save();
                $activite->provinces()->sync($provinceIds);

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        return $this->success([
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated,
        ], [], [
            'message' => sprintf('Import PTA termine : %d creee(s), %d mise(s) a jour.', $created, $updated),
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated,
        ]);
    }

    private function normalizeProvinceIds(array &$validated): array
    {
        $provinceIds = collect($validated['province_ids'] ?? []);

        if (!empty($validated['province_id'])) {
            $provinceIds->prepend((int) $validated['province_id']);
        }

        unset($validated['province_ids']);

        return $provinceIds
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTrimestreFlags(array $validated): array
    {
        $mapping = [
            'T1' => 'trimestre_1',
            'T2' => 'trimestre_2',
            'T3' => 'trimestre_3',
            'T4' => 'trimestre_4',
        ];

        foreach (['trimestre_1', 'trimestre_2', 'trimestre_3', 'trimestre_4'] as $field) {
            $validated[$field] = (bool) ($validated[$field] ?? false);
        }

        if (!empty($validated['trimestre'])) {
            $validated[$mapping[$validated['trimestre']]] = true;
            return $validated;
        }

        foreach ($mapping as $trimestre => $field) {
            if ($validated[$field]) {
                $validated['trimestre'] = $trimestre;
                break;
            }
        }

        return $validated;
    }

    private function syncActiviteProvinces(ActivitePlan $activite, array $provinceIds): void
    {
        $activite->provinces()->sync($provinceIds);
    }

    private function resolveImportedProvinceIds(array $record): array
    {
        $provinceIds = collect($record['province_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id);

        if ($provinceIds->isNotEmpty()) {
            return $provinceIds->unique()->values()->all();
        }

        $provinceNames = $record['province_names'] ?? [];
        if ($provinceNames === []) {
            return [];
        }

        $catalog = Province::query()->get(['id', 'nom']);
        $joined = $this->normalizeProvinceImportKey(implode(' ', $provinceNames));
        $ids = [];

        foreach ($catalog as $province) {
            $key = $this->normalizeProvinceImportKey($province->nom ?? '');
            if ($key !== '' && str_contains($joined, $key)) {
                $ids[] = (int) $province->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizeProvinceImportKey(string $value): string
    {
        $value = mb_strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]/u', '', $value) ?? $value;

        return str_replace([
            'KASAIORIENT',
            'SUDUBANGI',
            'BASUELE',
            'HAUTUELE',
        ], [
            'KASAIORIENTAL',
            'SUDUBANGI',
            'BASUELE',
            'HAUTUELE',
        ], $value);
    }

    private function activityTargetsProvince(ActivitePlan $activite, int $provinceId): bool
    {
        if ((int) $activite->province_id === $provinceId) {
            return true;
        }

        if (!$activite->relationLoaded('provinces')) {
            $activite->load('provinces:id');
        }

        return $activite->provinces->contains('id', $provinceId);
    }

    private function ptaCategories(): array
    {
        return [
            'Leadership',
            'Planification & Suivi Evaluation',
            'Renforcement des capacités et Recherche',
            'Coordination des secteurs et multisectorialité',
            'Partenariat et Coopération Régionale, Bi et Multilatérale',
            'Administration et Finances',
            'Informations stratégiques (Suivi Evaluation)',
            'Planification et Renforcement des capacités',
            'Coordination des secteurs et partenariat',
            'Administration logistique et finances',
        ];
    }

    private function ptaResponsables(): array
    {
        return [
            'SEN',
            'DPSE',
            'DRRC',
            'DCS',
            'DPCMB',
            'DAF',
            'SEP',
            'SEL',
            'Tous les départements',
        ];
    }
}
