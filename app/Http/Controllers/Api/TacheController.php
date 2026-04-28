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
use App\Services\RoleService;
use App\Services\UserDataScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TacheController extends ApiController
{

    /**
     * Display listing of taches.
     * - Regular agents see taches assigned to them.
     * - Directeurs also see taches they created.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;
        $isDirecteur = app(RoleService::class)->hasDirecteurOrDafRole($user);
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        // Personnel SEN (assistants, secrétaires) : accès aux tâches SEN
        $isSENStaff = false;
        if (!$isSENOrSENA && $agent) {
            $isSENStaff = ($agent->organe ?? '') === 'Secrétariat Exécutif National';
        }

        $mesTachesQuery = $agent
            ? Tache::query()->parAgent($agent->id)
            : Tache::query()->whereRaw('1 = 0');

        $tachesCreeesQuery = (($isDirecteur || $isSENOrSENA || $isSENStaff) && $agent)
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
                ['isDirecteur' => false, 'isSENScope' => true],
                ['mesTaches' => $senResource, 'tachesCreees' => [], 'isDirecteur' => false, 'isSENScope' => true]
            );
        }

        // Scope département : retourne toutes les tâches des agents du même département.
        // Réservé aux agents ayant un departement_id (directeurs, assistants, secrétaires).
        if ($request->input('scope') === 'departement') {
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
                    ['isDirecteur' => $isDirecteur, 'isDeptScope' => true],
                    ['mesTaches' => $deptResource, 'tachesCreees' => [], 'isDirecteur' => $isDirecteur, 'isDeptScope' => true]
                );
            }
            // Pas de département → on retombe sur les tâches personnelles
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
            ], [], [
                'assignedCount' => $assignedCount,
                'newAssignedCount' => $newAssignedCount,
                'inProgressAssignedCount' => $inProgressAssignedCount,
                'createdCount' => $createdCount,
                'isDirecteur' => $isDirecteur,
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
        if (($isDirecteur || $isSENOrSENA) && $agent) {
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
        ], [
            'mesTaches' => $mesTachesResource,
            'tachesCreees' => $tachesCreeesResource,
            'isDirecteur' => $isDirecteur,
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

        if (!$roles->hasTacheManagerRole($user)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent       = $user->agent;
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        if ($isSENOrSENA) {
            $senOrgane         = 'Secrétariat Exécutif National';
            $agentsDisponibles = Agent::actifs()
                ->where('organe', $senOrgane)
                ->when($agent, fn($q) => $q->where('id', '!=', $agent->id))
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom'])
                ->map(fn($a) => ['id' => $a->id, 'nom' => $a->nom, 'prenom' => $a->prenom, 'id_agent' => $a->id_agent]);

            $activitesPta = ActivitePlan::query()
                ->where('annee', now()->year)
                ->where('niveau_administratif', 'SEN')
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
                ->get(['id', 'nom', 'prenom'])
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
            'source_emetteurs' => [
                ['value' => 'directeur', 'label' => 'Directeur'],
                ['value' => 'sen',       'label' => 'SEN'],
                ['value' => 'sep',       'label' => 'SEP'],
                ['value' => 'sel',       'label' => 'SEL'],
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

        if (!$roles->hasTacheManagerRole($user)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'agent_id'        => 'required|exists:agents,id',
            'titre'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'source_type'     => 'required|in:pta,hors_pta',
            'source_emetteur' => 'required|in:directeur,sen,sep,sel,autre',
            'activite_plan_id'=> 'nullable|required_if:source_type,pta|exists:activite_plans,id',
            'priorite'        => 'required|in:normale,haute,urgente',
            'date_echeance'   => 'nullable|date',
            'date_tache'      => 'nullable|date',
            'documents'       => 'nullable|array',
            'documents.*'     => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
        ]);

        $agent       = $user->agent;
        $targetAgent = Agent::findOrFail($validated['agent_id']);
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        if ($isSENOrSENA) {
            if ($targetAgent->organe !== 'Secrétariat Exécutif National') {
                return response()->json(['message' => 'Vous ne pouvez assigner des tâches qu\'aux agents du SEN.'], 403);
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

        $validated['createur_id'] = $agent->id;
        $validated['statut'] = 'nouvelle';
        $validated['pourcentage'] = 0;
        if ($validated['source_type'] !== 'pta') {
            $validated['activite_plan_id'] = null;
        }

        $tache = Tache::create($validated);

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

        // SEN/SENA ou personnel du SEN : accès garanti à toutes les tâches SEN
        if ($isSENOrSENA || $isSENStaff) {
            $tache->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent']);
            $resource = TacheResource::make($tache);
            $resolved = $resource->resolve();
            return $this->resource($resource, [
                'isCreateur' => $isCreateur,
                'isAssigne'  => $isAssigne,
                'debug' => [
                    'createur_exists' => !is_null($tache->createur),
                    'agent_exists' => !is_null($tache->agent),
                    'createur_id' => $tache->createur_id,
                    'agent_id' => $tache->agent_id,
                    'has_nom_complet_createur' => $tache->createur ? isset($tache->createur->nom_complet) : false,
                    'has_nom_complet_agent' => $tache->agent ? isset($tache->agent->nom_complet) : false,
                    'resolved_keys' => array_keys($resolved),
                    'createur_key_exists' => array_key_exists('createur', $resolved),
                    'agent_key_exists' => array_key_exists('agent', $resolved),
                    'createur_value' => $resolved['createur'] ?? null,
                    'agent_value' => $resolved['agent'] ?? null,
                ],
            ], [
                'isCreateur' => $isCreateur,
                'isAssigne'  => $isAssigne,
            ]);
        }

        if (!$isCreateur && !$isAssigne && $agent?->departement_id) {
            $role = strtolower($user->role?->nom_role ?? '');
            $isDept = in_array($role, ['directeur', 'directeur de département', 'daf', 'assistant', 'assistant de département'])
                   || str_starts_with($role, 'assistant');
            if ($isDept) {
                $taskAgent = Agent::find($tache->agent_id);
                $isDeptManager = $taskAgent && $taskAgent->departement_id === $agent->departement_id;
            }
        }

        if (!$isCreateur && !$isAssigne && !$isDeptManager) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $tache->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent']);

        return $this->resource(TacheResource::make($tache), [
            'isCreateur' => $isCreateur,
            'isAssigne'  => $isAssigne,
        ], [
            'isCreateur' => $isCreateur,
            'isAssigne'  => $isAssigne,
        ]);
    }

    /**
     * Update the status of a tache (assigned agent or SEN/SENA).
     */
    public function updateStatut(Request $request, Tache $tache): JsonResponse
    {
        $user        = $request->user();
        $agent       = $user->agent;
        $isSENOrSENA = $user->hasRole('SEN') || $user->hasRole('SENA');

        $isAssigne = $agent && $tache->agent_id === $agent->id;

        if (!$isAssigne && !$isSENOrSENA) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (!$agent) {
            return response()->json(['message' => 'Vous devez être enregistré comme agent pour effectuer cette action.'], 422);
        }

        $validated = $request->validate([
            'statut'  => 'required|in:en_cours,terminee',
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

        $commentaire = TacheCommentaire::create([
            'tache_id'       => $tache->id,
            'agent_id'       => $agent->id,
            'contenu'        => $validated['contenu'],
            'ancien_statut'  => $ancienStatut,
            'nouveau_statut' => $validated['statut'],
        ]);

        $nouveauPourcentage = $validated['statut'] === 'terminee'
            ? 100
            : $validated['pourcentage'];

        $tache->update([
            'statut' => $validated['statut'],
            'pourcentage' => $nouveauPourcentage,
        ]);

        if ($hasDocument) {
            $this->storeDocumentForTache(
                $tache,
                $agent,
                $request->file('document'),
                $validated['statut'] === 'terminee' ? 'final' : 'progression',
                $commentaire
            );
        }

        $resource = TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent']));

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
            'priorite'        => 'sometimes|required|in:normale,haute,urgente',
            'date_echeance'   => 'nullable|date',
            'date_tache'      => 'nullable|date',
            'source_emetteur' => 'sometimes|required|in:directeur,sen,sep,sel,autre',
        ]);

        $tache->update($validated);

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

        if (!$agent || ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|max:1000',
        ]);

        TacheCommentaire::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'contenu'  => $validated['contenu'],
        ]);

        $resource = TacheResource::make($tache->fresh()->load(['createur', 'agent', 'activitePlan', 'commentaires.agent', 'commentaires.documents.agent', 'documents.agent']));

        return $this->resource($resource, [], [
            'message' => 'Commentaire ajoute.',
        ]);
    }

    public function downloadDocument(Request $request, Tache $tache, TacheDocument $document)
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id)) {
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

        return $this->success($report, [], ['message' => 'Rapport soumis avec succès.'], 201);
    }

    /**
     * View reports for a tache.
     */
    public function viewReports(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || ($tache->createur_id !== $agent->id && $tache->agent_id !== $agent->id && !$user->hasAdminAccess())) {
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

        if (!$this->hasDirecteurOrDafRole($user) && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agentId = $request->input('agent_id');
        $annee = $request->input('annee', now()->year);

        $query = Tache::query()->whereYear('created_at', $annee);

        if (!$user->hasAdminAccess()) {
            $query->where(function ($q) use ($user) {
                $q->where('createur_id', $user->agent?->id);
            });
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
