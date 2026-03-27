<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\AgentStatus;
use App\Models\Agent;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AgentStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Liste des statuts des agents
     */
    public function index(Request $request)
    {
        $query = AgentStatus::with(['agent.departement', 'createdBy', 'approvedBy']);

        // Filtres
        if ($request->filled('agent_id')) {
            $query->forAgent($request->agent_id);
        }

        if ($request->filled('statut')) {
            $query->byStatut($request->statut);
        }

        if ($request->filled('actuel')) {
            $query->actuel();
        }

        if ($request->filled('department_id')) {
            $query->whereHas('agent', function ($q) use ($request) {
                $q->where('departement_id', $request->department_id);
            });
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $start = Carbon::parse($request->date_debut);
            $end = Carbon::parse($request->date_fin);
            $query->activeBetween($start, $end);
        }

        $statuses = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($statuses);
    }

    /**
     * Statuts actuels de tous les agents
     */
    public function current(Request $request)
    {
        $query = Agent::with([
            'departement',
            'agentStatuses' => function ($q) {
                $q->where('actuel', true)->latest();
            }
        ]);

        // Filtres
        if ($request->filled('department_id')) {
            $query->where('departement_id', $request->department_id);
        }

        if ($request->filled('statut')) {
            $query->whereHas('agentStatuses', function ($q) use ($request) {
                $q->where('actuel', true)->where('statut', $request->statut);
            });
        }

        $agents = $query->actifs()->get();

        // Formaté pour un affichage en tableau de bord
        $grouped = $agents->mapToGroups(function ($agent) {
            $currentStatus = $agent->agentStatuses->first();
            $statut = $currentStatus ? $currentStatus->statut : 'disponible';

            return [$statut => [
                'id' => $agent->id,
                'nom_complet' => $agent->nom_complet,
                'departement' => $agent->departement->nom ?? 'Non défini',
                'statut_details' => $currentStatus ? [
                    'date_debut' => $currentStatus->date_debut,
                    'date_fin' => $currentStatus->date_fin,
                    'motif' => $currentStatus->motif,
                    'duree' => $currentStatus->duree
                ] : null
            ]];
        });

        return response()->json($grouped);
    }

    /**
     * Détails d'un statut agent
     */
    public function show(AgentStatus $agentStatus)
    {
        $agentStatus->load([
            'agent.departement',
            'createdBy',
            'approvedBy'
        ]);

        return response()->json($agentStatus);
    }

    /**
     * Création/Modification d'un statut agent
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'statut' => 'required|in:disponible,en_conge,en_mission,suspendu,en_formation',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'motif' => 'required|string|max:255',
            'commentaire' => 'nullable|string|max:1000',
            'document_joint' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        // Vérifications de permission
        $user = auth()->user()->agent;
        $agent = Agent::find($validated['agent_id']);

        $canModify = false;

        if ($user->hasRole(['RH National', 'SEN'])) {
            $canModify = true;
        } elseif ($user->hasRole('RH Provincial')) {
            $canModify = $agent->province_id === $user->province_id;
        } elseif ($user->hasRole(['Secrétaire', 'Assistant'])) {
            $canModify = $agent->departement_id === $user->departement_id;
        }

        if (!$canModify) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour modifier le statut de cet agent'
            ], 403);
        }

        // Gérer le document joint
        if ($request->hasFile('document_joint')) {
            $validated['document_joint'] = $request->file('document_joint')
                ->store('documents/statuts', 'public');
        }

        $validated['created_by'] = $user->id;

        // Si c'est un RH qui fait la modification, approuver automatiquement
        if ($user->hasRole(['RH National', 'RH Provincial'])) {
            $validated['approved_by'] = $user->id;
            $validated['approved_at'] = now();
        }

        // Créer le nouveau statut
        $newStatus = AgentStatus::setNewStatus($validated['agent_id'], $validated);

        return response()->json([
            'message' => 'Statut agent mis à jour avec succès',
            'status' => $newStatus->load(['agent', 'createdBy', 'approvedBy'])
        ], 201);
    }

    /**
     * Approbation d'un changement de statut
     */
    public function approve(AgentStatus $agentStatus)
    {
        if ($agentStatus->approved_by) {
            return response()->json([
                'message' => 'Ce changement de statut est déjà approuvé'
            ], 422);
        }

        $user = auth()->user()->agent;

        if (!$user->hasRole(['RH National', 'RH Provincial'])) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour approuver ce changement'
            ], 403);
        }

        $agentStatus->approve($user);

        return response()->json([
            'message' => 'Changement de statut approuvé',
            'status' => $agentStatus->fresh(['approvedBy'])
        ]);
    }

    /**
     * Historique des statuts d'un agent
     */
    public function history(Agent $agent, Request $request)
    {
        $year = $request->get('year');
        $limit = $request->get('limit', 50);

        $query = $agent->agentStatuses()
            ->with(['createdBy', 'approvedBy'])
            ->orderBy('created_at', 'desc');

        if ($year) {
            $query->whereYear('date_debut', $year);
        }

        $history = $query->limit($limit)->get();

        return response()->json([
            'agent' => $agent->only(['id', 'nom_complet']),
            'history' => $history,
            'current_status' => $agent->currentStatus()
        ]);
    }

    /**
     * Prolonger un statut
     */
    public function extend(Request $request, AgentStatus $agentStatus)
    {
        if (!$agentStatus->actuel) {
            return response()->json([
                'message' => 'Seul le statut actuel peut être prolongé'
            ], 422);
        }

        $validated = $request->validate([
            'nouvelle_date_fin' => 'required|date|after:' . ($agentStatus->date_fin ?: $agentStatus->date_debut)
        ]);

        $user = auth()->user()->agent;

        // Vérifier les permissions
        if (!$user->hasRole(['RH National', 'RH Provincial']) &&
            $agentStatus->created_by !== $user->id) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour prolonger ce statut'
            ], 403);
        }

        $newEndDate = Carbon::parse($validated['nouvelle_date_fin']);
        $agentStatus->extend($newEndDate);

        return response()->json([
            'message' => 'Statut prolongé avec succès',
            'status' => $agentStatus->fresh()
        ]);
    }

    /**
     * Statistiques des statuts
     */
    public function statistics(Request $request)
    {
        $departmentId = $request->get('department_id');
        $year = $request->get('year', date('Y'));

        $baseQuery = AgentStatus::whereYear('created_at', $year);

        if ($departmentId) {
            $baseQuery->whereHas('agent', function ($q) use ($departmentId) {
                $q->where('departement_id', $departmentId);
            });
        }

        $stats = [
            'total_changements' => (clone $baseQuery)->count(),
            'par_statut' => (clone $baseQuery)
                ->selectRaw('statut, COUNT(*) as count')
                ->groupBy('statut')
                ->pluck('count', 'statut'),
            'par_mois' => (clone $baseQuery)
                ->selectRaw('MONTH(created_at) as mois, COUNT(*) as count')
                ->groupBy('mois')
                ->orderBy('mois')
                ->pluck('count', 'mois'),
            'en_attente_approbation' => AgentStatus::whereNull('approved_by')->count(),
            'duree_moyenne_par_statut' => AgentStatus::selectRaw('
                    statut,
                    AVG(DATEDIFF(
                        COALESCE(date_fin, NOW()),
                        date_debut
                    )) as duree_moyenne
                ')
                ->whereYear('date_debut', $year)
                ->whereNotNull('date_fin')
                ->groupBy('statut')
                ->pluck('duree_moyenne', 'statut')
        ];

        // Statuts actuels par département
        if (!$departmentId) {
            $stats['par_departement'] = Agent::with(['departement', 'agentStatuses' => function ($q) {
                $q->where('actuel', true);
            }])
                ->actifs()
                ->get()
                ->groupBy('departement.nom')
                ->map(function ($agents, $deptName) {
                    return $agents->map(function ($agent) {
                        $status = $agent->agentStatuses->first();
                        return $status ? $status->statut : 'disponible';
                    })->countBy();
                });
        }

        return response()->json($stats);
    }

    /**
     * Agents disponibles
     */
    public function available(Request $request)
    {
        $date = $request->get('date') ? Carbon::parse($request->date) : Carbon::today();
        $departmentId = $request->get('department_id');

        $query = Agent::whereDoesntHave('agentStatuses', function ($q) use ($date) {
            $q->where('actuel', true)
              ->where('statut', '!=', 'disponible')
              ->where('date_debut', '<=', $date)
              ->where(function ($subQ) use ($date) {
                  $subQ->whereNull('date_fin')
                       ->orWhere('date_fin', '>=', $date);
              });
        });

        if ($departmentId) {
            $query->where('departement_id', $departmentId);
        }

        $agents = $query->with('departement')
            ->actifs()
            ->orderBy('nom')
            ->get();

        return response()->json($agents);
    }

    /**
     * Rapport des absences
     */
    public function absenceReport(Request $request)
    {
        $start = Carbon::parse($request->get('date_debut', now()->startOfMonth()));
        $end = Carbon::parse($request->get('date_fin', now()->endOfMonth()));
        $departmentId = $request->get('department_id');

        $query = AgentStatus::with(['agent.departement'])
            ->whereIn('statut', ['en_conge', 'en_mission', 'suspendu', 'en_formation'])
            ->activeBetween($start, $end);

        if ($departmentId) {
            $query->whereHas('agent', function ($q) use ($departmentId) {
                $q->where('departement_id', $departmentId);
            });
        }

        $absences = $query->get();

        $report = [
            'periode' => [
                'debut' => $start->format('d/m/Y'),
                'fin' => $end->format('d/m/Y')
            ],
            'total_absences' => $absences->count(),
            'jours_total' => $absences->sum('duree'),
            'par_type' => $absences->groupBy('statut')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total_jours' => $items->sum('duree'),
                    'agents' => $items->map(function ($item) {
                        return [
                            'nom' => $item->agent->nom_complet,
                            'departement' => $item->agent->departement->nom ?? 'Non défini',
                            'periode' => $item->date_debut->format('d/m') . ' - ' .
                                        ($item->date_fin ? $item->date_fin->format('d/m') : 'En cours'),
                            'duree' => $item->duree,
                            'motif' => $item->motif
                        ];
                    })
                ];
            })
        ];

        return response()->json($report);
    }
}
