<?php

namespace App\Http\Controllers\RH;

use App\Events\CongeApproved;
use App\Events\CongeRequested;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Agent;
use App\Models\AgentHolidayEntitlement;
use App\Models\AgentStatus;
use App\Models\Department;
use App\Services\HolidayEntitlementService;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HolidayController extends Controller
{
    private function entitlementService(): HolidayEntitlementService
    {
        return app(HolidayEntitlementService::class);
    }

    /**
     * Liste des congés avec filtres
     */
    public function index(Request $request)
    {
        $query = Holiday::with([
            'agent.departement',
            'holidayPlanning',
            'demandePar',
            'approuvePar'
        ]);

        // Province scoping for RH Provincial
        $scope = app(UserDataScope::class);
        $user = $request->user();
        if ($scope->isProvincialUser($user)) {
            $provinceId = $scope->provinceId($user);
            if ($provinceId) {
                $query->whereHas('agent', fn($q) => $q->where('province_id', $provinceId));
            }
        }

        // Filtres
        if ($request->filled('agent_id')) {
            $query->byAgent($request->agent_id);
        }

        if ($request->filled('statut')) {
            $query->byStatut($request->statut);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('year')) {
            $query->forYear($request->year);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('agent', function ($q) use ($request) {
                $q->where('departement_id', $request->department_id);
            });
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $start = Carbon::parse($request->date_debut);
            $end = Carbon::parse($request->date_fin);
            $query->between($start, $end);
        }

        $holidays = $query->orderBy('date_debut', 'desc')
            ->paginate(20);

        return response()->json($holidays);
    }

    /**
     * Congés en attente d'approbation
     */
    public function pending()
    {
        $holidays = Holiday::with([
            'agent.departement',
            'holidayPlanning',
            'demandePar'
        ])
            ->pending()
            ->orderBy('created_at')
            ->get();

        return response()->json($holidays);
    }

    /**
     * Détails d'un congé
     */
    public function show(Request $request, Holiday $holiday)
    {
        if (!app(UserDataScope::class)->canAccessAgent($request->user(), $holiday->agent, true)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas consulter ce congé.'
            ], 403);
        }

        $holiday->load([
            'agent.departement',
            'holidayPlanning',
            'demandePar',
            'approuvePar',
            'refusePar',
            'interimPar'
        ]);

        return response()->json($holiday);
    }

    /**
     * Agents par département/structure pour planification en lot
     */
    public function agentsByStructure(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|integer',
        ]);

        $scope = app(UserDataScope::class);
        $user = $request->user();

        $agentsQuery = Agent::where('statut', 'actif')->orderInstitutionally();

        if ($scope->isProvincialUser($user)) {
            // RH Provincial : tous les agents de sa province
            $provinceId = $scope->provinceId($user);
            if ($provinceId) {
                $agentsQuery->where('province_id', $provinceId);
            }
            // Optionnel : filtrer par département si fourni
            if ($request->filled('department_id')) {
                $agentsQuery->where('departement_id', $request->department_id);
            }
        } else {
            // RH National : department_id obligatoire, départements nationaux uniquement
            if (!$request->filled('department_id')) {
                return response()->json(['agents' => [], 'message' => 'Sélectionnez un département']);
            }
            $dept = Department::find($request->department_id);
            if (!$dept || $dept->province_id !== null) {
                return response()->json(['agents' => [], 'message' => 'Le RH National ne peut planifier que pour les structures nationales']);
            }
            $agentsQuery->where('departement_id', $request->department_id)
                ->whereNull('province_id');
        }

        $agents = $agentsQuery->get(['id', 'nom', 'postnom', 'prenom', 'fonction']);

        // Vérifier les congés existants pour l'année en cours
        $year = $request->query('year', date('Y'));
        $existingHolidays = Holiday::whereIn('agent_id', $agents->pluck('id'))
            ->whereYear('date_debut', $year)
            ->whereIn('statut_demande', ['en_attente', 'approuve'])
            ->get()
            ->groupBy('agent_id');

        $result = $agents->map(fn($a) => [
            'id' => $a->id,
            'nom_complet' => trim(($a->nom ?? '') . ' ' . ($a->postnom ?? '') . ' ' . ($a->prenom ?? '')),
            'fonction' => $a->fonction,
            'conge_existant' => $existingHolidays->has($a->id) ? $existingHolidays[$a->id]->map(fn($h) => [
                'date_debut' => $h->date_debut->format('Y-m-d'),
                'date_fin' => $h->date_fin->format('Y-m-d'),
                'type_conge' => $h->type_conge,
                'statut' => $h->statut_demande,
            ])->values() : [],
        ]);

        return response()->json(['agents' => $result]);
    }

    /**
     * Création d'une demande de congé
     */
    public function store(Request $request)
    {
        $isPlanning = $request->boolean('is_planning');

        // L'agent peut soumettre sa propre demande sans passer agent_id
        $requestingAgent = auth()->user()->agent;
        $isRhUser = $requestingAgent && $requestingAgent->hasRole(['Section ressources humaines', 'Chef Section RH', 'RH National', 'RH Provincial']);

        // Si non-RH : forcer agent_id à celui de l'utilisateur connecté
        if (!$isRhUser) {
            $request->merge(['agent_id' => $requestingAgent?->id]);
        }

        $rules = [
            'agent_id' => 'required|exists:agents,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'type_conge' => 'required|in:annuel,maladie,maternite,paternite,urgence,special',
            'motif' => $isPlanning ? 'nullable|string|max:1000' : 'required|string|max:1000',
            'observation' => 'nullable|string|max:1000',
            'interim_assure_par' => 'nullable|exists:agents,id',
            'document_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png|max:2048',
            'holiday_planning_id' => 'nullable|exists:holiday_plannings,id'
        ];

        // Pour les demandes normales (pas planning), date doit être dans le futur
        if (!$isPlanning) {
            $rules['date_debut'] = 'required|date|after_or_equal:today';
        }

        $validated = $request->validate($rules);

        $scope = app(UserDataScope::class);

        // Vérifier les conflits de dates
        $agent = Agent::find($validated['agent_id']);

        if (!$scope->canAccessAgent($request->user(), $agent, true)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas créer une demande de congé pour cet agent.'
            ], 403);
        }

        if (!empty($validated['interim_assure_par'])) {
            $interim = Agent::find($validated['interim_assure_par']);

            if (!$scope->canUseAgentAsInterim($request->user(), $interim, $agent)) {
                return response()->json([
                    'message' => 'L\'intérimaire doit être un agent actif de votre département, de votre province ou de votre périmètre autorisé.',
                    'errors' => [
                        'interim_assure_par' => ['Choisissez un intérimaire autorisé pour cette demande.'],
                    ],
                ], 422);
            }
        }

        $dateDébut = Carbon::parse($validated['date_debut']);
        $dateFin = Carbon::parse($validated['date_fin']);

        if (Holiday::hasConflict($validated['agent_id'], $dateDébut, $dateFin)) {
            return response()->json([
                'message' => 'Conflit de dates : l\'agent a déjà un congé approuvé sur cette période'
            ], 422);
        }

        $nombreJours = Holiday::calculateWorkingDays($dateDébut, $dateFin);
        if ($nombreJours <= 0) {
            return response()->json([
                'message' => 'La période choisie doit contenir au moins un jour ouvrable.'
            ], 422);
        }

        // Résoudre automatiquement le planning si non fourni (pour type annuel)
        if (empty($validated['holiday_planning_id'])) {
            $planning = $this->resolvePlanning($agent, $dateDébut->year);
            if ($planning) {
                $validated['holiday_planning_id'] = $planning->id;
            }
        } else {
            $planning = HolidayPlanning::find($validated['holiday_planning_id']);
        }

        // Vérifier le quota individuel (uniquement pour congé annuel)
        if (($validated['type_conge'] ?? '') === 'annuel') {
            $quota = $this->entitlementService()->quotaForAgent($agent, (int) $dateDébut->year);
            if ($nombreJours > $quota['jours_restants']) {
                $joursDisponibles = max(0, (int) $quota['jours_restants']);
                return response()->json([
                    'message' => "Quota individuel insuffisant : {$joursDisponibles} jour(s) disponible(s) sur {$quota['jours_autorises']} pour cet agent. Demande : {$nombreJours} jour(s)."
                ], 422);
            }
        }

        // Gérer le document médical si fourni
        if ($request->hasFile('document_medical')) {
            $validated['document_medical'] = $request->file('document_medical')
                ->store('documents/medical', 'public');
        }

        // Retirer is_planning avant la création
        unset($validated['is_planning']);
        if (empty($validated['motif'])) {
            $validated['motif'] = 'Congé planifié par RH';
        }

        $validated['demande_par'] = auth()->user()->agent->id;
        $validated['statut_demande'] = 'en_attente';

        // Calculer le nombre de jours ouvrables
        $validated['nombre_jours'] = $nombreJours;
        $validated['date_retour_prevu'] = $dateFin->copy()->addDay();

        $holiday = Holiday::create($validated);

        // Auto-approuver si RH planifie directement ou si urgence/maladie
        $isRh = auth()->user()->agent->hasRole(['RH National', 'RH Provincial']);
        $shouldAutoApprove = $isRh && ($isPlanning || in_array($validated['type_conge'], ['urgence', 'maladie']));

        if ($shouldAutoApprove) {
            $holiday->approve(auth()->user()->agent);
            CongeApproved::dispatch($holiday->fresh()); // C6 — notifier l'agent

            AgentStatus::setNewStatus($validated['agent_id'], [
                'statut' => 'en_conge',
                'date_debut' => $dateDébut,
                'date_fin' => $dateFin,
                'motif' => 'Congé ' . $validated['type_conge'],
                'created_by' => auth()->user()->agent->id,
                'approved_by' => auth()->user()->agent->id,
                'approved_at' => now()
            ]);
        }

        // Fire event for PTA conflict check
        CongeRequested::dispatch($holiday);

        return response()->json([
            'message' => 'Demande de congé créée avec succès',
            'holiday' => $holiday->load(['agent', 'demandePar'])
        ], 201);
    }

    /**
     * Création en lot de congés planifiés (par structure/département)
     */
    public function storeBatch(Request $request)
    {
        $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.agent_id' => 'required|exists:agents,id',
            'entries.*.date_debut' => 'required|date',
            'entries.*.date_fin' => 'required|date|after_or_equal:entries.*.date_debut',
            'entries.*.type_conge' => 'required|in:annuel,maladie,maternite,paternite,urgence,special',
            'entries.*.observation' => 'nullable|string|max:1000',
            'entries.*.interim_assure_par' => 'nullable|exists:agents,id',
            'holiday_planning_id' => 'nullable|exists:holiday_plannings,id',
        ]);

        $currentAgent = auth()->user()->agent;
        $isRh = $currentAgent && $currentAgent->hasRole(['Section ressources humaines', 'Chef Section RH', 'RH National', 'RH Provincial']);
        $scope = app(UserDataScope::class);

        $created = [];
        $errors = [];

        DB::beginTransaction();

        try {
        foreach ($request->entries as $entry) {
            $dateDébut = Carbon::parse($entry['date_debut']);
            $dateFin = Carbon::parse($entry['date_fin']);

            // Vérifier conflit
            $entryAgent = Agent::find($entry['agent_id']);
            $entryNom = trim(($entryAgent->nom ?? '') . ' ' . ($entryAgent->postnom ?? ''));

            if (!$scope->canAccessAgent($request->user(), $entryAgent, false)) {
                $errors[] = "Accès refusé pour {$entryNom} : agent hors de votre périmètre";
                continue;
            }

            if (!empty($entry['interim_assure_par'])) {
                $entryInterim = Agent::find($entry['interim_assure_par']);
                if (!$scope->canUseAgentAsInterim($request->user(), $entryInterim, $entryAgent)) {
                    $errors[] = "Intérimaire non autorisé pour {$entryNom} : choisissez un agent actif du même périmètre";
                    continue;
                }
            }

            if (Holiday::hasConflict($entry['agent_id'], $dateDébut, $dateFin)) {
                $errors[] = "Conflit de dates pour {$entryNom} : congé existant sur cette période";
                continue;
            }

            $entryNbJours = Holiday::calculateWorkingDays($dateDébut, $dateFin);
            if ($entryNbJours <= 0) {
                $errors[] = "Période invalide pour {$entryNom} : choisissez au moins un jour ouvrable";
                continue;
            }

            // Résoudre le planning pour cet agent
            $entryPlanningId = $request->holiday_planning_id ?? null;
            if (!$entryPlanningId && $entryAgent) {
                $entryPlanning = $this->resolvePlanning($entryAgent, $dateDébut->year);
                if ($entryPlanning) {
                    $entryPlanningId = $entryPlanning->id;
                }
            } else {
                $entryPlanning = $entryPlanningId ? HolidayPlanning::find($entryPlanningId) : null;
            }

            // Vérifier le quota individuel (congé annuel uniquement)
            if (($entry['type_conge'] ?? '') === 'annuel') {
                $quota = $this->entitlementService()->quotaForAgent($entryAgent, (int) $dateDébut->year);
                if ($entryNbJours > $quota['jours_restants']) {
                    $joursDisponibles = max(0, (int) $quota['jours_restants']);
                    $errors[] = "Quota individuel insuffisant pour {$entryNom} : {$joursDisponibles} jour(s) disponible(s) sur {$quota['jours_autorises']}, demande {$entryNbJours} jour(s).";
                    continue;
                }
            }

            $holiday = Holiday::create([
                'agent_id' => $entry['agent_id'],
                'date_debut' => $dateDébut,
                'date_fin' => $dateFin,
                'nombre_jours' => $entryNbJours,
                'date_retour_prevu' => $dateFin->copy()->addDay(),
                'type_conge' => $entry['type_conge'],
                'motif' => $entry['observation'] ?? 'Congé planifié par RH',
                'observation' => $entry['observation'] ?? null,
                'interim_assure_par' => $entry['interim_assure_par'] ?? null,
                'holiday_planning_id' => $entryPlanningId,
                'demande_par' => $currentAgent->id,
                'statut_demande' => 'en_attente',
            ]);

            // Auto-approuver si RH
            if ($isRh) {
                $holiday->approve($currentAgent);
            }

            $created[] = $holiday;
        }

        DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création en lot : ' . $e->getMessage(),
                'created_count' => 0,
                'error_count' => count($request->entries),
                'errors' => [$e->getMessage()],
            ], 500);
        }

        $msg = count($created) . ' congé(s) planifié(s) avec succès';
        if (count($errors) > 0) {
            $msg .= '. ' . count($errors) . ' erreur(s) : ' . implode('; ', $errors);
        }

        return response()->json([
            'message' => $msg,
            'created_count' => count($created),
            'error_count' => count($errors),
            'errors' => $errors,
        ], count($created) > 0 ? 201 : 422);
    }

    /**
     * Approbation d'un congé
     */
    public function approve(Holiday $holiday)
    {
        if ($holiday->statut_demande !== 'en_attente') {
            return response()->json([
                'message' => 'Seuls les congés en attente peuvent être approuvés'
            ], 422);
        }

        // Anti self-approve
        $user = auth()->user()->agent;
        if (!$user) {
            return response()->json(['message' => 'Compte non associé à un agent.'], 422);
        }

        if ($holiday->agent_id === $user->id || $holiday->demande_par === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas approuver votre propre demande de congé.'
            ], 403);
        }

        // Vérifications de permission selon la hiérarchie
        $canApprove = false;

        if ($user->hasRole(['RH National', 'SEN'])) {
            $canApprove = true;
        } elseif ($user->hasRole('RH Provincial')) {
            // RH Provincial peut approuver uniquement les congés dans sa province
            $canApprove = $holiday->agent->province_id === $user->province_id;
        }

        if (!$canApprove) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour approuver ce congé'
            ], 403);
        }

        // Re-vérification du quota individuel à l'approbation (C3)
        if ($holiday->type_conge === 'annuel') {
            $quota = $this->entitlementService()->quotaForAgent($holiday->agent, (int) $holiday->date_debut->year);
            if ($holiday->nombre_jours > $quota['jours_restants']) {
                $joursDisponibles = max(0, (int) $quota['jours_restants']);
                return response()->json([
                    'message' => "Quota individuel dépassé : {$joursDisponibles} jour(s) disponible(s) sur {$quota['jours_autorises']}. Congé demandé : {$holiday->nombre_jours} jour(s)."
                ], 422);
            }
        }

        $holiday->approve($user);

        // Fire event for PTA conflict re-check and notifications
        CongeApproved::dispatch($holiday->fresh());

        // Créer le statut agent correspondant
        AgentStatus::setNewStatus($holiday->agent_id, [
            'statut' => 'en_conge',
            'date_debut' => $holiday->date_debut,
            'date_fin' => $holiday->date_fin,
            'motif' => 'Congé ' . $holiday->type_conge,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now()
        ]);

        return response()->json([
            'message' => 'Congé approuvé avec succès',
            'holiday' => $holiday->fresh(['approuvePar'])
        ]);
    }

    /**
     * Refus d'un congé
     */
    public function refuse(Request $request, Holiday $holiday)
    {
        if ($holiday->statut_demande !== 'en_attente') {
            return response()->json([
                'message' => 'Seuls les congés en attente peuvent être refusés'
            ], 422);
        }

        $validated = $request->validate([
            'motif_refus' => 'required|string|max:1000'
        ]);

        // Vérification scope Provincial pour le refus (C5)
        $refuseur = auth()->user()->agent;
        if (!$refuseur) {
            return response()->json(['message' => 'Compte non associé à un agent.'], 422);
        }

        if ($refuseur->hasRole('RH Provincial')) {
            if ($holiday->agent->province_id !== $refuseur->province_id) {
                return response()->json([
                    'message' => 'Permissions insuffisantes pour refuser ce congé.'
                ], 403);
            }
        }

        $holiday->refuse($refuseur, $validated['motif_refus']);

        return response()->json([
            'message' => 'Congé refusé',
            'holiday' => $holiday->fresh(['refusePar'])
        ]);
    }

    /**
     * Annulation d'un congé
     */
    public function cancel(Holiday $holiday)
    {
        $user = auth()->user()->agent;
        $scope = app(UserDataScope::class);

        if (!$user || !$scope->canAccessAgent(request()->user(), $holiday->agent, false)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas annuler ce congé.'
            ], 403);
        }

        // Seul le demandeur ou un RH peut annuler
        if ($holiday->demande_par !== $user->id &&
            !$this->canManageHolidayRequests($user, request()->user())) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour annuler ce congé'
            ], 403);
        }

        $holiday->cancel();

        // Marquer l'agent comme disponible si le congé était en cours
        if ($holiday->is_active) {
            AgentStatus::setNewStatus($holiday->agent_id, [
                'statut' => 'disponible',
                'date_debut' => Carbon::today(),
                'motif' => 'Retour anticipé de congé',
                'created_by' => $user->id
            ]);
        }

        return response()->json([
            'message' => 'Congé annulé avec succès'
        ]);
    }

    /**
     * Marquer le retour d'un agent
     */
    public function markReturned(Request $request, Holiday $holiday)
    {
        $user = auth()->user()->agent;
        $scope = app(UserDataScope::class);

        if (!$user || !$scope->canAccessAgent($request->user(), $holiday->agent, false)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas enregistrer le retour de cet agent.'
            ], 403);
        }

        if (!$this->canManageHolidayRequests($user, $request->user()) && $holiday->demande_par !== $user->id) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour enregistrer ce retour.'
            ], 403);
        }

        if ($holiday->statut_demande !== 'approuve') {
            return response()->json([
                'message' => 'Seuls les congés approuvés peuvent être marqués comme retournés.'
            ], 422);
        }

        $validated = $request->validate([
            'date_retour' => 'nullable|date|after_or_equal:' . $holiday->date_debut->toDateString()
        ]);

        $dateRetour = $validated['date_retour']
            ? Carbon::parse($validated['date_retour'])
            : Carbon::today();

        $holiday->markReturned($dateRetour);

        // Marquer l'agent comme disponible
        AgentStatus::setNewStatus($holiday->agent_id, [
            'statut' => 'disponible',
            'date_debut' => $dateRetour,
            'motif' => 'Retour de congé',
            'created_by' => auth()->user()->agent->id
        ]);

        return response()->json([
            'message' => 'Retour d\'agent enregistré',
            'holiday' => $holiday->fresh()
        ]);
    }

    /**
     * Modification d'un congé (avant approbation)
     */
    public function update(Request $request, Holiday $holiday)
    {
        if (!in_array($holiday->statut_demande, ['en_attente', 'approuve'], true)) {
            return response()->json([
                'message' => 'Seuls les congés en attente ou approuvés peuvent être modifiés'
            ], 422);
        }

        $user = auth()->user()->agent;
        $scope = app(UserDataScope::class);
        $isRh = $this->canManageHolidayRequests($user, $request->user());

        if (!$user || !$scope->canAccessAgent($request->user(), $holiday->agent, false)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas modifier ce congé.'
            ], 403);
        }

        // Un congé approuvé/en cours ne peut être modifié que par RH.
        if (!$isRh && ($holiday->statut_demande !== 'en_attente' || $holiday->demande_par !== $user->id)) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour modifier ce congé'
            ], 403);
        }

        $validated = $request->validate([
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date',
            'type_conge' => 'sometimes|in:annuel,maladie,maternite,paternite,urgence,special',
            'motif' => 'nullable|string|max:1000',
            'observation' => 'nullable|string|max:1000',
            'interim_assure_par' => 'nullable|exists:agents,id',
            'document_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $dateDébut = Carbon::parse($validated['date_debut'] ?? $holiday->date_debut);
        $dateFin = Carbon::parse($validated['date_fin'] ?? $holiday->date_fin);

        if ($dateFin->lt($dateDébut)) {
            return response()->json([
                'message' => 'La date de fin doit être postérieure ou égale à la date de début.'
            ], 422);
        }

        // Vérifier les conflits si les dates changent
        if (isset($validated['date_debut']) || isset($validated['date_fin'])) {
            if (Holiday::hasConflict($holiday->agent_id, $dateDébut, $dateFin, $holiday->id)) {
                return response()->json([
                    'message' => 'Conflit de dates : l\'agent a déjà un congé approuvé sur cette période'
                ], 422);
            }
        }

        if (!empty($validated['interim_assure_par'])) {
            $interim = Agent::find($validated['interim_assure_par']);
            if (!$scope->canUseAgentAsInterim($request->user(), $interim, $holiday->agent)) {
                return response()->json([
                    'message' => 'L\'intérimaire doit être un agent actif de votre périmètre autorisé.',
                    'errors' => [
                        'interim_assure_par' => ['Choisissez un intérimaire autorisé pour ce congé.'],
                    ],
                ], 422);
            }
        }

        $newType = $validated['type_conge'] ?? $holiday->type_conge;
        $newDays = Holiday::calculateWorkingDays($dateDébut, $dateFin);
        if ($newDays <= 0) {
            return response()->json([
                'message' => 'La période choisie doit contenir au moins un jour ouvrable.'
            ], 422);
        }

        $oldApprovedAnnualDays = $holiday->statut_demande === 'approuve'
            && $holiday->type_conge === 'annuel'
            && (int) $holiday->date_debut->year === (int) $dateDébut->year
            ? $holiday->nombre_jours
            : 0;

        if ($newType === 'annuel') {
            $quota = $this->entitlementService()->quotaForAgent($holiday->agent, (int) $dateDébut->year);
            $availableDays = $quota['jours_restants'] + $oldApprovedAnnualDays;

            if ($newDays > $availableDays) {
                return response()->json([
                    'message' => "Quota individuel insuffisant : " . max(0, (int) $availableDays) . " jour(s) disponible(s) sur {$quota['jours_autorises']}. Congé demandé : {$newDays} jour(s)."
                ], 422);
            }
        }

        $oldPlanningId = $holiday->holiday_planning_id;
        $oldDays = $holiday->nombre_jours;
        $wasApproved = $holiday->statut_demande === 'approuve';

        if (isset($validated['date_debut']) || isset($validated['date_fin'])) {
            $validated['nombre_jours'] = $newDays;
            $validated['date_retour_prevu'] = $dateFin->copy()->addDay();
            $validated['date_retour_effectif'] = null;
        }

        if (isset($validated['date_debut']) || isset($validated['date_fin']) || isset($validated['type_conge'])) {
            $planning = $this->resolvePlanning($holiday->agent, (int) $dateDébut->year);
            $validated['holiday_planning_id'] = $planning?->id;
        }

        // Gérer le document médical
        if ($request->hasFile('document_medical')) {
            if ($holiday->document_medical) {
                Storage::disk('public')->delete($holiday->document_medical);
            }
            $validated['document_medical'] = $request->file('document_medical')
                ->store('documents/medical', 'public');
        }

        $holiday->update($validated);
        $holiday->refresh();

        if ($wasApproved && $oldPlanningId) {
            HolidayPlanning::find($oldPlanningId)?->decrementJoursUtilises($oldDays);
        }

        if ($holiday->statut_demande === 'approuve' && $holiday->holiday_planning_id) {
            $holiday->holidayPlanning?->incrementJoursUtilises($holiday->nombre_jours);
        }

        return response()->json([
            'message' => 'Congé mis à jour avec succès',
            'holiday' => $holiday->fresh(['agent', 'interimPar', 'holidayPlanning'])
        ]);
    }

    /**
     * Statistiques des congés par agent
     */
    public function agentStats(Agent $agent, Request $request)
    {
        $year = $request->get('year', date('Y'));

        if (!app(UserDataScope::class)->canAccessAgent($request->user(), $agent, false)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas consulter les congés de cet agent.'
            ], 403);
        }

        $joursUtilises = $agent->holidays()->forYear($year)->approved()->sum('nombre_jours');

        $quota = $this->entitlementService()->quotaForAgent($agent, (int) $year);

        $stats = [
            'total_conges' => $agent->holidays()->forYear($year)->count(),
            'jours_total' => $joursUtilises,
            'conges_approuves' => $agent->holidays()->forYear($year)->approved()->count(),
            'conges_en_attente' => $agent->holidays()->forYear($year)->pending()->count(),
            'quota_annuel' => $quota['jours_autorises'],
            'jours_annuels_utilises' => $quota['jours_utilises'],
            'jours_annuels_en_attente' => $quota['jours_en_attente'],
            'jours_restants' => $quota['jours_restants'],
            'quota_source' => $quota['source'],
            'quota_source_label' => $quota['source_label'],
            'quota_notes' => $quota['notes'],
            'par_type' => $agent->holidays()
                ->forYear($year)
                ->approved()
                ->selectRaw('type_conge, COUNT(*) as count, SUM(nombre_jours) as total_jours')
                ->groupBy('type_conge')
                ->get(),
            'statut_actuel' => $agent->currentStatus()
        ];

        return response()->json($stats);
    }

    /**
     * Mise à jour du quota annuel individuel d'un agent.
     */
    public function updateAgentEntitlement(Agent $agent, Request $request)
    {
        $currentAgent = $request->user()?->agent;

        if (!$currentAgent || !$currentAgent->hasRole(['RH National', 'RH Provincial'])) {
            return response()->json([
                'message' => 'Seuls les RH National et RH Provincial peuvent modifier les jours de congés individuels.'
            ], 403);
        }

        if (!app(UserDataScope::class)->canAccessAgent($request->user(), $agent, false)) {
            return response()->json([
                'message' => 'Vous ne pouvez pas modifier les jours de congés de cet agent.'
            ], 403);
        }

        $validated = $request->validate([
            'annee' => 'required|integer|min:2020|max:2035',
            'jours_autorises' => 'required|integer|min:0|max:120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $entitlement = AgentHolidayEntitlement::firstOrNew([
            'agent_id' => $agent->id,
            'annee' => (int) $validated['annee'],
        ]);

        if (!$entitlement->exists) {
            $entitlement->created_by = $currentAgent->id;
        }

        $entitlement->jours_autorises = (int) $validated['jours_autorises'];
        $entitlement->notes = $validated['notes'] ?? null;
        $entitlement->updated_by = $currentAgent->id;
        $entitlement->save();

        return response()->json([
            'message' => 'Jours de congés mis à jour avec succès',
            'agent' => $this->entitlementService()
                ->enrichAgents(collect([$agent->fresh()]), (int) $validated['annee'])
                ->first(),
        ]);
    }

    /**
     * Congés actifs (en cours)
     */
    public function active(Request $request)
    {
        $date = $request->get('date') ? Carbon::parse($request->date) : Carbon::today();

        $holidays = Holiday::with([
            'agent.departement',
            'holidayPlanning'
        ])
            ->active($date)
            ->orderBy('date_debut')
            ->get();

        return response()->json($holidays);
    }

    /**
     * Vérifier la disponibilité d'un agent
     */
    public function checkAvailability(Agent $agent, Request $request)
    {
        $validated = $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut'
        ]);

        $start = Carbon::parse($validated['date_debut']);
        $end = Carbon::parse($validated['date_fin']);

        $conflicts = Holiday::where('agent_id', $agent->id)
            ->approved()
            ->between($start, $end)
            ->with('holidayPlanning')
            ->get();

        $available = $conflicts->isEmpty();

        return response()->json([
            'available' => $available,
            'conflicts' => $conflicts,
            'agent' => $agent->load('currentStatus')
        ]);
    }

    /**
     * Demande de congé par l'agent lui-même (accessible à tous les agents authentifiés).
     * Force agent_id = agent du user connecté.
     */
    public function storeOwn(Request $request)
    {
        $user  = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return response()->json([
                'message' => "Votre compte n'est pas associé à un agent."
            ], 422);
        }

        $validated = $request->validate([
            'date_debut'         => 'required|date|after_or_equal:today',
            'date_fin'           => 'required|date|after_or_equal:date_debut',
            'type_conge'         => 'required|in:annuel,maladie,maternite,paternite,urgence,special',
            'motif'              => 'required|string|max:1000',
            'observation'        => 'nullable|string|max:1000',
            'interim_assure_par' => 'nullable|exists:agents,id',
            'document_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png|max:2048',
            'lettre_demande'     => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png|max:5120',
        ]);

        $dateDébut = Carbon::parse($validated['date_debut']);
        $dateFin   = Carbon::parse($validated['date_fin']);

        // Conflit de dates
        if (Holiday::hasConflict($agent->id, $dateDébut, $dateFin)) {
            return response()->json([
                'message' => 'Conflit de dates : vous avez déjà un congé sur cette période.'
            ], 422);
        }

        if (!empty($validated['interim_assure_par'])) {
            $scope = app(UserDataScope::class);
            $interim = Agent::find($validated['interim_assure_par']);

            if (!$scope->canUseAgentAsInterim($user, $interim, $agent)) {
                return response()->json([
                    'message' => 'L\'intérimaire doit être un agent actif de votre département, de votre province ou de votre périmètre autorisé.',
                    'errors' => [
                        'interim_assure_par' => ['Choisissez un intérimaire autorisé pour cette demande.'],
                    ],
                ], 422);
            }
        }

        $nombreJours = Holiday::calculateWorkingDays($dateDébut, $dateFin);
        if ($nombreJours <= 0) {
            return response()->json([
                'message' => 'La période choisie doit contenir au moins un jour ouvrable.'
            ], 422);
        }

        // Auto-résolution du planning
        $planning = $this->resolvePlanning($agent, $dateDébut->year);
        if ($planning) {
            $validated['holiday_planning_id'] = $planning->id;
        }

        // Vérification quota individuel (annuel uniquement)
        if ($validated['type_conge'] === 'annuel') {
            $quota = $this->entitlementService()->quotaForAgent($agent, (int) $dateDébut->year);
            if ($nombreJours > $quota['jours_restants']) {
                $joursDisponibles = max(0, (int) $quota['jours_restants']);
                return response()->json([
                    'message' => "Quota individuel insuffisant : {$joursDisponibles} jour(s) disponible(s) sur {$quota['jours_autorises']}. Demande : {$nombreJours} jour(s)."
                ], 422);
            }
        }

        // Document médical
        if ($request->hasFile('document_medical')) {
            $validated['document_medical'] = $request->file('document_medical')
                ->store('documents_medicaux', 'public');
        }

        // Lettre de demande
        if ($request->hasFile('lettre_demande')) {
            $validated['lettre_demande'] = $request->file('lettre_demande')
                ->store('lettres_conge', 'public');
        }

        $validated['agent_id']          = $agent->id;
        $validated['nombre_jours']       = $nombreJours;
        $validated['date_retour_prevu']  = $dateFin->copy()->addDay()->format('Y-m-d');
        $validated['statut_demande']     = 'en_attente';
        $validated['demande_par']        = $agent->id;

        $holiday = Holiday::create($validated);
        $holiday->load('agent');

        return response()->json([
            'message' => 'Demande de congé soumise avec succès.',
            'holiday' => $holiday,
        ], 201);
    }

    /**
     * Trouver le planning de congé correspondant à la structure de l'agent pour une année donnée.
     * - Agent provincial  → planning SEP de sa province
     * - Agent national    → planning du département
     * - Autres           → planning SEN national (structure_id = 1)
     */
    private function canManageHolidayRequests(?Agent $agent, $user = null): bool
    {
        if (!$agent) {
            return false;
        }

        $scope = app(UserDataScope::class);

        if ($scope->hasGlobalAdminAccess($user)) {
            return true;
        }

        return $agent->hasRole([
            'Section ressources humaines',
            'Chef Section RH',
            'Chef de Section RH',
            'Chef Section Ressources Humaines',
            'RH National',
            'RH Provincial',
            'SEN',
        ]);
    }

    private function resolvePlanning(Agent $agent, int $year): ?HolidayPlanning
    {
        return $this->entitlementService()->resolvePlanning($agent, $year);
    }
}
