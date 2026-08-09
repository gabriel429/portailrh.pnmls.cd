<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\HolidayPlanning;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Agent;
use App\Models\NotificationPortail;
use App\Models\Localite;
use App\Models\Province;
use App\Services\HolidayEntitlementService;
use App\Services\HolidayPlanningWorkflowService;
use App\Services\HolidayPlanningRequirementService;
use App\Services\NotificationService;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class HolidayPlanningController extends Controller
{
    private function applyStructureFilter($query, ?string $structureType, $structureId)
    {
        if (!$structureType) {
            return $query;
        }

        return $structureId
            ? $query->forStructure($structureType, (int) $structureId)
            : $query->where('type_structure', $structureType);
    }

    private function applyHolidayStructureFilter($query, ?string $structureType, $structureId)
    {
        if (!$structureType) {
            return $query;
        }

        return $query->whereHas('holidayPlanning', function ($planningQuery) use ($structureType, $structureId) {
            $this->applyStructureFilter($planningQuery, $structureType, $structureId);
        });
    }

    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    private function entitlementService(): HolidayEntitlementService
    {
        return app(HolidayEntitlementService::class);
    }

    private function workflow(): HolidayPlanningWorkflowService
    {
        return app(HolidayPlanningWorkflowService::class);
    }

    /**
     * Apply provincial scoping on a HolidayPlanning query.
     * RH Provincial can only see plannings for structures in their province.
     */
    private function applyProvinceScope($query, ?int $provinceId)
    {
        if (!$provinceId) {
            return $query;
        }

        $deptIds = Department::where('province_id', $provinceId)->pluck('id');

        return $query->where(function ($q) use ($provinceId, $deptIds) {
            // SEP plannings for their province
            $q->where(function ($q2) use ($provinceId) {
                $q2->where('type_structure', 'sep')
                   ->where('structure_id', $provinceId);
            })
            // Department plannings within their province
            ->orWhere(function ($q2) use ($deptIds) {
                $q2->where('type_structure', 'department')
                   ->whereIn('structure_id', $deptIds);
            })
            // Local structures within their province departments
            ->orWhere(function ($q2) use ($deptIds) {
                $q2->where('type_structure', 'local')
                   ->whereIn('structure_id', $deptIds);
            });
        });
    }

    /**
     * Liste des plannings de congés avec filtres
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $structureType = $request->get('structure_type');
        $structureId = $request->get('structure_id');

        $scope = $this->scopeService();
        $user = $request->user();
        if (!$this->workflow()->canAccessModule($user)) {
            return response()->json(['message' => 'Vous n’avez pas accès à la gestion des plannings de congés.'], 403);
        }
        $isProvincial = $scope->isProvincialUser($user);
        $provinceId = $isProvincial ? $scope->provinceId($user) : null;

        $query = HolidayPlanning::with(['createdBy', 'validatedBy'])
            ->forYear($year);
        $scope->applyHolidayPlanningScope($query, $user);

        $this->applyStructureFilter($query, $structureType, $structureId);

        $plannings = $query->orderBy('nom_structure')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        $plannings->getCollection()->each(fn (HolidayPlanning $planning) => $planning
            ->setAttribute('capabilities', $this->workflow()->capabilities($user, $planning)));

        // Statistiques globales pour l'année (scoped)
        $statsQuery = HolidayPlanning::forYear($year);
        $scope->applyHolidayPlanningScope($statsQuery, $user);
        $this->applyStructureFilter($statsQuery, $structureType, $structureId);

        $stats = [
            'total_plannings' => (clone $statsQuery)->count(),
            'plannings_valides' => (clone $statsQuery)->validated()->count(),
            'total_jours_prevus' => (clone $statsQuery)->sum('jours_conge_totaux'),
            'total_jours_utilises' => (clone $statsQuery)->sum('jours_utilises'),
        ];

        // Liste des structures pour les filtres (scoped)
        $departments = Department::query()->fonctionnel();
        if (!$scope->hasGlobalHolidayAccess($user)) {
            $accessibleAgents = Agent::query();
            $scope->applyHolidayAgentScope($accessibleAgents, $user);
            $departments->whereIn('id', (clone $accessibleAgents)
                ->whereNotNull('departement_id')
                ->pluck('departement_id'));
        }
        $departments = $departments->orderBy('nom')->get();

        // Liste des congés individuels (pour le tableau principal)
        $holidaysQuery = Holiday::with(['agent', 'interimPar'])
            ->forYear($year);
        $scope->applyHolidayScope($holidaysQuery, $user);
        $this->applyHolidayStructureFilter($holidaysQuery, $structureType, $structureId);
        $holidays = $holidaysQuery->orderBy('date_debut', 'desc')
            ->paginate(20);

        $responsibility = $this->workflow()->responsibilityFor($user);

        // Liste des agents à intégrer au planning de la structure de l'initiateur.
        $agentsQuery = Agent::select('id', 'nom', 'postnom', 'prenom', 'fonction', 'province_id', 'localite_id', 'departement_id')
            ->where('statut', 'actif')
            ->orderInstitutionally();
        $scope->applyHolidayAgentScope($agentsQuery, $user);
        if ($responsibility && $responsibility['type'] === 'department') {
            $agentsQuery->where('departement_id', $responsibility['structure_id']);
        } elseif ($responsibility && $responsibility['type'] === 'sep') {
            $agentsQuery->where('province_id', $responsibility['structure_id']);
        } elseif ($responsibility && $responsibility['type'] === 'local') {
            $agentsQuery->where('localite_id', $responsibility['structure_id']);
        } elseif ($provinceId) {
            $agentsQuery->where('province_id', $provinceId);
        }
        $agents = $this->entitlementService()->enrichAgents($agentsQuery->get(), (int) $year);

        // Liste des provinces (toutes pour RH National, filtrée pour RH Provincial)
        $provincesQuery = Province::orderBy('nom');
        if (!$scope->hasGlobalHolidayAccess($user)) {
            $accessibleAgents = Agent::query();
            $scope->applyHolidayAgentScope($accessibleAgents, $user);
            $provincesQuery->whereIn('id', (clone $accessibleAgents)
                ->whereNotNull('province_id')
                ->pluck('province_id'));
        }
        $provinces = $provincesQuery->get(['id', 'nom']);

        $localitiesQuery = Localite::with('province:id,nom')->orderBy('nom');
        if (!$scope->hasGlobalHolidayAccess($user)) {
            $accessibleAgents = Agent::query();
            $scope->applyHolidayAgentScope($accessibleAgents, $user);
            $localitiesQuery->whereIn('id', (clone $accessibleAgents)
                ->whereNotNull('localite_id')
                ->pluck('localite_id'));
        }
        $localities = $localitiesQuery->get(['id', 'code', 'nom', 'province_id']);

        return response()->json([
            'plannings' => $plannings,
            'holidays' => $holidays,
            'stats' => $stats,
            'departments' => $departments,
            'agents' => $agents,
            'provinces' => $provinces,
            'localities' => $localities,
            'year' => $year,
            'scope' => [
                'is_provincial' => $isProvincial,
                'province_id' => $provinceId,
                'province_nom' => $provinceId ? Province::find($provinceId)?->nom : null,
            ],
            'workflow' => [
                'can_create' => $user->isSuperAdmin() || ($responsibility
                    && $this->workflow()->canInitiate(
                        $user,
                        $responsibility['level'],
                        $responsibility['structure_id'],
                    )),
                'user_role' => $user?->role?->nom_role,
                'responsibility' => $responsibility,
            ],
        ]);
    }

    /**
     * Affichage détaillé d'un planning
     */
    public function show(Request $request, HolidayPlanning $holidayPlanning)
    {
        $accessiblePlanning = HolidayPlanning::whereKey($holidayPlanning->id);
        $this->scopeService()->applyHolidayPlanningScope($accessiblePlanning, $request->user());
        if (!$accessiblePlanning->exists()) {
            return response()->json(['message' => 'Ce planning est hors de votre périmètre.'], 403);
        }

        $user = $request->user();
        $scope = $this->scopeService();
        $holidayPlanning->load([
            'createdBy',
            'validatedBy',
            'holidays' => function ($query) use ($scope, $user) {
                $scope->applyHolidayScope($query, $user);
                $query->with(['agent', 'demandePar', 'approuvePar'])
                      ->orderBy('date_debut');
            }
        ]);

        $holidaysQuery = $holidayPlanning->holidays();
        $scope->applyHolidayScope($holidaysQuery, $user);

        // Statistiques du planning
        $stats = [
            'conges_approuves' => (clone $holidaysQuery)
                ->where('statut_demande', 'approuve')->count(),
            'conges_en_attente' => (clone $holidaysQuery)
                ->where('statut_demande', 'en_attente')->count(),
            'agents_concernes' => (clone $holidaysQuery)
                ->distinct('agent_id')->count(),
            'taux_utilisation' => $holidayPlanning->pourcentage_utilisation
        ];

        return response()->json([
            'planning' => $holidayPlanning->setAttribute(
                'capabilities',
                $this->workflow()->capabilities($request->user(), $holidayPlanning),
            ),
            'stats' => $stats
        ]);
    }

    /**
     * Vue calendrier du planning
     */
    public function calendar(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');
        $structureType = $request->get('structure_type');
        $structureId = $request->get('structure_id');

        $scope = $this->scopeService();
        $user = $request->user();
        $isProvincial = $scope->isProvincialUser($user);
        $provinceId = $isProvincial ? $scope->provinceId($user) : null;

        $start = Carbon::create($year, $month ?: 1, 1)->startOfMonth();
        $end = $month
            ? $start->copy()->endOfMonth()
            : Carbon::create($year, 12, 31)->endOfYear();

        $query = Holiday::with(['agent', 'holidayPlanning'])
            ->whereIn('statut_demande', ['approuve', 'en_attente'])
            ->between($start, $end);
        $scope->applyHolidayScope($query, $user);

        $this->applyHolidayStructureFilter($query, $structureType, $structureId);

        $holidays = $query->get();

        // Formatage pour le calendrier
        $events = $holidays->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'title' => $holiday->agent->nom_complet,
                'start' => $holiday->date_debut->toDateString(),
                'end' => $holiday->date_fin->toDateString(),
                'color' => $holiday->type_conge === 'maladie' ? '#dc3545' :
                          ($holiday->type_conge === 'urgence' ? '#ffc107' : '#007bff'),
                'agent' => $holiday->agent->nom_complet,
                'agent_id' => $holiday->agent_id,
                'type_conge' => $holiday->type_conge,
                'type_label' => $holiday->getTypeCongeLabel(),
                'statut_demande' => $holiday->statut_demande,
                'statut_label' => $holiday->getStatutDemandeLabel(),
                'jours' => $holiday->nombre_jours,
                'structure' => $holiday->holidayPlanning->nom_structure ?? '',
                'extendedProps' => [
                    'agent' => $holiday->agent->nom_complet,
                    'type' => $holiday->type_conge,
                    'type_label' => $holiday->getTypeCongeLabel(),
                    'statut' => $holiday->statut_demande,
                    'statut_label' => $holiday->getStatutDemandeLabel(),
                    'jours' => $holiday->nombre_jours,
                    'structure' => $holiday->holidayPlanning->nom_structure ?? ''
                ]
            ];
        });

        return response()->json([
            'events' => $events,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString()
            ]
        ]);
    }

    /**
     * Création d'un nouveau planning
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee' => 'required|integer|min:2020|max:2030',
            'type_structure' => 'required|in:department,sep,local',
            'structure_id' => 'required|integer|min:1|max:99999',
            'nom_structure' => 'required|string|max:255',
            'jours_conge_totaux' => 'required|integer|min:1|max:50',
            'periods_fermeture' => 'nullable|array',
            'periods_fermeture.*.start' => 'required_with:periods_fermeture|date',
            'periods_fermeture.*.end' => 'required_with:periods_fermeture|date|after_or_equal:periods_fermeture.*.start',
            'notes' => 'nullable|string|max:1000',
            'entries' => 'required|array|min:1',
            'entries.*.agent_id' => 'required|integer|distinct|exists:agents,id',
            'entries.*.date_debut' => 'required|date',
            'entries.*.date_fin' => 'required|date|after_or_equal:entries.*.date_debut',
            'entries.*.observation' => 'nullable|string|max:1000',
        ]);

        $validated['niveau_administratif'] = $this->workflow()->levelFor($validated['type_structure']);

        if (!$this->workflow()->canInitiate($request->user(), $validated['niveau_administratif'], (int) $validated['structure_id'])) {
            return response()->json([
                'message' => "Votre fonction ne permet pas d'initier ce planning.",
            ], 403);
        }

        // Valider que structure_id correspond à une vraie entité
        $type = $validated['type_structure'];
        $sid  = $validated['structure_id'];
        if ($type === 'sep') {
            if (!\App\Models\Province::where('id', $sid)->exists()) {
                return response()->json(['message' => 'Province introuvable (structure_id invalide).'], 422);
            }
        } elseif ($type === 'local') {
            if (!Localite::where('id', $sid)->exists()) {
                return response()->json(['message' => 'Structure locale introuvable (structure_id invalide).'], 422);
            }
        } elseif ($type === 'department') {
            if (!\App\Models\Department::where('id', $sid)->exists()) {
                return response()->json(['message' => 'Département introuvable (structure_id invalide).'], 422);
            }
        }

        // Vérifier qu'un planning n'existe pas déjà pour cette structure/année
        $exists = HolidayPlanning::forYear($validated['annee'])
            ->forStructure($validated['type_structure'], $validated['structure_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Un planning existe déjà pour cette structure en ' . $validated['annee']
            ], 422);
        }

        $validated['created_by'] = auth()->user()->agent->id;
        $validated['statut'] = HolidayPlanning::STATUT_BROUILLON;
        $validated['valide'] = false;

        $entries = $validated['entries'];
        unset($validated['entries']);

        $expectedAgentIds = $this->agentsForStructure(
            $validated['type_structure'],
            (int) $validated['structure_id'],
        )->pluck('id')->sort()->values();
        $submittedAgentIds = collect($entries)->pluck('agent_id')->map(fn ($id) => (int) $id)->sort()->values();
        $missingAgentIds = $expectedAgentIds->diff($submittedAgentIds);
        if ($missingAgentIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'entries' => 'Une période de congé doit être renseignée pour chaque agent actif de la structure.',
            ]);
        }

        foreach ($entries as $index => $entry) {
            $agent = Agent::findOrFail($entry['agent_id']);
            if ($agent->statut !== 'actif' || !$this->agentBelongsToStructure($agent, $validated['type_structure'], (int) $validated['structure_id'])) {
                throw ValidationException::withMessages([
                    "entries.{$index}.agent_id" => 'Cet agent n’appartient pas à la structure du planning.',
                ]);
            }

            $start = Carbon::parse($entry['date_debut']);
            $end = Carbon::parse($entry['date_fin']);
            if ($start->year !== (int) $validated['annee'] || $end->year !== (int) $validated['annee']) {
                throw ValidationException::withMessages([
                    "entries.{$index}.date_debut" => 'Les dates doivent appartenir à l’année du planning.',
                ]);
            }

            $workingDays = Holiday::calculateWorkingDays($start, $end);
            if ($workingDays < 1 || $workingDays > (int) $validated['jours_conge_totaux']) {
                throw ValidationException::withMessages([
                    "entries.{$index}.date_fin" => 'La période doit contenir entre 1 et ' . $validated['jours_conge_totaux'] . ' jours ouvrables.',
                ]);
            }

            if (Holiday::hasConflict($agent->id, $start, $end)) {
                throw ValidationException::withMessages([
                    "entries.{$index}.date_debut" => 'Cet agent possède déjà un congé approuvé sur cette période.',
                ]);
            }
        }

        $planning = DB::transaction(function () use ($validated, $entries) {
            $planning = HolidayPlanning::create($validated);

            foreach ($entries as $entry) {
                $start = Carbon::parse($entry['date_debut']);
                $end = Carbon::parse($entry['date_fin']);

                Holiday::create([
                    'agent_id' => $entry['agent_id'],
                    'holiday_planning_id' => $planning->id,
                    'date_debut' => $start,
                    'date_fin' => $end,
                    'nombre_jours' => Holiday::calculateWorkingDays($start, $end),
                    'date_retour_prevu' => Holiday::nextWorkingDayAfter($end),
                    'type_conge' => 'annuel',
                    'motif' => $entry['observation'] ?? 'Congé annuel planifié',
                    'observation' => $entry['observation'] ?? null,
                    'demande_par' => auth()->user()->agent->id,
                    'statut_demande' => 'en_attente',
                ]);
            }

            return $planning;
        });

        return response()->json([
            'message' => 'Planning créé avec succès',
            'planning' => $planning->load(['createdBy', 'holidays.agent'])
        ], 201);
    }

    private function agentBelongsToStructure(Agent $agent, string $type, int $structureId): bool
    {
        return match ($type) {
            'department' => (int) $agent->departement_id === $structureId,
            'sep' => (int) $agent->province_id === $structureId,
            'local' => (int) $agent->localite_id === $structureId,
            default => false,
        };
    }

    private function agentsForStructure(string $type, int $structureId)
    {
        $query = Agent::query()->where('statut', 'actif');

        return match ($type) {
            'department' => $query->where('departement_id', $structureId),
            'sep' => $query->where('province_id', $structureId),
            'local' => $query->where('localite_id', $structureId),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function notifyMissing(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'structure_type' => 'required|in:department,sep,local',
            'structure_id' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $type = $validated['structure_type'];
        $structureId = (int) $validated['structure_id'];
        $scope = $this->scopeService();
        $agent = $user->agent;
        $withinScope = $scope->hasGlobalHolidayAccess($user)
            || ($type === 'department' && (int) $agent?->departement_id === $structureId)
            || ($type === 'sep' && (int) $agent?->province_id === $structureId)
            || ($type === 'local' && (int) $agent?->localite_id === $structureId);

        if (!$withinScope) {
            return response()->json(['message' => 'Cette structure est hors de votre périmètre.'], 403);
        }

        if (HolidayPlanning::forYear($validated['year'])->forStructure($type, $structureId)->exists()) {
            return response()->json(['message' => 'Le planning est déjà disponible pour cette structure.'], 422);
        }

        $planning = new HolidayPlanning([
            'annee' => $validated['year'],
            'type_structure' => $type,
            'structure_id' => $structureId,
            'niveau_administratif' => $this->workflow()->levelFor($type),
        ]);
        $recipients = User::with(['agent.departement', 'agent.province', 'role'])
            ->whereHas('agent', fn ($query) => $query->where('statut', 'actif'))
            ->get()
            ->filter(function (User $candidate) use ($planning, $type, $structureId) {
                $responsibility = $this->workflow()->responsibilityFor($candidate);
                $isInitiator = $responsibility
                    && $responsibility['type'] === $type
                    && (int) $responsibility['structure_id'] === $structureId;

                return $isInitiator || $this->workflow()->canValidate($candidate, $planning);
            });

        if ($recipients->isEmpty()) {
            return response()->json(['message' => 'Aucun responsable actif n’a été trouvé pour cette structure.'], 422);
        }

        $structureName = match ($type) {
            'department' => Department::find($structureId)?->nom,
            'local' => Localite::find($structureId)?->nom,
            default => Province::find($structureId)?->nom,
        };
        $structureName ??= "la structure {$structureId}";
        $contextKey = "holiday-planning-reminder:{$validated['year']}:{$type}:{$structureId}";

        $recipients->each(function (User $recipient) use ($validated, $type, $structureName, $contextKey, $user) {
            NotificationPortail::updateOrCreate(
                [
                    'user_id' => $recipient->id,
                    'type' => 'holiday_planning_required_actor',
                    'context_key' => "{$contextKey}:{$recipient->id}",
                ],
                [
                    'titre' => "Rappel : planning annuel {$validated['year']} à préparer",
                    'message' => "Le planning annuel de congés {$validated['year']} de {$structureName} n’est pas encore disponible. Merci de l’élaborer ou de le faire valider.",
                    'icone' => 'fa-calendar-exclamation',
                    'couleur' => '#dc2626',
                    'lien' => '/rh/holidays/planning',
                    'emetteur_id' => $user->id,
                    'lu' => false,
                ],
            );
        });

        return response()->json([
            'message' => 'Le rappel a été envoyé aux responsables de cette structure.',
            'recipients_count' => $recipients->count(),
        ]);
    }

    /**
     * Mise à jour d'un planning
     */
    public function update(Request $request, HolidayPlanning $holidayPlanning)
    {
        if (!$this->canAccessPlanning($request->user(), $holidayPlanning)) {
            return response()->json(['message' => 'Ce planning est hors de votre périmètre.'], 403);
        }

        if (!$this->workflow()->canEdit($request->user(), $holidayPlanning)) {
            return response()->json([
                'message' => 'Seul l’initiateur compétent peut modifier un planning encore au brouillon.'
            ], 403);
        }

        $validated = $request->validate([
            'jours_conge_totaux' => 'sometimes|integer|min:1|max:50',
            'periods_fermeture' => 'nullable|array',
            'periods_fermeture.*.start' => 'required_with:periods_fermeture|date',
            'periods_fermeture.*.end' => 'required_with:periods_fermeture|date|after_or_equal:periods_fermeture.*.start',
            'notes' => 'nullable|string|max:1000'
        ]);

        $holidayPlanning->update($validated);

        return response()->json([
            'message' => 'Planning mis à jour avec succès',
            'planning' => $holidayPlanning->fresh()
        ]);
    }

    public function submit(Request $request, HolidayPlanning $holidayPlanning)
    {
        if (!$this->canAccessPlanning($request->user(), $holidayPlanning)) {
            return response()->json(['message' => 'Ce planning est hors de votre périmètre.'], 403);
        }

        if (!$this->workflow()->canSubmit($request->user(), $holidayPlanning)) {
            return response()->json(['message' => 'Ce planning ne peut pas être soumis par cet utilisateur.'], 403);
        }

        $holidayPlanning->submit();

        return response()->json([
            'message' => 'Planning soumis à l’autorité compétente.',
            'planning' => $holidayPlanning->fresh(),
        ]);
    }

    /**
     * Validation d'un planning
     */
    public function validate(Request $request, HolidayPlanning $holidayPlanning)
    {
        if (!$this->canAccessPlanning($request->user(), $holidayPlanning)) {
            return response()->json(['message' => 'Ce planning est hors de votre périmètre.'], 403);
        }

        if (!$this->workflow()->canValidate($request->user(), $holidayPlanning)) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour valider un planning'
            ], 403);
        }

        if ($holidayPlanning->statut !== HolidayPlanning::STATUT_SOUMIS) {
            return response()->json([
                'message' => 'Seul un planning soumis peut être validé.'
            ], 422);
        }

        $holidayPlanning->validate($request->user()->agent);
        app(HolidayPlanningRequirementService::class)->closeForPlanning(
            $holidayPlanning,
            $request->user()->agent->id,
        );
        $this->notifyRh($holidayPlanning, $request->user());

        return response()->json([
            'message' => 'Planning validé avec succès',
            'planning' => $holidayPlanning->fresh(['validatedBy'])
        ]);
    }

    private function notifyRh(HolidayPlanning $planning, User $validator): void
    {
        $query = User::query()->whereHas('role', function ($roleQuery) {
            $roleQuery->whereIn('nom_role', [
                'RH National',
                'RH Provincial',
                'Section ressources humaines',
                'Chef Section RH',
            ]);
        });

        if ($planning->niveau_administratif === 'provincial') {
            $query->whereHas('agent', fn ($agentQuery) => $agentQuery
                ->where('province_id', $planning->structure_id));
        } elseif ($planning->niveau_administratif === 'local') {
            $query->whereHas('agent', fn ($agentQuery) => $agentQuery
                ->where('localite_id', $planning->structure_id));
        }

        NotificationService::envoyerMultiple(
            $query->pluck('id')->all(),
            'holiday_planning_validated',
            'Planning de congés validé',
            "Le planning {$planning->annee} de {$planning->nom_structure} est disponible pour consultation et suivi.",
            '/rh/holidays/planning',
            $validator->id,
            false,
        );
    }

    /**
     * Statistiques détaillées par structure
     */
    public function statistiques(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $structureType = $request->get('structure_type');
        $structureId = $request->get('structure_id');

        $scope = $this->scopeService();
        $user = $request->user();
        if (!$this->workflow()->canAccessModule($user)) {
            return response()->json(['message' => 'Vous n’avez pas accès à la gestion des plannings de congés.'], 403);
        }

        $planningIdsQuery = HolidayPlanning::forYear($year);
        $scope->applyHolidayPlanningScope($planningIdsQuery, $user);
        $this->applyStructureFilter($planningIdsQuery, $structureType, $structureId);
        $planningIds = $planningIdsQuery->pluck('id');
        $accessibleAgentIds = null;
        if (!$scope->hasGlobalHolidayAccess($user)) {
            $accessibleAgents = Agent::query();
            $scope->applyHolidayAgentScope($accessibleAgents, $user);
            $accessibleAgentIds = $accessibleAgents->pluck('id');
        }

        $baseQuery = DB::table('holiday_plannings as hp')
            ->where('hp.annee', $year)
            ->whereIn('hp.id', $planningIds);

        $stats = $baseQuery->select([
                'hp.type_structure',
                'hp.nom_structure',
                'hp.jours_conge_totaux',
                'hp.jours_utilises',
                DB::raw('COUNT(h.id) as total_conges'),
                DB::raw('COUNT(CASE WHEN h.statut_demande = "approuve" THEN 1 END) as conges_approuves'),
                DB::raw('COUNT(CASE WHEN h.statut_demande = "en_attente" THEN 1 END) as conges_en_attente'),
                DB::raw('COUNT(DISTINCT h.agent_id) as agents_concernes'),
                DB::raw('ROUND((hp.jours_utilises * 100.0 / hp.jours_conge_totaux), 1) as taux_utilisation')
            ])
            ->leftJoin('holidays as h', function ($join) use ($accessibleAgentIds) {
                $join->on('hp.id', '=', 'h.holiday_planning_id');
                if ($accessibleAgentIds !== null) {
                    $join->whereIn('h.agent_id', $accessibleAgentIds);
                }
            })
            ->where('hp.annee', $year)
            ->groupBy([
                'hp.id', 'hp.type_structure', 'hp.nom_structure',
                'hp.jours_conge_totaux', 'hp.jours_utilises'
            ])
            ->orderBy('hp.type_structure')
            ->orderBy('hp.nom_structure')
            ->get();

        // Regrouper par type de structure
        $grouped = $stats->groupBy('type_structure')->map(function ($items) {
            return [
                'structures' => $items,
                'totals' => [
                    'jours_totaux' => $items->sum('jours_conge_totaux'),
                    'jours_utilises' => $items->sum('jours_utilises'),
                    'total_conges' => $items->sum('total_conges'),
                    'agents_concernes' => $items->sum('agents_concernes'),
                    'taux_moyen' => $items->avg('taux_utilisation')
                ]
            ];
        });

        $holidayStatsQuery = Holiday::query()
            ->whereYear('date_debut', $year);
        $scope->applyHolidayScope($holidayStatsQuery, $user);
        $this->applyHolidayStructureFilter($holidayStatsQuery, $structureType, $structureId);

        $byType = (clone $holidayStatsQuery)
            ->selectRaw('type_conge, COUNT(*) as total, COALESCE(SUM(nombre_jours), 0) as jours')
            ->groupBy('type_conge')
            ->orderByDesc('jours')
            ->get()
            ->map(fn($item) => [
                'type' => $item->type_conge,
                'label' => Holiday::TYPES_CONGE[$item->type_conge] ?? $item->type_conge,
                'total' => (int) $item->total,
                'jours' => (int) $item->jours,
            ]);

        $byStatus = (clone $holidayStatsQuery)
            ->selectRaw('statut_demande, COUNT(*) as total, COALESCE(SUM(nombre_jours), 0) as jours')
            ->groupBy('statut_demande')
            ->get()
            ->map(fn($item) => [
                'statut' => $item->statut_demande,
                'label' => Holiday::STATUTS_DEMANDE[$item->statut_demande] ?? $item->statut_demande,
                'total' => (int) $item->total,
                'jours' => (int) $item->jours,
            ]);

        $monthly = (clone $holidayStatsQuery)
            ->whereIn('statut_demande', ['approuve', 'en_attente'])
            ->selectRaw('MONTH(date_debut) as month, COUNT(*) as total, COALESCE(SUM(nombre_jours), 0) as jours')
            ->groupBy(DB::raw('MONTH(date_debut)'))
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'month' => (int) $item->month,
                'total' => (int) $item->total,
                'jours' => (int) $item->jours,
            ]);

        $topAgents = (clone $holidayStatsQuery)
            ->where('statut_demande', 'approuve')
            ->with('agent')
            ->selectRaw('agent_id, COUNT(*) as total, COALESCE(SUM(nombre_jours), 0) as jours')
            ->groupBy('agent_id')
            ->orderByDesc('jours')
            ->limit(8)
            ->get()
            ->map(fn($item) => [
                'agent_id' => $item->agent_id,
                'agent' => $item->agent?->nom_complet ?? 'Agent #' . $item->agent_id,
                'total' => (int) $item->total,
                'jours' => (int) $item->jours,
            ]);

        return response()->json([
            'year' => $year,
            'statistiques' => $grouped,
            'global' => [
                'total_structures' => $stats->count(),
                'total_jours_prevus' => $stats->sum('jours_conge_totaux'),
                'total_jours_utilises' => $stats->sum('jours_utilises'),
                'total_conges' => $stats->sum('total_conges'),
                'taux_utilisation_global' => $stats->avg('taux_utilisation')
            ],
            'holidays_summary' => [
                'by_type' => $byType,
                'by_status' => $byStatus,
                'monthly' => $monthly,
                'top_agents' => $topAgents,
            ],
        ]);
    }

    /**
     * Export des données de planning
     */
    public function export(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $format = $request->get('format', 'json'); // json, csv
        $structureType = $request->get('structure_type');
        $structureId = $request->get('structure_id');

        $scope = $this->scopeService();
        $user = $request->user();
        if (!$this->workflow()->canAccessModule($user)) {
            return response()->json(['message' => 'Vous n’avez pas accès à la gestion des plannings de congés.'], 403);
        }

        $query = HolidayPlanning::with([
            'createdBy',
            'validatedBy',
            'holidays' => function ($holidayQuery) use ($scope, $user) {
                $scope->applyHolidayScope($holidayQuery, $user);
                $holidayQuery->with('agent');
            },
        ])->forYear($year);
        $scope->applyHolidayPlanningScope($query, $user);
        $this->applyStructureFilter($query, $structureType, $structureId);

        $plannings = $query->get();

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=planning-conges-{$year}.csv"
            ];

            $callback = function () use ($plannings) {
                $file = fopen('php://output', 'w');

                // En-têtes CSV
                fputcsv($file, [
                    'Structure', 'Type', 'Année', 'Jours Totaux', 'Jours Utilisés',
                    'Taux Utilisation (%)', 'Validé', 'Créé par', 'Date création'
                ]);

                foreach ($plannings as $planning) {
                    fputcsv($file, [
                        $planning->nom_structure,
                        $planning->type_structure_label,
                        $planning->annee,
                        $planning->jours_conge_totaux,
                        $planning->jours_utilises,
                        $planning->pourcentage_utilisation,
                        $planning->valide ? 'Oui' : 'Non',
                        $planning->createdBy->nom_complet ?? '',
                        $planning->created_at->format('d/m/Y H:i')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json([
            'year' => $year,
            'plannings' => $plannings,
            'generated_at' => now()->toISOString()
        ]);
    }

    /**
     * Suppression d'un planning
     */
    public function destroy(Request $request, HolidayPlanning $holidayPlanning)
    {
        if (!$this->canAccessPlanning($request->user(), $holidayPlanning)) {
            return response()->json(['message' => 'Ce planning est hors de votre périmètre.'], 403);
        }

        if ($holidayPlanning->statut !== HolidayPlanning::STATUT_BROUILLON) {
            return response()->json([
                'message' => 'Seul un planning au brouillon peut être supprimé.'
            ], 403);
        }

        if ($holidayPlanning->holidays()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un planning ayant des congés associés'
            ], 422);
        }

        $holidayPlanning->delete();

        return response()->json([
            'message' => 'Planning supprimé avec succès'
        ]);
    }

    private function canAccessPlanning(?User $user, HolidayPlanning $planning): bool
    {
        $query = HolidayPlanning::whereKey($planning->id);
        $this->scopeService()->applyHolidayPlanningScope($query, $user);

        return $query->exists();
    }
}
