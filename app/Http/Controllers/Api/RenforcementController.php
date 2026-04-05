<?php

namespace App\Http\Controllers\Api;

use App\Events\FormationPlanned;
use App\Http\Controllers\Api\ApiController;
use App\Models\Formation;
use App\Models\FormationBeneficiaire;
use App\Models\Agent;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RenforcementController extends ApiController
{
    /**
     * List formations (scoped by role).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Formation::with(['createur', 'validateur', 'beneficiaires.agent']);

        if (!$user->hasAdminAccess() && !$user->hasAnyPermission(['renforcement.view', 'renforcement.monitor'])) {
            // Agent: see formations where they are beneficiary
            $agentId = $user->agent?->id;
            if ($agentId) {
                $query->whereHas('beneficiaires', fn ($q) => $q->where('agent_id', $agentId));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $formations = $query->latest()->paginate(15);

        return response()->json($formations);
    }

    /**
     * Create a formation (manually or from a request).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasAnyPermission(['renforcement.plan', 'renforcement.process'])) {
            abort(403, 'Permission requise : renforcement.plan');
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectif' => 'nullable|string',
            'lieu' => 'nullable|string|max:255',
            'formateur' => 'nullable|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'budget' => 'nullable|numeric|min:0',
            'request_id' => 'nullable|exists:requests,id',
            'beneficiaires' => 'nullable|array',
            'beneficiaires.*' => 'exists:agents,id',
        ]);

        $validated['statut'] = 'planifiee';
        $validated['created_by'] = $user->agent?->id;

        $formation = Formation::create($validated);

        // Add beneficiaries
        if (!empty($validated['beneficiaires'])) {
            foreach ($validated['beneficiaires'] as $agentId) {
                FormationBeneficiaire::create([
                    'formation_id' => $formation->id,
                    'agent_id' => $agentId,
                    'statut' => 'inscrit',
                ]);
            }
        }

        FormationPlanned::dispatch($formation);

        $formation->load(['createur', 'beneficiaires.agent']);

        return response()->json([
            'message' => 'Formation créée avec succès.',
            'data' => $formation,
        ], 201);
    }

    /**
     * Show formation details with beneficiaries.
     */
    public function show(Formation $formation): JsonResponse
    {
        $formation->load(['createur', 'validateur', 'beneficiaires.agent', 'request']);

        return response()->json(['data' => $formation]);
    }

    /**
     * Update a formation.
     */
    public function update(Request $request, Formation $formation): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasAnyPermission(['renforcement.plan', 'renforcement.process'])) {
            abort(403, 'Permission requise : renforcement.plan');
        }

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'objectif' => 'nullable|string',
            'lieu' => 'nullable|string|max:255',
            'formateur' => 'nullable|string|max:255',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $formation->update($validated);
        $formation->load(['createur', 'validateur', 'beneficiaires.agent']);

        return response()->json([
            'message' => 'Formation mise à jour.',
            'data' => $formation,
        ]);
    }

    /**
     * Validate a formation (Chef Cellule Renforcement).
     */
    public function validate(Request $request, Formation $formation): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasPermission('renforcement.validate')) {
            abort(403, 'Permission requise : renforcement.validate');
        }

        $formation->update([
            'validated_by' => $user->agent?->id,
            'validated_at' => now(),
            'statut' => 'en_cours',
        ]);

        $formation->load(['createur', 'validateur', 'beneficiaires.agent']);

        return response()->json([
            'message' => 'Formation validée.',
            'data' => $formation,
        ]);
    }

    /**
     * Add a beneficiary to a formation.
     */
    public function addBeneficiaire(Request $request, Formation $formation): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasAnyPermission(['renforcement.plan', 'renforcement.process'])) {
            abort(403, 'Permission requise.');
        }

        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
        ]);

        $exists = $formation->beneficiaires()->where('agent_id', $validated['agent_id'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Cet agent est déjà bénéficiaire.'], 422);
        }

        FormationBeneficiaire::create([
            'formation_id' => $formation->id,
            'agent_id' => $validated['agent_id'],
            'statut' => 'inscrit',
        ]);

        return response()->json(['message' => 'Bénéficiaire ajouté.']);
    }

    /**
     * Update beneficiary status (present/absent/certified).
     */
    public function updateStatutBeneficiaire(Request $request, Formation $formation, FormationBeneficiaire $beneficiaire): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasAnyPermission(['renforcement.monitor', 'renforcement.process'])) {
            abort(403, 'Permission requise.');
        }

        $validated = $request->validate([
            'statut' => 'required|in:inscrit,confirme,present,absent,certifie',
            'note_evaluation' => 'nullable|numeric|min:0|max:100',
            'certificat' => 'nullable|string|max:255',
        ]);

        $beneficiaire->update($validated);

        return response()->json(['message' => 'Statut du bénéficiaire mis à jour.']);
    }

    /**
     * Monthly report.
     */
    public function reportMonthly(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasPermission('renforcement.report.monthly')) {
            abort(403, 'Permission requise.');
        }

        $mois = $request->input('mois', now()->month);
        $annee = $request->input('annee', now()->year);

        $formations = Formation::whereMonth('date_debut', $mois)
            ->whereYear('date_debut', $annee)
            ->with(['beneficiaires.agent', 'createur'])
            ->get();

        $stats = [
            'total_formations' => $formations->count(),
            'planifiees' => $formations->where('statut', 'planifiee')->count(),
            'en_cours' => $formations->where('statut', 'en_cours')->count(),
            'terminees' => $formations->where('statut', 'terminee')->count(),
            'total_beneficiaires' => $formations->sum(fn ($f) => $f->beneficiaires->count()),
            'certifies' => $formations->sum(fn ($f) => $f->beneficiaires->where('statut', 'certifie')->count()),
            'budget_total' => $formations->sum('budget'),
        ];

        return response()->json([
            'mois' => $mois,
            'annee' => $annee,
            'stats' => $stats,
            'formations' => $formations,
        ]);
    }

    /**
     * Annual report.
     */
    public function reportAnnual(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAdminAccess() && !$user->hasPermission('renforcement.report.annual')) {
            abort(403, 'Permission requise.');
        }

        $annee = $request->input('annee', now()->year);

        $formations = Formation::whereYear('date_debut', $annee)
            ->with(['beneficiaires.agent', 'createur'])
            ->get();

        $parMois = [];
        for ($m = 1; $m <= 12; $m++) {
            $moisFormations = $formations->filter(fn ($f) => $f->date_debut->month === $m);
            $parMois[$m] = [
                'count' => $moisFormations->count(),
                'beneficiaires' => $moisFormations->sum(fn ($f) => $f->beneficiaires->count()),
                'certifies' => $moisFormations->sum(fn ($f) => $f->beneficiaires->where('statut', 'certifie')->count()),
                'budget' => $moisFormations->sum('budget'),
            ];
        }

        $stats = [
            'total_formations' => $formations->count(),
            'total_beneficiaires' => $formations->sum(fn ($f) => $f->beneficiaires->count()),
            'total_certifies' => $formations->sum(fn ($f) => $f->beneficiaires->where('statut', 'certifie')->count()),
            'budget_total' => $formations->sum('budget'),
            'taux_certification' => $formations->sum(fn ($f) => $f->beneficiaires->count()) > 0
                ? round($formations->sum(fn ($f) => $f->beneficiaires->where('statut', 'certifie')->count()) / $formations->sum(fn ($f) => $f->beneficiaires->count()) * 100, 1)
                : 0,
        ];

        return response()->json([
            'annee' => $annee,
            'stats' => $stats,
            'par_mois' => $parMois,
            'formations' => $formations,
        ]);
    }
}
