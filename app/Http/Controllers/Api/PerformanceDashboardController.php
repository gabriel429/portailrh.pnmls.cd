<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\Evaluation;
use App\Services\PerformanceScoreService;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceDashboardController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    private function scoreService(): PerformanceScoreService
    {
        return app(PerformanceScoreService::class);
    }

    private function organeNameFromCode(?string $code): ?string
    {
        return [
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
        ][strtoupper((string) $code)] ?? null;
    }

    private function structureCodeFromOrgane(?string $organe): string
    {
        $normalized = $this->normalizedText($organe);

        if (str_contains($normalized, 'local')) {
            return 'SEL';
        }

        if (str_contains($normalized, 'provincial')) {
            return 'SEP';
        }

        if (str_contains($normalized, 'national')) {
            return 'SEN';
        }

        return 'AUTRE';
    }

    private function structureLabelFromCode(string $code): string
    {
        return match (strtoupper($code)) {
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariats Exécutifs Locaux',
            default => 'Autres agents',
        };
    }

    private function normalizedText(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function baseAgentQuery(Request $request)
    {
        $user = $request->user();
        $query = Agent::query()->actifs();
        $this->scopeService()->applyAgentScope($query, $user);

        if ($organe = $request->query('organe')) {
            if ($organe !== 'tous') {
                $organeNom = $this->organeNameFromCode($organe) ?? $organe;
                $query->where('organe', $organeNom);
            }
        }

        if ($provinceId = $request->query('province_id')) {
            $query->where('province_id', $provinceId);
        }

        if ($departementId = $request->query('departement_id')) {
            $query->where('departement_id', $departementId);
        }

        if ($search = $request->query('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('prenom', 'like', $term)
                    ->orWhere('postnom', 'like', $term)
                    ->orWhere('matricule_etat', 'like', $term);
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $annee = (int) $request->query('annee', now()->year);
        $trimestre = $request->query('trimestre');
        $trimestre = ($trimestre === null || $trimestre === '' || $trimestre === 'annuel') ? 0 : (int) $trimestre;
        $statutFiltre = $request->query('statut_evaluation', 'tous');

        $agentQuery = $this->baseAgentQuery($request);

        if ($statutFiltre === 'non_evalue') {
            $agentQuery->whereDoesntHave('evaluations', function ($q) use ($annee, $trimestre) {
                $q->where('periode_annee', $annee)->where('periode_trimestre', $trimestre);
            });
        } elseif ($statutFiltre !== 'tous' && $statutFiltre) {
            $agentQuery->whereHas('evaluations', function ($q) use ($annee, $trimestre, $statutFiltre) {
                $q->where('periode_annee', $annee)->where('periode_trimestre', $trimestre)->where('statut', $statutFiltre);
            });
        }

        $perPage = max(10, min((int) $request->query('per_page', 25), 100));
        $paginator = (clone $agentQuery)
            ->with(['departement:id,nom,code', 'province:id,nom', 'evaluations' => function ($q) use ($annee, $trimestre) {
                $q->where('periode_annee', $annee)->where('periode_trimestre', $trimestre);
            }])
            ->orderInstitutionally()
            ->paginate($perPage);

        $rows = $paginator->getCollection()->map(function (Agent $agent) use ($annee, $trimestre) {
            return $this->agentRow($agent, $annee, $trimestre);
        });

        $kpis = $this->buildKpis((clone $agentQuery)->pluck('id'), $annee, $trimestre);
        $parOrgane = $this->buildParOrgane($request, $annee, $trimestre);

        return $this->success($rows->values(), [], [
            'kpis' => $kpis,
            'par_organe' => $parOrgane,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function agentDetail(Request $request, Agent $agent)
    {
        if (!$this->scopeService()->canAccessAgent($request->user(), $agent, true)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $now = now();
        $evaluations = $agent->evaluations()
            ->with(['details.critere', 'evaluateur:id,nom,prenom', 'valideePar:id,nom,prenom'])
            ->orderByDesc('periode_annee')
            ->orderByDesc('periode_trimestre')
            ->get();

        $live = $this->scoreService()->computeAutoScore($agent, $now->year, 0);

        return $this->success([
            'agent' => $agent->only(['id', 'nom', 'prenom', 'postnom', 'matricule_etat', 'organe', 'poste_actuel', 'fonction']),
            'indicateurs_courants' => $live,
            'evaluations' => $evaluations,
        ]);
    }

    private function agentRow(Agent $agent, int $annee, int $trimestre): array
    {
        $evaluation = $agent->evaluations->first();

        if ($evaluation) {
            $scoreAuto = $evaluation->score_auto;
            $scoreManuel = $evaluation->score_manuel;
            $scoreGlobal = $evaluation->score_global;
            $statut = $evaluation->statut;
            $derniereEvaluationLe = $evaluation->updated_at;
        } else {
            $auto = $this->scoreService()->computeAutoScore($agent, $annee, $trimestre);
            $scoreAuto = $auto['score_auto'];
            $scoreManuel = null;
            $scoreGlobal = null;
            $statut = 'non_evalue';
            $derniereEvaluationLe = null;
        }

        $structureCode = $this->structureCodeFromOrgane($agent->organe);

        return [
            'id' => $agent->id,
            'nom' => $agent->nom,
            'prenom' => $agent->prenom,
            'postnom' => $agent->postnom,
            'matricule_etat' => $agent->matricule_etat,
            'poste_actuel' => $agent->poste_actuel,
            'organe' => $agent->organe,
            'structure_code' => $structureCode,
            'structure_label' => $this->structureLabelFromCode($structureCode),
            'province' => $agent->province?->nom,
            'departement' => $agent->departement?->nom,
            'score_auto' => $scoreAuto,
            'score_manuel' => $scoreManuel,
            'score_global' => $scoreGlobal,
            'statut_evaluation' => $statut,
            'statut_evaluation_label' => Evaluation::STATUTS[$statut] ?? 'Non évalué',
            'derniere_evaluation_le' => $derniereEvaluationLe,
            'evaluation_id' => $evaluation?->id,
        ];
    }

    private function buildKpis($agentIds, int $annee, int $trimestre): array
    {
        $agentIds = $agentIds->values();
        $nbAgents = $agentIds->count();

        $evaluations = Evaluation::whereIn('agent_id', $agentIds)
            ->where('periode_annee', $annee)
            ->where('periode_trimestre', $trimestre)
            ->get();

        return [
            'nb_agents' => $nbAgents,
            'nb_evalues' => $evaluations->count(),
            'nb_en_attente_validation' => $evaluations->where('statut', 'soumise')->count(),
            'score_global_moyen' => round($evaluations->whereNotNull('score_global')->avg('score_global') ?? 0, 1),
            'taux_completion_moyen' => round($evaluations->whereNotNull('taux_completion_taches')->avg('taux_completion_taches') ?? 0, 1),
            'taux_assiduite_moyen' => round($evaluations->whereNotNull('taux_assiduite')->avg('taux_assiduite') ?? 0, 1),
        ];
    }

    private function buildParOrgane(Request $request, int $annee, int $trimestre): array
    {
        $result = [];

        foreach (['SEN', 'SEP', 'SEL'] as $code) {
            $organeNom = $this->organeNameFromCode($code);
            $agentQuery = $this->baseAgentQuery($request)->where('organe', $organeNom);
            $ids = $agentQuery->pluck('id');

            $evaluations = Evaluation::whereIn('agent_id', $ids)
                ->where('periode_annee', $annee)
                ->where('periode_trimestre', $trimestre)
                ->get();

            $result[] = [
                'code' => $code,
                'label' => $this->structureLabelFromCode($code),
                'nb_agents' => $ids->count(),
                'nb_evalues' => $evaluations->count(),
                'score_global_moyen' => round($evaluations->whereNotNull('score_global')->avg('score_global') ?? 0, 1),
            ];
        }

        return $result;
    }
}
