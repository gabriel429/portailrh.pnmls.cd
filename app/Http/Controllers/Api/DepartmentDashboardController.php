<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Pointage;
use App\Models\Tache;
use App\Models\Holiday;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentDashboardController extends ApiController
{
    public function index(Request $request)
    {
        $user     = $request->user();
        // Le département est sur le profil agent (agents.departement_id), pas sur users
        $deptId   = $user->agent?->departement_id ?? $user->departement_id;

        if (!$deptId) {
            return response()->json(['message' => 'Aucun département associé à votre compte.'], 403);
        }

        $now          = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // ─── Département info ───────────────────────────────────
        $department = Department::with('province:id,nom')->find($deptId);

        // Seuls les départements fonctionnels (pris en charge) ont un dashboard.
        // Les autres sont conservés pour l'affectation et l'historisation uniquement.
        if (!$department || !$department->pris_en_charge) {
            return response()->json([
                'message' => 'Ce département n\'est pas encore pris en charge par le système.',
                'code'    => 'DEPT_NOT_FUNCTIONAL',
            ], 403);
        }

        // ─── Agents ────────────────────────────────────────────
        $agentsActifs = Agent::where('departement_id', $deptId)->actifs()->count();
        $agentsTotal  = Agent::where('departement_id', $deptId)->count();
        $agentIds     = Agent::where('departement_id', $deptId)->pluck('id');

        // ─── Tâches ────────────────────────────────────────────
        $tachesEnCours  = Tache::whereIn('agent_id', $agentIds)
            ->whereIn('statut', ['nouvelle', 'en_cours'])->count();
        $tachesTermine  = Tache::whereIn('agent_id', $agentIds)
            ->where('statut', 'terminee')->count();
        $tachesOverdue  = Tache::whereIn('agent_id', $agentIds)
            ->whereNotIn('statut', ['terminee'])
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $now->toDateString())
            ->count();

        // ─── Demandes ──────────────────────────────────────────
        $requestsPending = RequestModel::whereIn('agent_id', $agentIds)
            ->enAttente()->count();

        // ─── Présence ──────────────────────────────────────────
        $totalActive   = max($agentsActifs, 1);
        $todayPresent  = Pointage::byDate($now->toDateString())
            ->whereIn('agent_id', $agentIds)
            ->whereNotNull('heure_entree')
            ->distinct('agent_id')->count('agent_id');
        $todayRate     = round(($todayPresent / $totalActive) * 100, 1);

        // Taux mensuel moyen
        $monthlyPointages = Pointage::betweenDates(
            $startOfMonth->toDateString(),
            $now->toDateString()
        )->whereIn('agent_id', $agentIds)
         ->whereNotNull('heure_entree')
         ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
         ->groupBy('date_pointage')->get();

        $monthlyRate = 0;
        if ($monthlyPointages->count() > 0) {
            $monthlyRate = round(($monthlyPointages->avg('present') / $totalActive) * 100, 1);
        }

        // ─── Congés actifs ─────────────────────────────────────
        $activeLeaves = Holiday::whereIn('agent_id', $agentIds)
            ->where('statut_demande', 'approuve')
            ->where('date_debut', '<=', $now->toDateString())
            ->where('date_fin',   '>=', $now->toDateString())
            ->count();

        // ─── Performance équipe ────────────────────────────────
        $teamPerf = Agent::where('departement_id', $deptId)
            ->actifs()
            ->with([
                'tachesAssignees' => fn($q) => $q->select('id', 'agent_id', 'statut', 'pourcentage', 'date_echeance'),
            ])
            ->get(['id', 'nom', 'prenom', 'photo', 'fonction'])
            ->map(function ($agent) use ($now) {
                $taches    = $agent->tachesAssignees;
                $total     = $taches->count();
                $done      = $taches->where('statut', 'terminee')->count();
                $inProgress = $taches->whereIn('statut', ['nouvelle', 'en_cours'])->count();
                $overdue   = $taches->whereNotIn('statut', ['terminee'])
                    ->filter(fn($t) => $t->date_echeance && $t->date_echeance->lt($now))
                    ->count();
                $avgPct    = $total > 0 ? round($taches->avg('pourcentage'), 0) : 0;

                return [
                    'id'            => $agent->id,
                    'nom'           => $agent->nom,
                    'prenom'        => $agent->prenom,
                    'photo'         => $agent->photo,
                    'fonction'      => $agent->fonction,
                    'taches_total'  => $total,
                    'taches_done'   => $done,
                    'taches_en_cours' => $inProgress,
                    'taches_overdue'  => $overdue,
                    'avg_completion'  => $avgPct,
                ];
            });

        // ─── Tâches récentes ───────────────────────────────────
        $recentTaches = Tache::whereIn('agent_id', $agentIds)
            ->with('agent:id,nom,prenom')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'titre', 'statut', 'pourcentage', 'date_echeance', 'priorite', 'updated_at']);

        // ─── Demandes en attente ───────────────────────────────
        $pendingRequests = RequestModel::whereIn('agent_id', $agentIds)
            ->enAttente()
            ->with('agent:id,nom,prenom')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'agent_id', 'type', 'description', 'statut', 'created_at']);

        return $this->success([
            'department' => [
                'id'       => $department?->id,
                'nom'      => $department?->nom,
                'code'     => $department?->code,
                'province' => $department?->province?->nom,
            ],
            'agents' => [
                'total'  => $agentsTotal,
                'actifs' => $agentsActifs,
            ],
            'taches' => [
                'en_cours'  => $tachesEnCours,
                'terminees' => $tachesTermine,
                'overdue'   => $tachesOverdue,
            ],
            'requests' => [
                'en_attente' => $requestsPending,
            ],
            'attendance' => [
                'today_present'  => $todayPresent,
                'today_rate'     => $todayRate,
                'monthly_rate'   => $monthlyRate,
                'total_actifs'   => $agentsActifs,
            ],
            'conges' => [
                'actifs' => $activeLeaves,
            ],
            'team_performance' => $teamPerf,
            'recent_taches'    => $recentTaches,
            'pending_requests' => $pendingRequests,
        ]);
    }
}
