<?php

namespace App\Http\Controllers\RH;

use App\Events\CongeApproved;
use App\Events\CongeRequested;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayPlanning;
use App\Models\Agent;
use App\Models\AgentStatus;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
        if ($scope->isProvincialRh($user)) {
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
    public function show(Holiday $holiday)
    {
        $holiday->load([
            'agent.departement',
            'holidayPlanning',
            'demandePar',
            'approuvePar',
            'refusePar'
        ]);

        return response()->json($holiday);
    }

    /**
     * Création d'une demande de congé
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'type_conge' => 'required|in:annuel,maladie,maternite,paternite,urgence,special',
            'motif' => 'required|string|max:1000',
            'document_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'holiday_planning_id' => 'nullable|exists:holiday_plannings,id'
        ]);

        // Vérifier les conflits de dates
        $agent = Agent::find($validated['agent_id']);
        $dateDebut = Carbon::parse($validated['date_debut']);
        $dateFin = Carbon::parse($validated['date_fin']);

        if (Holiday::hasConflict($validated['agent_id'], $dateDebut, $dateFin)) {
            return response()->json([
                'message' => 'Conflit de dates : l\'agent a déjà un congé approuvé sur cette période'
            ], 422);
        }

        // Gérer le document médical si fourni
        if ($request->hasFile('document_medical')) {
            $validated['document_medical'] = $request->file('document_medical')
                ->store('documents/medical', 'public');
        }

        $validated['demande_par'] = auth()->user()->agent->id;
        $validated['statut_demande'] = 'en_attente';

        $holiday = Holiday::create($validated);

        // Créer un nouveau statut agent si approuvé automatiquement
        // (par exemple pour les congés d'urgence par un RH)
        if (auth()->user()->agent->hasRole(['RH National', 'RH Provincial']) &&
            in_array($validated['type_conge'], ['urgence', 'maladie'])) {

            $holiday->approve(auth()->user()->agent);

            AgentStatus::setNewStatus($validated['agent_id'], [
                'statut' => 'en_conge',
                'date_debut' => $dateDebut,
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
     * Approbation d'un congé
     */
    public function approve(Holiday $holiday)
    {
        if ($holiday->statut_demande !== 'en_attente') {
            return response()->json([
                'message' => 'Seuls les congés en attente peuvent être approuvés'
            ], 422);
        }

        // Vérifications de permission selon la hiérarchie
        $user = auth()->user()->agent;
        $canApprove = false;

        if ($user->hasRole(['RH National', 'SEN'])) {
            $canApprove = true;
        } elseif ($user->hasRole('RH Provincial')) {
            // RH Provincial peut approuver les congés dans sa province
            $canApprove = $holiday->agent->province_id === $user->province_id;
        }

        if (!$canApprove) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour approuver ce congé'
            ], 403);
        }

        $holiday->approve($user);

        // Fire event for PTA conflict re-check and notifications
        CongeApproved::dispatch($holiday);

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

        $holiday->refuse(auth()->user()->agent, $validated['motif_refus']);

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

        // Seul le demandeur ou un RH peut annuler
        if ($holiday->demande_par !== $user->id &&
            !$user->hasRole(['RH National', 'RH Provincial'])) {
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
        if ($holiday->statut_demande !== 'en_attente') {
            return response()->json([
                'message' => 'Seuls les congés en attente peuvent être modifiés'
            ], 422);
        }

        $user = auth()->user()->agent;

        // Seul le demandeur ou un RH peut modifier
        if ($holiday->demande_par !== $user->id &&
            !$user->hasRole(['RH National', 'RH Provincial'])) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour modifier ce congé'
            ], 403);
        }

        $validated = $request->validate([
            'date_debut' => 'sometimes|date|after_or_equal:today',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'type_conge' => 'sometimes|in:annuel,maladie,maternite,paternite,urgence,special',
            'motif' => 'sometimes|string|max:1000',
            'document_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        // Vérifier les conflits si les dates changent
        if (isset($validated['date_debut']) || isset($validated['date_fin'])) {
            $dateDebut = Carbon::parse($validated['date_debut'] ?? $holiday->date_debut);
            $dateFin = Carbon::parse($validated['date_fin'] ?? $holiday->date_fin);

            if (Holiday::hasConflict($holiday->agent_id, $dateDebut, $dateFin, $holiday->id)) {
                return response()->json([
                    'message' => 'Conflit de dates : l\'agent a déjà un congé approuvé sur cette période'
                ], 422);
            }
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

        return response()->json([
            'message' => 'Congé mis à jour avec succès',
            'holiday' => $holiday->fresh()
        ]);
    }

    /**
     * Statistiques des congés par agent
     */
    public function agentStats(Agent $agent, Request $request)
    {
        $year = $request->get('year', date('Y'));

        $stats = [
            'total_conges' => $agent->holidays()->forYear($year)->count(),
            'jours_total' => $agent->holidays()->forYear($year)->approved()->sum('nombre_jours'),
            'conges_approuves' => $agent->holidays()->forYear($year)->approved()->count(),
            'conges_en_attente' => $agent->holidays()->forYear($year)->pending()->count(),
            'jours_restants' => 30 - $agent->holidays()->forYear($year)->approved()->sum('nombre_jours'),
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
}
