<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\HolidayPlanning;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Agent;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HolidayPlanningController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Section ressources humaines,RH National,RH Provincial,SEN');
    }

    /**
     * Liste des plannings de congés avec filtres
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $structureType = $request->get('structure_type');
        $structureId = $request->get('structure_id');

        $query = HolidayPlanning::with(['createdBy', 'validatedBy'])
            ->forYear($year);

        if ($structureType && $structureId) {
            $query->forStructure($structureType, $structureId);
        }

        $plannings = $query->orderBy('nom_structure')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistiques globales pour l'année
        $stats = [
            'total_plannings' => HolidayPlanning::forYear($year)->count(),
            'plannings_valides' => HolidayPlanning::forYear($year)->validated()->count(),
            'total_jours_prevus' => HolidayPlanning::forYear($year)->sum('jours_conge_totaux'),
            'total_jours_utilises' => HolidayPlanning::forYear($year)->sum('jours_utilises'),
        ];

        // Liste des structures pour les filtres
        $departments = Department::orderBy('nom')->get();

        return response()->json([
            'plannings' => $plannings,
            'stats' => $stats,
            'departments' => $departments,
            'year' => $year
        ]);
    }

    /**
     * Affichage détaillé d'un planning
     */
    public function show(HolidayPlanning $holidayPlanning)
    {
        $holidayPlanning->load([
            'createdBy',
            'validatedBy',
            'holidays' => function ($query) {
                $query->with(['agent', 'demandePar', 'approuvePar'])
                      ->orderBy('date_debut');
            }
        ]);

        // Statistiques du planning
        $stats = [
            'conges_approuves' => $holidayPlanning->holidays()
                ->where('statut_demande', 'approuve')->count(),
            'conges_en_attente' => $holidayPlanning->holidays()
                ->where('statut_demande', 'en_attente')->count(),
            'agents_concernes' => $holidayPlanning->holidays()
                ->distinct('agent_id')->count(),
            'taux_utilisation' => $holidayPlanning->pourcentage_utilisation
        ];

        return response()->json([
            'planning' => $holidayPlanning,
            'stats' => $stats
        ]);
    }

    /**
     * Vue calendrier du planning
     */
    public function calendar(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');
        $structureType = $request->get('structure_type');
        $structureId = $request->get('structure_id');

        $start = Carbon::create($year, $month ?: 1, 1)->startOfMonth();
        $end = $month
            ? $start->copy()->endOfMonth()
            : Carbon::create($year, 12, 31)->endOfYear();

        $query = Holiday::with(['agent', 'holidayPlanning'])
            ->approved()
            ->between($start, $end);

        if ($structureType && $structureId) {
            $query->whereHas('holidayPlanning', function ($q) use ($structureType, $structureId) {
                $q->forStructure($structureType, $structureId);
            });
        }

        $holidays = $query->get();

        // Formatage pour le calendrier
        $events = $holidays->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'title' => $holiday->agent->nom_complet,
                'start' => $holiday->date_debut->toDateString(),
                'end' => $holiday->date_fin->addDay()->toDateString(),
                'color' => $holiday->type_conge === 'maladie' ? '#dc3545' :
                          ($holiday->type_conge === 'urgence' ? '#ffc107' : '#007bff'),
                'extendedProps' => [
                    'agent' => $holiday->agent->nom_complet,
                    'type' => $holiday->type_conge_label,
                    'jours' => $holiday->nombre_jours,
                    'structure' => $holiday->holidayPlanning->nom_structure ?? ''
                ]
            ];
        });

        return response()->json([
            'events' => $events,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString()
            ]
        ]);
    }

    /**
     * Création d'un nouveau planning
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee' => 'required|integer|min:2020|max:2030',
            'type_structure' => 'required|in:department,sen,sena,sep,local',
            'structure_id' => 'required|integer',
            'nom_structure' => 'required|string|max:255',
            'jours_conge_totaux' => 'required|integer|min:1|max:50',
            'periods_fermeture' => 'nullable|array',
            'periods_fermeture.*.start' => 'required_with:periods_fermeture|date',
            'periods_fermeture.*.end' => 'required_with:periods_fermeture|date|after_or_equal:periods_fermeture.*.start',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Vérifier qu'un planning n'existe pas déjà pour cette structure/année
        $exists = HolidayPlanning::forYear($validated['annee'])
            ->forStructure($validated['type_structure'], $validated['structure_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Un planning existe déjà pour cette structure en ' . $validated['annee']
            ], 422);
        }

        $validated['created_by'] = auth()->user()->agent->id;

        $planning = HolidayPlanning::create($validated);

        return response()->json([
            'message' => 'Planning créé avec succès',
            'planning' => $planning->load('createdBy')
        ], 201);
    }

    /**
     * Mise à jour d'un planning
     */
    public function update(Request $request, HolidayPlanning $holidayPlanning)
    {
        // Vérifier les permissions
        if ($holidayPlanning->valide && !auth()->user()->agent->hasRole(['RH National', 'SEN'])) {
            return response()->json([
                'message' => 'Impossible de modifier un planning validé'
            ], 403);
        }

        $validated = $request->validate([
            'jours_conge_totaux' => 'sometimes|integer|min:1|max:50',
            'periods_fermeture' => 'nullable|array',
            'periods_fermeture.*.start' => 'required_with:periods_fermeture|date',
            'periods_fermeture.*.end' => 'required_with:periods_fermeture|date|after_or_equal:periods_fermeture.*.start',
            'notes' => 'nullable|string|max:1000'
        ]);

        $holidayPlanning->update($validated);

        return response()->json([
            'message' => 'Planning mis à jour avec succès',
            'planning' => $holidayPlanning->fresh()
        ]);
    }

    /**
     * Validation d'un planning
     */
    public function validate(HolidayPlanning $holidayPlanning)
    {
        if (!auth()->user()->agent->hasRole(['RH National', 'SEN'])) {
            return response()->json([
                'message' => 'Permissions insuffisantes pour valider un planning'
            ], 403);
        }

        if ($holidayPlanning->valide) {
            return response()->json([
                'message' => 'Ce planning est déjà validé'
            ], 422);
        }

        $holidayPlanning->validate(auth()->user()->agent);

        return response()->json([
            'message' => 'Planning validé avec succès',
            'planning' => $holidayPlanning->fresh(['validatedBy'])
        ]);
    }

    /**
     * Statistiques détaillées par structure
     */
    public function statistiques(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $stats = DB::table('holiday_plannings as hp')
            ->select([
                'hp.type_structure',
                'hp.nom_structure',
                'hp.jours_conge_totaux',
                'hp.jours_utilises',
                DB::raw('COUNT(h.id) as total_conges'),
                DB::raw('COUNT(CASE WHEN h.statut_demande = "approuve" THEN 1 END) as conges_approuves'),
                DB::raw('COUNT(CASE WHEN h.statut_demande = "en_attente" THEN 1 END) as conges_en_attente'),
                DB::raw('COUNT(DISTINCT h.agent_id) as agents_concernes'),
                DB::raw('ROUND((hp.jours_utilises * 100.0 / hp.jours_conge_totaux), 1) as taux_utilisation')
            ])
            ->leftJoin('holidays as h', 'hp.id', '=', 'h.holiday_planning_id')
            ->where('hp.annee', $year)
            ->groupBy([
                'hp.id', 'hp.type_structure', 'hp.nom_structure',
                'hp.jours_conge_totaux', 'hp.jours_utilises'
            ])
            ->orderBy('hp.type_structure')
            ->orderBy('hp.nom_structure')
            ->get();

        // Regrouper par type de structure
        $grouped = $stats->groupBy('type_structure')->map(function ($items) {
            return [
                'structures' => $items,
                'totals' => [
                    'jours_totaux' => $items->sum('jours_conge_totaux'),
                    'jours_utilises' => $items->sum('jours_utilises'),
                    'total_conges' => $items->sum('total_conges'),
                    'agents_concernes' => $items->sum('agents_concernes'),
                    'taux_moyen' => $items->avg('taux_utilisation')
                ]
            ];
        });

        return response()->json([
            'year' => $year,
            'statistiques' => $grouped,
            'global' => [
                'total_structures' => $stats->count(),
                'total_jours_prevus' => $stats->sum('jours_conge_totaux'),
                'total_jours_utilises' => $stats->sum('jours_utilises'),
                'total_conges' => $stats->sum('total_conges'),
                'taux_utilisation_global' => $stats->avg('taux_utilisation')
            ]
        ]);
    }

    /**
     * Export des données de planning
     */
    public function export(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $format = $request->get('format', 'json'); // json, csv

        $plannings = HolidayPlanning::with([
            'createdBy',
            'validatedBy',
            'holidays.agent'
        ])
            ->forYear($year)
            ->get();

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=planning-conges-{$year}.csv"
            ];

            $callback = function () use ($plannings) {
                $file = fopen('php://output', 'w');

                // En-têtes CSV
                fputcsv($file, [
                    'Structure', 'Type', 'Année', 'Jours Totaux', 'Jours Utilisés',
                    'Taux Utilisation (%)', 'Validé', 'Créé par', 'Date création'
                ]);

                foreach ($plannings as $planning) {
                    fputcsv($file, [
                        $planning->nom_structure,
                        $planning->type_structure_label,
                        $planning->annee,
                        $planning->jours_conge_totaux,
                        $planning->jours_utilises,
                        $planning->pourcentage_utilisation,
                        $planning->valide ? 'Oui' : 'Non',
                        $planning->createdBy->nom_complet ?? '',
                        $planning->created_at->format('d/m/Y H:i')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json([
            'year' => $year,
            'plannings' => $plannings,
            'generated_at' => now()->toISOString()
        ]);
    }

    /**
     * Suppression d'un planning
     */
    public function destroy(HolidayPlanning $holidayPlanning)
    {
        if ($holidayPlanning->valide && !auth()->user()->agent->hasRole(['RH National', 'SEN'])) {
            return response()->json([
                'message' => 'Impossible de supprimer un planning validé'
            ], 403);
        }

        if ($holidayPlanning->holidays()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un planning ayant des congés associés'
            ], 422);
        }

        $holidayPlanning->delete();

        return response()->json([
            'message' => 'Planning supprimé avec succès'
        ]);
    }
}
