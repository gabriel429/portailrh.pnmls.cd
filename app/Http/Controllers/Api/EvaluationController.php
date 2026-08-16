<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\EvaluationCritere;
use App\Services\PerformanceScoreService;
use App\Services\RoleService;
use App\Services\UserDataScope;
use App\Support\RoleGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    private function roleService(): RoleService
    {
        return app(RoleService::class);
    }

    private function recordAudit(string $action, string $tableName, $recordId, ?array $before = null, ?array $after = null): void
    {
        $user = request()->user();
        if (!$user) {
            return;
        }

        $sensitive = ['password', 'remember_token'];
        if ($before) {
            $before = array_diff_key($before, array_flip($sensitive));
        }
        if ($after) {
            $after = array_diff_key($after, array_flip($sensitive));
        }

        try {
            AuditLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'donnees_avant' => $before,
                'donnees_apres' => $after,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Audit log failed: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $query = Evaluation::with(['agent:id,nom,prenom,postnom,matricule_etat,organe,poste_actuel', 'evaluateur:id,nom,prenom', 'valideePar:id,nom,prenom']);
        $this->scopeService()->applyEvaluationScope($query, $request->user());

        if ($agentId = $request->query('agent_id')) {
            $query->where('agent_id', $agentId);
        }

        if ($annee = $request->query('annee')) {
            $query->where('periode_annee', $annee);
        }

        if ($request->query('trimestre') !== null && $request->query('trimestre') !== '') {
            $query->where('periode_trimestre', $request->query('trimestre'));
        }

        if ($statut = $request->query('statut')) {
            $query->where('statut', $statut);
        }

        $perPage = max(10, min((int) $request->query('per_page', 25), 100));
        $paginator = $query->orderByDesc('periode_annee')->orderByDesc('periode_trimestre')->orderByDesc('created_at')->paginate($perPage);

        return $this->success($paginator->items(), [], [
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function criteres()
    {
        return $this->success(EvaluationCritere::actifs()->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$this->roleService()->hasTacheManagerRole($user) && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'periode_type' => 'required|in:trimestriel,annuel',
            'periode_annee' => 'required|integer|min:2020|max:2100',
            'periode_trimestre' => 'required_if:periode_type,trimestriel|integer|min:0|max:4',
            'commentaire_general' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.evaluation_critere_id' => 'required|exists:evaluation_criteres,id',
            'details.*.note' => 'required|numeric|min:0|max:5',
            'details.*.commentaire' => 'nullable|string',
        ]);

        $agentQuery = Agent::query()->whereKey($validated['agent_id']);
        $this->scopeService()->applyAgentScope($agentQuery, $user);
        $agent = $agentQuery->first();

        if (!$agent) {
            return response()->json(['message' => "Agent hors de votre périmètre."], 403);
        }

        $trimestre = $validated['periode_type'] === 'annuel' ? 0 : (int) $validated['periode_trimestre'];

        $exists = Evaluation::where('agent_id', $agent->id)
            ->where('periode_annee', $validated['periode_annee'])
            ->where('periode_trimestre', $trimestre)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Une évaluation existe déjà pour cet agent sur cette période.'], 422);
        }

        $evaluation = DB::transaction(function () use ($validated, $agent, $user, $trimestre) {
            $evaluation = Evaluation::create([
                'agent_id' => $agent->id,
                'evaluateur_id' => $user->agent?->id,
                'organe' => $agent->organe,
                'province_id' => $agent->province_id,
                'departement_id' => $agent->departement_id,
                'periode_type' => $validated['periode_type'],
                'periode_annee' => $validated['periode_annee'],
                'periode_trimestre' => $trimestre,
                'statut' => 'brouillon',
                'commentaire_general' => $validated['commentaire_general'] ?? null,
            ]);

            $this->saveDetails($evaluation, $validated['details']);

            return $evaluation;
        });

        $this->recordAudit('CREATE', 'evaluations', $evaluation->id, null, $evaluation->fresh()->toArray());

        return $this->success($evaluation->load('details.critere'), [], [], 201);
    }

    public function show(Request $request, Evaluation $evaluation)
    {
        if (!$this->canAccessEvaluation($request->user(), $evaluation)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return $this->success($evaluation->load(['agent', 'evaluateur:id,nom,prenom', 'details.critere', 'valideePar:id,nom,prenom']));
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        $user = $request->user();

        if (!$this->canEditEvaluation($user, $evaluation)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'commentaire_general' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.evaluation_critere_id' => 'required|exists:evaluation_criteres,id',
            'details.*.note' => 'required|numeric|min:0|max:5',
            'details.*.commentaire' => 'nullable|string',
        ]);

        $before = $evaluation->toArray();

        DB::transaction(function () use ($evaluation, $validated) {
            $evaluation->details()->delete();
            $this->saveDetails($evaluation, $validated['details']);

            $evaluation->update([
                'commentaire_general' => $validated['commentaire_general'] ?? null,
                'statut' => $evaluation->statut === 'rejetee' ? 'brouillon' : $evaluation->statut,
                'motif_rejet' => $evaluation->statut === 'rejetee' ? null : $evaluation->motif_rejet,
            ]);
        });

        $this->recordAudit('UPDATE', 'evaluations', $evaluation->id, $before, $evaluation->fresh()->toArray());

        return $this->success($evaluation->fresh()->load('details.critere'));
    }

    public function destroy(Request $request, Evaluation $evaluation)
    {
        $user = $request->user();

        if (!$this->canEditEvaluation($user, $evaluation)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $before = $evaluation->toArray();
        $evaluation->delete();
        $this->recordAudit('DELETE', 'evaluations', $evaluation->id, $before);

        return response()->json(['message' => 'Évaluation supprimée.']);
    }

    public function submit(Request $request, Evaluation $evaluation)
    {
        $user = $request->user();

        if (!$this->canEditEvaluation($user, $evaluation)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($evaluation->statut !== 'brouillon') {
            return response()->json(['message' => 'Seule une évaluation en brouillon peut être soumise.'], 422);
        }

        $before = $evaluation->toArray();

        $agent = $evaluation->agent;
        $scores = app(PerformanceScoreService::class)->computeAutoScore($agent, $evaluation->periode_annee, $evaluation->periode_trimestre);
        $scoreGlobal = app(PerformanceScoreService::class)->combine($scores['score_auto'], $evaluation->score_manuel);

        $evaluation->update([
            'taux_completion_taches' => $scores['taux_completion_taches'],
            'taux_assiduite' => $scores['taux_assiduite'],
            'score_auto' => $scores['score_auto'],
            'score_global' => $scoreGlobal,
            'statut' => 'soumise',
            'soumise_par_id' => $user->agent?->id,
            'soumise_le' => now(),
        ]);

        $this->recordAudit('UPDATE', 'evaluations', $evaluation->id, $before, $evaluation->fresh()->toArray());

        return $this->success($evaluation->fresh());
    }

    public function validateEvaluation(Request $request, Evaluation $evaluation)
    {
        if ($evaluation->statut !== 'soumise') {
            return response()->json(['message' => 'Seule une évaluation soumise peut être validée.'], 422);
        }

        $before = $evaluation->toArray();

        $evaluation->update([
            'statut' => 'validee',
            'validee_par_id' => $request->user()->agent?->id,
            'validee_le' => now(),
        ]);

        $this->recordAudit('UPDATE', 'evaluations', $evaluation->id, $before, $evaluation->fresh()->toArray());

        return $this->success($evaluation->fresh());
    }

    public function reject(Request $request, Evaluation $evaluation)
    {
        if ($evaluation->statut !== 'soumise') {
            return response()->json(['message' => 'Seule une évaluation soumise peut être rejetée.'], 422);
        }

        $validated = $request->validate([
            'motif_rejet' => 'required|string|min:5',
        ]);

        $before = $evaluation->toArray();

        $evaluation->update([
            'statut' => 'rejetee',
            'validee_par_id' => $request->user()->agent?->id,
            'validee_le' => now(),
            'motif_rejet' => $validated['motif_rejet'],
        ]);

        $this->recordAudit('UPDATE', 'evaluations', $evaluation->id, $before, $evaluation->fresh()->toArray());

        return $this->success($evaluation->fresh());
    }

    private function saveDetails(Evaluation $evaluation, array $details): void
    {
        $criteres = EvaluationCritere::actifs()->get()->keyBy('id');
        $weightedSum = 0;
        $weightTotal = 0;

        foreach ($details as $detail) {
            $critere = $criteres->get($detail['evaluation_critere_id']);
            if (!$critere) {
                continue;
            }

            $evaluation->details()->create([
                'evaluation_critere_id' => $critere->id,
                'note' => $detail['note'],
                'poids_utilise' => $critere->poids,
                'commentaire' => $detail['commentaire'] ?? null,
            ]);

            $weightedSum += ($detail['note'] / 5) * 100 * $critere->poids;
            $weightTotal += $critere->poids;
        }

        $scoreManuel = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null;
        $evaluation->update(['score_manuel' => $scoreManuel]);
    }

    private function canAccessEvaluation($user, Evaluation $evaluation): bool
    {
        if ($this->scopeService()->hasGlobalAdminAccess($user)) {
            return true;
        }

        $agentId = $user?->agent?->id;
        if (!$agentId) {
            return false;
        }

        return (int) $evaluation->agent_id === (int) $agentId
            || (int) $evaluation->evaluateur_id === (int) $agentId
            || $this->scopeService()->canAccessAgent($user, $evaluation->agent, false);
    }

    private function canEditEvaluation($user, Evaluation $evaluation): bool
    {
        if (!in_array($evaluation->statut, ['brouillon', 'rejetee'], true)) {
            return false;
        }

        if ($user?->hasAdminAccess()) {
            return true;
        }

        return (int) ($user?->agent?->id ?? 0) === (int) $evaluation->evaluateur_id;
    }
}
