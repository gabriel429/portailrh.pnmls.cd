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
        $isDirecteur = $user->hasRole('Directeur');

        $mesTachesQuery = $agent
            ? Tache::query()->parAgent($agent->id)
            : Tache::query()->whereRaw('1 = 0');

        $tachesCreeesQuery = ($isDirecteur && $agent)
            ? Tache::query()->parCreateur($agent->id)
            : Tache::query()->whereRaw('1 = 0');

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
        if ($isDirecteur && $agent) {
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
     * Return agents in the same department (for task creation).
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Directeur')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agent = $user->agent;
        if (!$agent || !$agent->departement_id) {
            return response()->json([
                'message' => 'Vous devez etre affecte a un departement pour creer des taches.',
            ], 422);
        }

        $agentsDuDepartement = Agent::actifs()
            ->where('departement_id', $agent->departement_id)
            ->where('id', '!=', $agent->id)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom'])
            ->map(fn($a) => [
                'id' => $a->id,
                'nom' => $a->nom,
                'prenom' => $a->prenom,
                'id_agent' => $a->id_agent,
            ]);

        $activitesPta = ActivitePlan::query()
            ->where('annee', now()->year)
            ->when($agent->departement_id, fn($query) => $query->where('departement_id', $agent->departement_id))
            ->when(!$agent->departement_id && $agent->province_id, fn($query) => $query->where('province_id', $agent->province_id))
            ->orderBy('titre')
            ->get(['id', 'titre', 'annee', 'trimestre', 'niveau_administratif'])
            ->map(function ($activite) {
                return [
                    'id' => $activite->id,
                    'titre' => $activite->titre,
                    'annee' => $activite->annee,
                    'trimestre' => $activite->trimestre,
                    'niveau_administratif' => $activite->niveau_administratif,
                ];
            });

        return $this->success([
            'agents' => $agentsDuDepartement,
            'activites_pta' => $activitesPta,
            'source_emetteurs' => [
                ['value' => 'directeur', 'label' => 'Directeur'],
                ['value' => 'sen', 'label' => 'SEN'],
                ['value' => 'sep', 'label' => 'SEP'],
                ['value' => 'sel', 'label' => 'SEL'],
                ['value' => 'autre', 'label' => 'Autre'],
            ],
        ]);
    }

    /**
     * Store a newly created tache.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Directeur')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'agent_id'      => 'required|exists:agents,id',
            'titre'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'source_type'   => 'required|in:pta,hors_pta',
            'source_emetteur' => 'required|in:directeur,assistant_departement,sen,sep,sel,autre',
            'activite_plan_id' => 'nullable|required_if:source_type,pta|exists:activite_plans,id',
            'priorite'      => 'required|in:normale,haute,urgente',
            'date_echeance' => 'nullable|date',
            'date_tache'    => 'nullable|date',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
        ]);

        $agent = $user->agent;
        $targetAgent = Agent::findOrFail($validated['agent_id']);

        if ($targetAgent->departement_id !== $agent->departement_id) {
            return response()->json([
                'message' => 'Vous ne pouvez assigner des taches qu\'aux agents de votre departement.',
            ], 403);
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

        if (!$agent) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $isCreateur   = $tache->createur_id === $agent->id;
        $isAssigne    = $tache->agent_id    === $agent->id;
        $isDeptManager = false;

        if (!$isCreateur && !$isAssigne && $agent->departement_id) {
            $role = strtolower($user->role?->nom_role ?? '');
            $isDept = in_array($role, ['directeur', 'directeur de département', 'assistant', 'assistant de département'])
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
     * Update the status of a tache (assigned agent only).
     */
    public function updateStatut(Request $request, Tache $tache): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent || $tache->agent_id !== $agent->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'statut'  => 'required|in:en_cours,terminee',
            'contenu' => 'required|string|max:1000',
            'pourcentage' => 'required|integer|min:0|max:100',
            'document' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
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
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::uuid() . ($extension ? '.' . $extension : '');
        $directory = public_path('uploads/taches');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $uploadedFile->move($directory, $filename);

        return TacheDocument::create([
            'tache_id' => $tache->id,
            'agent_id' => $agent->id,
            'tache_commentaire_id' => $commentaire?->id,
            'type_document' => $typeDocument,
            'titre' => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'fichier' => 'uploads/taches/' . $filename,
            'nom_original' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'taille' => $uploadedFile->getSize(),
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

        if (!$user->hasRole('Directeur') && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $agentId = $request->input('agent_id');
        $annee = $request->input('annee', now()->year);

        $query = Tache::query()->whereYear('created_at', $annee);

        if ($user->hasRole('Directeur') && !$user->hasAdminAccess()) {
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
