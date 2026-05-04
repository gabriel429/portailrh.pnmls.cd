<?php

namespace App\Http\Controllers\Api;

use App\Events\TacheAssigned;
use App\Http\Resources\TacheResource;
use App\Models\ActivitePlan;
use App\Models\Tache;
use App\Models\TacheCommentaire;
use App\Models\TacheDocument;
use App\Models\TaskReport;
use App\Models\Agent;
use App\Services\NotificationService;
use App\Services\RoleService;
use App\Services\TacheWorkflowService;
use App\Services\UserDataScope;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TacheController extends ApiController
{
    protected function workflowService(): TacheWorkflowService
    {
        return app(TacheWorkflowService::class);
    }

    /**
     * Display listing of taches.
     * - Regular agents see taches assigned to them.
     * - Directeurs also see taches they created.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;
        $roles = app(RoleService::class);
        $isDirecteur = $roles->hasDirecteurOrDafRole($user);
        $isDeptManager = $roles->isDepartmentManager($user);
        $isTaskManager = $roles->hasTacheManagerRole($user);
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');
        $isSEP = $roles->isSepManager($user);
        $isSEL = $workflow->isSelManager($user) || $workflow->isLocalSupport($user);

        // Personnel SEN (assistants, secrétaires) : accès aux tâches SEN
        $isSENStaff = false;
        if (!$isSENOrSENA && $agent) {
            $isSENStaff = ($agent->organe ?? '') === 'Secrétariat Exécutif National';
        }

        $mesTachesQuery = $agent
            ? Tache::query()->parAgent($agent->id)
            : Tache::query()->whereRaw('1 = 0');

        $tachesCreeesQuery = (($isTaskManager || $isSENStaff) && $agent)
            ? Tache::query()->parCreateur($agent->id)
            : Tache::query()->whereRaw('1 = 0');

        // Scope SEN : toutes les tâches des agents du SEN (pour SEN/SENA et personnel SEN).
        if ($request->input('scope') === 'sen' && ($isSENOrSENA || $isSENStaff)) {
            $senOrgane   = 'Secrétariat Exécutif National';
            $senAgentIds = Agent::where('organe', $senOrgane)->pluck('id');
            $senTaches   = Tache::query()
                ->whereIn('agent_id', $senAgentIds)
                ->with(['agent', 'createur', 'activitePlan', 'documents.agent'])
                ->latest()
                ->get();
            $senResource = TacheResource::collection($senTaches)->resolve();
            return $this->success(
                ['mes_taches' => $senResource, 'taches_creees' => []],
                ['isDirecteur' => false, 'canManageTaches' => $isTaskManager, 'isSENScope' => true],
                ['mesTaches' => $senResource, 'tachesCreees' => [], 'isDirecteur' => false, 'canManageTaches' => $isTaskManager, 'isSENScope' => true]
            );
        }

        // Scope département : retourne toutes les tâches des agents du même département.
        // Réservé aux agents ayant un departement_id (directeurs, assistants, secrétaires).
        if ($request->input('scope') === 'departement' && $isDeptManager) {
            $deptId = $agent?->departement_id;
            if ($deptId) {
                $agentIds = Agent::where('departement_id', $deptId)->pluck('id');
                $deptTaches = Tache::query()
                    ->whereIn('agent_id', $agentIds)
                    ->with(['agent', 'createur', 'activitePlan', 'documents.agent'])
                    ->latest()
                    ->get();
                $deptResource = TacheResource::collection($deptTaches)->resolve();
                return $this->success(
                    ['mes_taches' => $deptResource, 'taches_creees' => []],
                    ['isDirecteur' => $isDirecteur, 'canManageTaches' => $isTaskManager, 'isDeptScope' => true],
                    ['mesTaches' => $deptResource, 'tachesCreees' => [], 'isDirecteur' => $isDirecteur, 'canManageTaches' => $isTaskManager, 'isDeptScope' => true]
                );
            }
            // Pas de département → on retombe sur les tâches personnelles
        }

        if ($request->input('scope') === 'province' && $isSEP) {
            $provinceId = $agent?->province_id;
            if ($provinceId) {
                $provinceTaches = Tache::query()
                    ->whereHas('agent', fn($q) => $q->where('province_id', $provinceId))
                    ->with(['agent', 'createur', 'activitePlan', 'documents.agent'])
                    ->latest()
                    ->get();
                $provinceResource = TacheResource::collection($provinceTaches)->resolve();
                return $this->success(
                    ['mes_taches' => $provinceResource, 'taches_creees' => []],
                    ['isDirecteur' => false, 'canManageTaches' => $isTaskManager, 'isProvinceScope' => true],
                    ['mesTaches' => $provinceResource, 'tachesCreees' => [], 'isDirecteur' => false, 'canManageTaches' => $isTaskManager, 'isProvinceScope' => true]
                );
            }
        }

        if ($request->boolean('summary')) {
            $assignedCount = (clone $mesTachesQuery)->count();
            $newAssignedCount = (clone $mesTachesQuery)
                ->where('statut', 'nouvelle')
                ->count();
            $inProgressAssignedCount = (clone $mesTachesQuery)
                ->where('statut', 'en_cours')
                ->count();
            $createdCount = (clone $tachesCreeesQuery)->count();

            return $this->success([
                'assigned_count' => $assignedCount,
                'new_assigned_count' => $newAssignedCount,
                'in_progress_assigned_count' => $inProgressAssignedCount,
                'created_count' => $createdCount,
                'is_directeur' => $isDirecteur,
                'can_manage_taches' => $isTaskManager,
            ], [], [
                'assignedCount' => $assignedCount,
                'newAssignedCount' => $newAssignedCount,
                'inProgressAssignedCount' => $inProgressAssignedCount,
                'createdCount' => $createdCount,
                'isDirecteur' => $isDirecteur,
                'canManageTaches' => $isTaskManager,
            ]);
        }

        $mesTaches = $agent
            ? (clone $mesTachesQuery)
                ->with('createur')
                ->with('activitePlan')
                ->with('documents.agent')
                ->latest()
                ->get()
            : collect();

        $tachesCreees = collect();
        if (($isTaskManager || $isSENStaff) && $agent) {
            $tachesCreees = (clone $tachesCreeesQuery)
                ->with(['agent', 'activitePlan', 'documents.agent'])
                ->latest()
                ->get();
        }

        $mesTachesResource = TacheResource::collection($mesTaches)->resolve();
        $tachesCreeesResource = TacheResource::collection($tachesCreees)->resolve();

        return $this->success([
            'mes_taches' => $mesTachesResource,
            'taches_creees' => $tachesCreeesResource,
        ], [
            'isDirecteur' => $isDirecteur,
            'canManageTaches' => $isTaskManager,
        ], [
            'mesTaches' => $mesTachesResource,
            'tachesCreees' => $tachesCreeesResource,
            'isDirecteur' => $isDirecteur,
            'canManageTaches' => $isTaskManager,
        ]);
    }

    /**
     * Return agents available for task creation.
     * - Directeur/DAF: agents in their department.
     * - SEN/SENA: all agents of the SEN organe.
     */
    public function create(Request $request): JsonResponse
    {
        $user  = $request->user();
        $roles = app(RoleService::class);
        $workflow = $this->workflowService();

        if (!$roles->hasTacheManagerRole($user)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent       = $user->agent;
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');
        $isSEP = $roles->isSepManager($user);
        $isSEL = $workflow->isSelManager($user) || $workflow->isLocalSupport($user);

        if ($isSENOrSENA) {
            $senOrgane         = 'Secrétariat Exécutif National';
            $agentsDisponibles = Agent::actifs()
                ->where('organe', $senOrgane)
                ->when($agent, fn($q) => $q->where('id', '!=', $agent->id))
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom', 'id_agent'])
                ->map(fn($a) => ['id' => $a->id, 'nom' => $a->nom, 'prenom' => $a->prenom, 'id_agent' => $a->id_agent]);

            $activitesPta = ActivitePlan::query()
                ->where('annee', now()->year)
                ->where('niveau_administratif', 'SEN')
                ->orderBy('titre')
                ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
                ->map(fn($a) => ['id' => $a->id, 'titre' => $a->titre, 'annee' => $a->annee, 'trimestre' => $a->trimestre, 'niveau_administratif' => $a->niveau_administratif]);
        } elseif ($isSEP) {
            if (!$agent || !$agent->province_id) {
                return response()->json(['message' => 'Vous devez etre affecte a une province pour creer des taches.'], 422);
            }

            $agentsDisponibles = Agent::actifs()
                ->where('province_id', $agent->province_id)
                ->where('id', '!=', $agent->id)
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom', 'id_agent'])
                ->map(fn($a) => ['id' => $a->id, 'nom' => $a->nom, 'prenom' => $a->prenom, 'id_agent' => $a->id_agent]);

            $activitesPta = $this->resolveProvinceActivitesPta($agent->province_id);
        } elseif ($isSEL) {
            if (!$agent || !$agent->province_id) {
                return response()->json(['message' => 'Vous devez etre affecte a une province pour creer des taches locales.'], 422);
            }

            $agentsDisponibles = Agent::actifs()
                ->where('province_id', $agent->province_id)
                ->where('organe', 'Secrétariat Exécutif Local')
                ->where('id', '!=', $agent->id)
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom', 'id_agent'])
                ->map(fn($a) => ['id' => $a->id, 'nom' => $a->nom, 'prenom' => $a->prenom, 'id_agent' => $a->id_agent]);

            $activitesPta = ActivitePlan::query()
                ->where('annee', now()->year)
                ->where('niveau_administratif', 'SEL')
                ->when($agent->province_id, fn($q) => $q->where('province_id', $agent->province_id))
                ->orderBy('titre')
                ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
                ->map(fn($a) => ['id' => $a->id, 'titre' => $a->titre, 'annee' => $a->annee, 'trimestre' => $a->trimestre, 'niveau_administratif' => $a->niveau_administratif]);
        } else {
            if (!$agent || !$agent->departement_id) {
                return response()->json(['message' => 'Vous devez etre affecte a un departement pour creer des taches.'], 422);
            }

            $agentsDisponibles = Agent::actifs()
                ->where('departement_id', $agent->departement_id)
                ->where('id', '!=', $agent->id)
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom', 'id_agent'])
                ->map(fn($a) => ['id' => $a->id, 'nom' => $a->nom, 'prenom' => $a->prenom, 'id_agent' => $a->id_agent]);

            $activitesPta = ActivitePlan::query()
                ->where('annee', now()->year)
                ->when($agent->departement_id, fn($q) => $q->where('departement_id', $agent->departement_id))
                ->when(!$agent->departement_id && $agent->province_id, fn($q) => $q->where('province_id', $agent->province_id))
                ->orderBy('titre')
                ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
                ->map(fn($a) => ['id' => $a->id, 'titre' => $a->titre, 'annee' => $a->annee, 'trimestre' => $a->trimestre, 'niveau_administratif' => $a->niveau_administratif]);
        }

        return $this->success([
            'agents'          => $agentsDisponibles,
            'activites_pta'   => $activitesPta,
            'isSENScope'      => $isSENOrSENA,
            'isProvinceScope' => $isSEP,
            'isLocalScope'    => $isSEL,
            'validation_role' => $workflow->determineValidationRole($user),
            'source_emetteurs' => [
                ['value' => 'directeur', 'label' => 'Directeur'],
                ['value' => 'assistant_departement', 'label' => 'Assistant / Secretaire du departement'],
                ['value' => 'sen',       'label' => 'SEN'],
                ['value' => 'sep',       'label' => 'SEP'],
                ['value' => 'secom',     'label' => 'SECOM'],
                ['value' => 'sel',       'label' => 'SEL'],
                ['value' => 'aaf_local', 'label' => 'AAF / RH local'],
                ['value' => 'autre',     'label' => 'Autre'],
            ],
        ]);
    }

    /**
     * Store a newly created tache.
     */
    public function store(Request $request): JsonResponse
    {
        $user  = $request->user();
        $roles = app(RoleService::class);
        $workflow = $this->workflowService();

        if (!$roles->hasTacheManagerRole($user)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'agent_id'        => 'required|exists:agents,id',
            'titre'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'source_type'     => 'required|in:pta,hors_pta',
            'source_emetteur' => 'required|in:directeur,assistant_departement,sen,sep,secom,sel,aaf_local,autre',
            'activite_plan_id'=> 'nullable|required_if:source_type,pta|exists:activite_plans,id',
            'priorite'        => 'required|in:faible,normale,haute,urgente',
            'date_echeance'   => 'nullable|date',
            'date_tache'      => 'nullable|date',
            'documents'       => 'nullable|array',
            'documents.*'     => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
        ]);

        $agent       = $user->agent;
        $targetAgent = Agent::findOrFail($validated['agent_id']);
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');
        $isSEP = $roles->isSepManager($user);
        $isSEL = $workflow->isSelManager($user) || $workflow->isLocalSupport($user);

        if ($isSENOrSENA) {
            if ($targetAgent->organe !== 'Secrétariat Exécutif National') {
                return response()->json(['message' => 'Vous ne pouvez assigner des tâches qu\'aux agents du SEN.'], 403);
            }
        } elseif ($isSEP) {
            if (!$agent || !$agent->province_id) {
                return response()->json(['message' => 'Vous devez etre affecte a une province pour creer des taches.'], 422);
            }
            if ((int) $targetAgent->province_id !== (int) $agent->province_id) {
                return response()->json(['message' => 'Vous ne pouvez assigner des taches qu\'aux agents de votre province.'], 403);
            }
        } elseif ($isSEL) {
            if (!$agent || !$agent->province_id) {
                return response()->json(['message' => 'Vous devez etre affecte a une structure locale pour creer des taches.'], 422);
            }
            if ((int) $targetAgent->province_id !== (int) $agent->province_id || $targetAgent->organe !== 'Secrétariat Exécutif Local') {
                return response()->json(['message' => 'Vous ne pouvez assigner des taches qu\'aux agents locaux de votre ressort.'], 403);
            }
        } else {
            if (!$agent) {
                return response()->json(['message' => 'Vous devez être affecté à un agent pour créer des tâches.'], 422);
            }
            if ($targetAgent->departement_id !== $agent->departement_id) {
                return response()->json(['message' => 'Vous ne pouvez assigner des taches qu\'aux agents de votre departement.'], 403);
            }
        }

        // createur_id : utilise l'agent SEN/SENA s'il existe, sinon l'agent du département
        if (!$agent) {
            return response()->json(['message' => 'Vous devez être enregistré comme agent pour créer des tâches.'], 422);
        }

        if ($validated['source_type'] === 'pta' && $isSEP) {
            $allowedActiviteIds = $this->resolveProvinceActivitesPta((int) $agent->province_id)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            if (
                !empty($validated['activite_plan_id'])
                && !in_array((int) $validated['activite_plan_id'], $allowedActiviteIds, true)
            ) {
                return response()->json([
                    'message' => 'L activite PTA selectionnee n est pas disponible pour votre province.',
                    'errors' => [
                        'activite_plan_id' => ['L activite PTA selectionnee n est pas disponible pour votre province.'],
                    ],
                ], 422);
            }
        }

        $validated['createur_id'] = $agent->id;
        $validated['statut'] = 'nouvelle';
        $validated['pourcentage'] = 0;
        $validated['niveau_gestion'] = $workflow->determineManagementLevel($user);
        $validated['validation_responsable_role'] = $workflow->determineValidationRole($user);
        $validated['validation_statut'] = 'non_requise';
        if ($validated['source_type'] !== 'pta') {
            $validated['activite_plan_id'] = null;
        }

        $tache = Tache::create($validated);
        $workflow->recordHistory(
            $tache,
            $agent,
            'creation',
            'Creation de la tache',
            null,
            $tache->statut,
            null,
            $tache->validation_statut,
            $validated['description'] ?? null,
            [
                'priorite' => $tache->priorite,
                'niveau_gestion' => $tache->niveau_gestion,
                'validation_responsable_role' => $tache->validation_responsable_role,
            ]
        );

        TacheAssigned::dispatch($tache);

        foreach ($request->file('documents', []) as $uploadedFile) {
            $this->storeDocumentForTache($tache, $agent, $uploadedFile, 'initial');
        }

        $resource = TacheResource::make($tache->load(['createur', 'agent', 'activitePlan', 'documents.agent']));

        return $this->resource($resource, [], [
            'message' => 'Tache creee avec succes.',
        ], 201);
    }

    /**
     * Display the specified tache.
     */
    public function show(Request $request, Tache $tache): JsonResponse
    {
        $user  = $request->user();
        $agent = $user->agent;
        $roles = app(RoleService::class);
        $workflow = $this->workflowService();
        $isSEP = $roles->isSepManager($user);

        // SEN/SENA : accès à toutes les tâches des agents du SEN (pas besoin d'agent record)
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        // Vérification élargie pour les assistants/secrétaires du SEN/SENA
        // On regarde l'organe de l'agent connecté plutôt que le nom exact du rôle
        $isSENStaff = false;
        if (!$isSENOrSENA && $agent) {
            // Vérifie si l'agent est au Secrétariat Exécutif National (SEN)
            $agentOrgane = $agent->organe ?? '';
            $isSENStaff = $agentOrgane === 'Secrétariat Exécutif National';
        }

        if (!$agent && !$isSENOrSENA) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $isCreateur   = $agent && $tache->createur_id === $agent->id;
        $isAssigne    = $agent && $tache->agent_id    === $agent->id;
        $isDeptManager = false;
        $isProvinceManager = false;

        // SEN/SENA ou personnel du SEN : accès garanti à toutes les tâches SEN
        if ($isSENOrSENA || $isSENStaff) {
            $tache->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent', 'histories.agent', 'validateur', 'rejecteur', 'bloqueur']);
            $resource = TacheResource::make($tache);
            return $this->resource($resource, [
                'isCreateur' => $isCreateur,
                'isAssigne'  => $isAssigne,
                'canValidateFinal' => $workflow->canFinalValidate($user, $tache),
                'validationRoleLabel' => $workflow->validationRoleLabel($tache->validation_responsable_role),
            ], [
                'isCreateur' => $isCreateur,
                'isAssigne'  => $isAssigne,
                'canValidateFinal' => $workflow->canFinalValidate($user, $tache),
                'validationRoleLabel' => $workflow->validationRoleLabel($tache->validation_responsable_role),
            ]);
        }

        if (!$isCreateur && !$isAssigne && $agent?->departement_id) {
            if ($roles->isDepartmentManager($user)) {
                $taskAgent = Agent::find($tache->agent_id);
                $isDeptManager = $taskAgent && $taskAgent->departement_id === $agent->departement_id;
            }
        }

        if (!$isCreateur && !$isAssigne && !$isDeptManager && $isSEP && $agent?->province_id) {
            $taskAgent = $taskAgent ?? Agent::find($tache->agent_id);
            $isProvinceManager = $taskAgent && (int) $taskAgent->province_id === (int) $agent->province_id;
        }

        $isLocalManager = false;
        if (!$isCreateur && !$isAssigne && !$isDeptManager && !$isProvinceManager && $isSEL && $agent?->province_id) {
            $taskAgent = $taskAgent ?? Agent::find($tache->agent_id);
            $isLocalManager = $taskAgent
                && (int) $taskAgent->province_id === (int) $agent->province_id
                && Str::ascii((string) $taskAgent->organe) === Str::ascii((string) $agent->organe);
        }

        if (!$isCreateur && !$isAssigne && !$isDeptManager && !$isProvinceManager && !$isLocalManager) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $tache->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent', 'histories.agent', 'validateur', 'rejecteur', 'bloqueur']);

        return $this->resource(TacheResource::make($tache), [
            'isCreateur' => $isCreateur,
            'isAssigne'  => $isAssigne,
            'isProvinceManager' => $isProvinceManager,
            'isLocalManager' => $isLocalManager,
            'canValidateFinal' => $workflow->canFinalValidate($user, $tache),
            'validationRoleLabel' => $workflow->validationRoleLabel($tache->validation_responsable_role),
        ], [
            'isCreateur' => $isCreateur,
            'isAssigne'  => $isAssigne,
            'isProvinceManager' => $isProvinceManager,
            'isLocalManager' => $isLocalManager,
            'canValidateFinal' => $workflow->canFinalValidate($user, $tache),
            'validationRoleLabel' => $workflow->validationRoleLabel($tache->validation_responsable_role),
        ]);
    }

    protected function resolveProvinceActivitesPta(int $provinceId)
    {
        $query = ActivitePlan::query()
            ->where('annee', now()->year)
            ->where('niveau_administratif', 'SEP')
            ->where(function ($builder) use ($provinceId) {
                $builder->where('province_id', $provinceId);

                if (Schema::hasTable('activite_plan_province')) {
                    $builder->orWhereHas('provinces', fn($provinceQuery) => $provinceQuery->where('provinces.id', $provinceId));
                }
            })
            ->orderBy('titre');

        try {
            return $query
                ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
                ->map(fn($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'annee' => $a->annee,
                    'trimestre' => $a->trimestre,
                    'niveau_administratif' => $a->niveau_administratif,
                ]);
        } catch (QueryException $exception) {
            Log::warning('Provincial PTA lookup fallback during task creation.', [
                'province_id' => $provinceId,
                'message' => $exception->getMessage(),
            ]);

            return ActivitePlan::query()
                ->where('annee', now()->year)
                ->where('niveau_administratif', 'SEP')
                ->where('province_id', $provinceId)
                ->orderBy('titre')
                ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
                ->map(fn($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre,
                    'annee' => $a->annee,
                    'trimestre' => $a->trimestre,
                    'niveau_administratif' => $a->niveau_administratif,
                ]);
        }
    }

    /**
     * Update the status of a tache (assigned agent or SEN/SENA).
     */
    public function updateStatut(Request $request, Tache $tache): JsonResponse
    {
        $user        = $request->user();
        $agent       = $user->agent;
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');
        $workflow = $this->workflowService();

        $isAssigne = $agent && $tache->agent_id === $agent->id;

        if (!$isAssigne && !$isSENOrSENA) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!$agent) {
            return response()->json(['message' => 'Vous devez être enregistré comme agent pour effectuer cette action.'], 422);
        }

        $validated = $request->validate([
            'statut'  => 'required|in:en_cours,terminee,bloquee',
            'contenu' => 'required|string|max:1000',
            'pourcentage' => 'required|integer|min:0|max:100',
            'document' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png',
        ]);

        $pourcentageChanged = (int) $validated['pourcentage'] !== (int) $tache->pourcentage;
        $hasDocument = $request->hasFile('document');

        if ($validated['statut'] === 'terminee' && !$hasDocument) {
            return response()->json([
                'message' => 'Le document final est obligatoire pour terminer la tache.',
                'errors' => ['document' => ['Le document final est obligatoire pour terminer la tache.']],
            ], 422);
        }

        if ($pourcentageChanged && !$hasDocument) {
            return response()->json([
                'message' => 'Un document justificatif est obligatoire lorsque vous modifiez la progression.',
                'errors' => ['document' => ['Ajoutez un document justificatif pour modifier la progression.']],
            ], 422);
        }

        $ancienStatut = $tache->statut;

        $typeCommentaire = match ($validated['statut']) {
            'bloquee' => 'blocage',
            'terminee' => 'validation',
            default => 'commentaire',
        };

        $commentaire = TacheCommentaire::create([
            'tache_id'       => $tache->id,
            'agent_id'       => $agent->id,
            'contenu'        => $validated['contenu'],
            'type_commentaire' => $typeCommentaire,
            'ancien_statut'  => $ancienStatut,
            'nouveau_statut' => $validated['statut'],
        ]);

        $nouveauPourcentage = $validated['statut'] === 'terminee'
            ? 100
            : $validated['pourcentage'];

        if ($validated['statut'] === 'bloquee') {
            $workflow->markBlocked($tache, $agent, $validated['contenu']);
        } else {
            if ($ancienStatut === 'bloquee' && $validated['statut'] === 'en_cours') {
                $workflow->reopenAfterBlocked($tache, $agent, $validated['contenu']);
            }

            $tache->update([
                'statut' => $validated['statut'],
                'pourcentage' => $nouveauPourcentage,
                'validation_statut' => $validated['statut'] === 'terminee' ? 'a_valider' : 'non_requise',
                'soumise_validation_at' => $validated['statut'] === 'terminee' ? now() : null,
                'validation_commentaire' => $validated['statut'] === 'terminee' ? $validated['contenu'] : null,
                'blocked_by' => null,
                'blocked_at' => null,
                'blocking_reason' => null,
            ]);

            if ($validated['statut'] === 'terminee') {
                $workflow->recordHistory(
                    $tache,
                    $agent,
                    'soumission_validation',
                    'Soumission pour validation',
                    $ancienStatut,
                    $validated['statut'],
                    'non_requise',
                    'a_valider',
                    $validated['contenu']
                );
            } else {
                $workflow->recordHistory(
                    $tache,
                    $agent,
                    'mise_a_jour_statut',
                    'Mise a jour du statut',
                    $ancienStatut,
                    $validated['statut'],
                    $tache->validation_statut,
                    $tache->validation_statut,
                    $validated['contenu']
                );
            }
        }

        if ($hasDocument) {
            $this->storeDocumentForTache(
                $tache,
                $agent,
                $request->file('document'),
                  $validated['statut'] === 'terminee' ? 'final' : 'progression',
                  $commentaire
              );
          }

        if ($validated['statut'] === 'terminee') {
            $validatorIds = $workflow->finalValidators($tache)->pluck('id')->all();
            if (!empty($validatorIds)) {
                NotificationService::envoyerMultipleAvecEmail(
                    $validatorIds,
                    'tache',
                    'Tache soumise pour validation',
                    $this->agentDisplayName($agent) . ' a soumis la tache "' . $tache->titre . '" pour validation finale.',
                    '/taches/' . $tache->id,
                    $user->id
                );
            }
        } else {
            $message = sprintf(
                '%s a mis a jour la tache "%s" : statut %s, progression %d%%.',
                $this->agentDisplayName($agent),
                $tache->titre,
                $this->tacheStatutLabel($validated['statut']),
                (int) $nouveauPourcentage
            );

            $this->notifyTacheParticipants(
                $tache,
                $user->id,
                'Tache mise a jour',
                $message
            );
        }

        $resource = TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent', 'histories.agent', 'validateur', 'rejecteur', 'bloqueur']));

        return $this->resource($resource, [], [
            'message' => 'Statut mis a jour avec succes.',
        ]);
    }

    /**
     * Update editable fields of a tache (createur, SEN, SENA).
     */
    public function update(Request $request, Tache $tache): JsonResponse
    {
        $user  = $request->user();
        $agent = $user->agent;

        $isCreateur  = $agent && $tache->createur_id === $agent->id;
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        $isSENTask = false;
        if ($isSENOrSENA && $tache->agent_id) {
            $isSENTask = Agent::where('id', $tache->agent_id)
                ->where('organe', 'Secrétariat Exécutif National')
                ->exists();
        }

        if (!$isCreateur && !($isSENOrSENA && $isSENTask)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'titre'           => 'sometimes|required|string|max:255',
            'description'     => 'nullable|string',
            'priorite'        => 'sometimes|required|in:faible,normale,haute,urgente',
            'date_echeance'   => 'nullable|date',
            'date_tache'      => 'nullable|date',
            'source_emetteur' => 'sometimes|required|in:directeur,assistant_departement,sen,sep,secom,sel,aaf_local,autre',
        ]);

        $tache->fill($validated);
        $changedFields = array_keys($tache->getDirty());

        if (!empty($changedFields)) {
            $tache->save();
            $this->workflowService()->recordHistory(
                $tache,
                $agent,
                'modification',
                'Modification de la tache',
                $tache->statut,
                $tache->statut,
                $tache->validation_statut,
                $tache->validation_statut,
                null,
                ['fields' => $changedFields]
            );

            $message = sprintf(
                '%s a modifie %s de la tache "%s".',
                $this->agentDisplayName($agent),
                $this->formatTacheChangedFields($changedFields),
                $tache->titre
            );

            $this->notifyTacheParticipants(
                $tache,
                $user->id,
                'Tache modifiee',
                $message
            );
        }

        return $this->success([
            'tache'   => TacheResource::make($tache->fresh(['createur', 'agent', 'activitePlan'])),
            'message' => 'Tâche mise à jour avec succès.',
        ]);
    }

    /**
     * Delete a tache (SENA/SEN only, or createur for non-terminee tasks).
     */
    public function destroy(Request $request, Tache $tache): JsonResponse
    {
        $user  = $request->user();
        $agent = $user->agent;

        $isCreateur  = $agent && $tache->createur_id === $agent->id;
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        // Vérification élargie pour les assistants/secrétaires du SEN/SENA
        $isSENStaff = false;
        if (!$isSENOrSENA && $agent) {
            $agentOrgane = $agent->organe ?? '';
            $isSENStaff = $agentOrgane === 'Secrétariat Exécutif National';
        }

        $isSENTask = false;
        if (($isSENOrSENA || $isSENStaff) && $tache->agent_id) {
            $isSENTask = Agent::where('id', $tache->agent_id)
                ->where('organe', 'Secrétariat Exécutif National')
                ->exists();
        }

        if (!$isCreateur && !(($isSENOrSENA || $isSENStaff) && $isSENTask)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if ($tache->statut === 'terminee' && !($isSENOrSENA || $isSENStaff)) {
            return response()->json(['message' => 'Impossible de supprimer une tâche terminée.'], 422);
        }

        $tache->delete();

        return response()->json(null, 204);
    }

    /**
     * Add a comment to a tache.
     */
    public function addCommentaire(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$this->canAccessTacheConsultation($user, $tache)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|max:1000',
            'type_commentaire' => 'nullable|in:commentaire,relance,correction',
        ]);

        TacheCommentaire::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'contenu'  => $validated['contenu'],
            'type_commentaire' => $validated['type_commentaire'] ?? 'commentaire',
        ]);

        $this->workflowService()->recordHistory(
            $tache,
            $agent,
            'commentaire',
            'Ajout de commentaire',
            $tache->statut,
            $tache->statut,
            $tache->validation_statut,
            $tache->validation_statut,
            $validated['contenu'],
            ['type_commentaire' => $validated['type_commentaire'] ?? 'commentaire']
        );

        $message = sprintf(
            '%s a ajoute un commentaire sur la tache "%s" : %s',
            $this->agentDisplayName($agent),
            $tache->titre,
            Str::limit($validated['contenu'], 140)
        );

        $this->notifyTacheParticipants(
            $tache,
            $user->id,
            'Nouveau commentaire sur une tache',
            $message
        );

        $resource = TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent', 'histories.agent', 'validateur', 'rejecteur', 'bloqueur']));

        return $this->resource($resource, [], [
            'message' => 'Commentaire ajoute.',
        ]);
    }

    public function validateTask(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;
        $workflow = $this->workflowService();

        if (!$agent || !$workflow->canFinalValidate($user, $tache)) {
            return response()->json(['message' => 'Acces refuse pour cette validation finale.'], 403);
        }

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $workflow->validateTask($tache, $agent, $validated['commentaire'] ?? null);

        TacheCommentaire::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'contenu' => $validated['commentaire'] ?: 'Tache validee.',
            'type_commentaire' => 'validation',
            'ancien_statut' => $tache->statut,
            'nouveau_statut' => $tache->statut,
        ]);

        $this->notifyTacheParticipants(
            $tache,
            $user->id,
            'Tache validee',
            $this->agentDisplayName($agent) . ' a valide la tache "' . $tache->titre . '".'
        );

        return $this->resource(
            TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent', 'histories.agent', 'validateur', 'rejecteur', 'bloqueur'])),
            [],
            ['message' => 'Tache validee avec succes.']
        );
    }

    public function rejectTask(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;
        $workflow = $this->workflowService();

        if (!$agent || !$workflow->canFinalValidate($user, $tache)) {
            return response()->json(['message' => 'Acces refuse pour ce rejet.'], 403);
        }

        $validated = $request->validate([
            'commentaire' => 'required|string|max:1000',
        ]);

        $workflow->rejectTask($tache, $agent, $validated['commentaire']);

        TacheCommentaire::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'contenu' => $validated['commentaire'],
            'type_commentaire' => 'rejet',
            'ancien_statut' => 'terminee',
            'nouveau_statut' => 'en_cours',
        ]);

        $this->notifyTacheParticipants(
            $tache,
            $user->id,
            'Tache retournee pour correction',
            $this->agentDisplayName($agent) . ' a retourne la tache "' . $tache->titre . '" pour correction.'
        );

        return $this->resource(
            TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent', 'histories.agent', 'validateur', 'rejecteur', 'bloqueur'])),
            [],
            ['message' => 'Tache retournee pour correction.']
        );
    }

    public function downloadDocument(Request $request, Tache $tache, TacheDocument $document)
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$this->canAccessTacheConsultation($user, $tache)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if ($document->tache_id !== $tache->id) {
            return response()->json(['message' => 'Document introuvable pour cette tache.'], 404);
        }

        $filePath = public_path($document->fichier);
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return response()->download($filePath, $document->nom_original);
    }

    protected function canAccessTacheConsultation($user, Tache $tache): bool
    {
        $agent = $user?->agent;

        if ($agent && ((int) $tache->createur_id === (int) $agent->id || (int) $tache->agent_id === (int) $agent->id)) {
            return true;
        }

        $isSENOrSENA = $user?->hasRole('SEN') || $user?->hasRole('SENA');
        $isSENStaff = !$isSENOrSENA && $agent && ($agent->organe ?? '') === 'Secrétariat Exécutif National';
        if ($isSENOrSENA || $isSENStaff) {
            return true;
        }

        $taskAgent = $tache->agent_id ? Agent::find($tache->agent_id) : null;
        if (!$taskAgent || !$agent) {
            return false;
        }

        if (app(RoleService::class)->isDepartmentManager($user) && $agent->departement_id) {
            return (int) $taskAgent->departement_id === (int) $agent->departement_id;
        }

        if (app(RoleService::class)->isSepManager($user) && $agent->province_id) {
            return (int) $taskAgent->province_id === (int) $agent->province_id;
        }

        $workflow = $this->workflowService();
        if (($workflow->isSelManager($user) || $workflow->isLocalSupport($user)) && $agent->province_id) {
            return (int) $taskAgent->province_id === (int) $agent->province_id
                && Str::ascii((string) $taskAgent->organe) === Str::ascii((string) $agent->organe);
        }

        return false;
    }

    protected function notifyTacheParticipants(Tache $tache, ?int $emetteurId, string $titre, string $message): void
    {
        $tache->loadMissing(['agent.user', 'createur.user']);

        $recipientIds = collect([
            $tache->agent?->user?->id,
            $tache->createur?->user?->id,
        ])
            ->filter(fn($id) => $id && (!$emetteurId || (int) $id !== (int) $emetteurId))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        NotificationService::envoyerMultipleAvecEmail(
            $recipientIds,
            'tache',
            $titre,
            $message,
            '/taches/' . $tache->id,
            $emetteurId
        );
    }

    protected function agentDisplayName(?Agent $agent): string
    {
        if (!$agent) {
            return 'Un utilisateur';
        }

        $name = trim(($agent->prenom ?? '') . ' ' . ($agent->nom ?? ''));

        return $name !== '' ? $name : 'Un utilisateur';
    }

    protected function tacheStatutLabel(?string $statut): string
    {
        return [
            'nouvelle' => 'en attente',
            'en_cours' => 'en cours',
            'terminee' => 'terminee',
            'bloquee' => 'bloquee',
        ][$statut] ?? 'mis a jour';
    }

    protected function formatTacheChangedFields(array $fields): string
    {
        $labels = [
            'titre' => 'le titre',
            'description' => 'la description',
            'priorite' => 'la priorite',
            'date_echeance' => "l'echeance",
            'date_tache' => "la date de l'agenda",
            'source_emetteur' => 'la source',
        ];

        $names = collect($fields)
            ->filter(fn($field) => $field !== 'updated_at')
            ->map(fn($field) => $labels[$field] ?? $field)
            ->values();

        if ($names->isEmpty()) {
            return 'les informations';
        }

        if ($names->count() === 1) {
            return $names->first();
        }

        if ($names->count() === 2) {
            return $names->implode(' et ');
        }

        return $names->slice(0, -1)->implode(', ') . ' et ' . $names->last();
    }

    protected function storeDocumentForTache(Tache $tache, Agent $agent, $uploadedFile, string $typeDocument, ?TacheCommentaire $commentaire = null): TacheDocument
    {
        $extension   = $uploadedFile->getClientOriginalExtension();
        $filename    = Str::uuid() . ($extension ? '.' . $extension : '');
        $directory   = public_path('uploads/taches');
        $mimeType    = $uploadedFile->getMimeType();
        $taille      = $uploadedFile->getSize();
        $nomOriginal = $uploadedFile->getClientOriginalName();

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $uploadedFile->move($directory, $filename);

        return TacheDocument::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'tache_commentaire_id' => $commentaire?->id,
            'type_document' => $typeDocument,
            'titre' => pathinfo($nomOriginal, PATHINFO_FILENAME),
            'fichier' => 'uploads/taches/' . $filename,
            'nom_original' => $nomOriginal,
            'mime_type' => $mimeType,
            'taille' => $taille,
        ]);
    }

    /**
     * Submit an execution report for a tache.
     */
    public function submitReport(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || $tache->agent_id !== $agent->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'rapport' => 'required|string',
            'fichier' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx',
        ]);

        $fichierPath = null;
        if ($request->hasFile('fichier')) {
            $fichierPath = $request->file('fichier')->store('task_reports', 'public');
        }

        $report = TaskReport::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'rapport' => $validated['rapport'],
            'fichier' => $fichierPath,
        ]);

        $this->workflowService()->recordHistory(
            $tache,
            $agent,
            'rapport',
            'Rapport d execution soumis',
            $tache->statut,
            $tache->statut,
            $tache->validation_statut,
            $tache->validation_statut,
            $validated['rapport']
        );

        $this->notifyTacheParticipants(
            $tache,
            $user->id,
            'Rapport de tache soumis',
            $this->agentDisplayName($agent) . ' a soumis un rapport pour la tache "' . $tache->titre . '".'
        );

        return $this->success($report, [], ['message' => 'Rapport soumis avec succes.'], 201);
    }

    /**
     * View reports for a tache.
     */
    public function viewReports(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessTacheConsultation($user, $tache) && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $reports = TaskReport::where('tache_id', $tache->id)
            ->with('agent:id,nom,prenom')
            ->latest()
            ->get();

        return $this->success($reports);
    }

    /**
     * Performance report across tasks (for Directeurs / RH).
     */
    public function performanceReport(Request $request): JsonResponse
    {
        $user = $request->user();
        $roles = app(RoleService::class);
        $workflow = $this->workflowService();

        if (!$roles->hasTacheManagerRole($user) && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agentId = $request->input('agent_id');
        $annee = $request->input('annee', now()->year);

        $query = Tache::query()->whereYear('created_at', $annee);

        if (!$user->hasAdminAccess() && $user->agent) {
            $agent = $user->agent;

            if ($user->hasRole('SEN') || $user->hasRole('SENA') || str_contains(Str::ascii((string) $agent->organe), 'National')) {
                $query->whereHas('agent', fn($q) => $q->where('organe', 'Secrétariat Exécutif National'));
            } elseif ($roles->isSepManager($user) && $agent->province_id) {
                $query->whereHas('agent', fn($q) => $q->where('province_id', $agent->province_id));
            } elseif (($workflow->isSelManager($user) || $workflow->isLocalSupport($user)) && $agent->province_id) {
                $query->whereHas('agent', fn($q) => $q
                    ->where('province_id', $agent->province_id)
                    ->where('organe', $agent->organe));
            } elseif ($roles->isDepartmentManager($user) && $agent->departement_id) {
                $query->whereHas('agent', fn($q) => $q->where('departement_id', $agent->departement_id));
            } else {
                $query->where('createur_id', $agent->id);
            }
        }

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        $taches = $query->get();

        $agents = $taches->groupBy('agent_id')->map(function ($group, $agentId) {
            $agent = Agent::find($agentId);
            return [
                'agent_id' => $agentId,
                'nom' => $agent ? $agent->prenom . ' ' . $agent->nom : 'Inconnu',
                'total' => $group->count(),
                'terminees' => $group->where('statut', 'terminee')->count(),
                'en_cours' => $group->where('statut', 'en_cours')->count(),
                'nouvelles' => $group->where('statut', 'nouvelle')->count(),
                'bloquees' => $group->where('statut', 'bloquee')->count(),
                'taux_completion' => $group->count() > 0
                    ? round($group->where('statut', 'terminee')->count() / $group->count() * 100, 1)
                    : 0,
                'pourcentage_moyen' => round($group->avg('pourcentage'), 1),
                'rapports_soumis' => TaskReport::where('agent_id', $agentId)
                    ->whereIn('tache_id', $group->pluck('id'))
                    ->count(),
            ];
        })->values();

        return $this->success([
            'annee' => $annee,
            'total_taches' => $taches->count(),
            'taux_completion_global' => $taches->count() > 0
                ? round($taches->where('statut', 'terminee')->count() / $taches->count() * 100, 1)
                : 0,
            'agents' => $agents,
        ]);
    }
}
