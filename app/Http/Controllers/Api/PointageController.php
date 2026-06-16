<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PointageResource;
use App\Models\Pointage;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Province;
use App\Models\Holiday;
use App\Models\AgentStatus;
use App\Models\Request as RequestModel;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PointageController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Display a paginated listing of pointages with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pointage::with(['agent']);

        $this->scopeService()->applyPointageScope($query, $request->user());

        // Filter by specific date
        if ($request->filled('date')) {
            $query->whereDate('date_pointage', $request->date);
        }

        // Filter by date range
        if ($request->filled('date_debut')) {
            $query->whereDate('date_pointage', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_pointage', '<=', $request->date_fin);
        }

        // Filter by agent
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Filter by department (via agent relation)
        if ($request->filled('department_id')) {
            $query->whereHas('agent', function ($q) use ($request) {
                $q->where('departement_id', $request->department_id);
            });
        }

        // Filter by organe (via agent relation)
        if ($request->filled('organe')) {
            $query->whereHas('agent', function ($q) use ($request) {
                $q->where('organe', $request->organe);
            });
        }

        $pointages = $query->orderBy('date_pointage', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => PointageResource::collection($pointages->getCollection())->resolve(),
            'meta' => [
                'current_page' => $pointages->currentPage(),
                'last_page' => $pointages->lastPage(),
                'per_page' => $pointages->perPage(),
                'total' => $pointages->total(),
                'from' => $pointages->firstItem(),
                'to' => $pointages->lastItem(),
            ],
            'current_page' => $pointages->currentPage(),
            'last_page' => $pointages->lastPage(),
            'per_page' => $pointages->perPage(),
            'total' => $pointages->total(),
            'from' => $pointages->firstItem(),
            'to' => $pointages->lastItem(),
        ]);
    }

    /**
     * Store bulk pointages for a given date.
     * Accepts: { date_pointage, pointages: [ { agent_id, heure_entree, heure_sortie, observations } ] }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date_pointage' => 'required|date',
            'pointages' => 'required|array',
            'pointages.*.agent_id' => 'required|exists:agents,id',
            'pointages.*.heure_entree' => 'nullable|date_format:H:i',
            'pointages.*.heure_sortie' => 'nullable|date_format:H:i',
            'pointages.*.observations' => 'nullable|string',
        ]);

        $date = $request->date_pointage;
        $created = 0;
        $updated = 0;
        $scope = $this->scopeService();
        $user = $request->user();

        $agents = Agent::whereIn('id', collect($request->pointages)->pluck('agent_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($request->pointages as $row) {
            $agent = $agents->get((int) $row['agent_id']);
            if (!$scope->canAccessAgent($user, $agent)) {
                return response()->json([
                    'message' => 'Accès refusé pour au moins un agent hors de votre périmètre.',
                ], 403);
            }

            // Skip rows where no time was entered at all
            if (empty($row['heure_entree']) && empty($row['heure_sortie'])) {
                continue;
            }

            $justifiedAbsence = $this->justifiedAbsenceFor((int) $row['agent_id'], $date);
            if ($justifiedAbsence !== null) {
                return response()->json([
                    'message' => "Pointage bloqué : {$agent->prenom} {$agent->nom} est en absence justifiée ({$justifiedAbsence['label']}).",
                    'errors' => [
                        'pointages' => ["{$agent->prenom} {$agent->nom} est en absence justifiée ({$justifiedAbsence['label']}) pour cette date."],
                    ],
                ], 422);
            }

            $existing = Pointage::where('agent_id', $row['agent_id'])
                ->where('date_pointage', $date)
                ->first();

            $heureEntree = $row['heure_entree'] ?? ($existing->heure_entree ?? null);
            $heureSortie = $row['heure_sortie'] ?? ($existing->heure_sortie ?? null);

            $heures = null;
            if ($heureEntree && $heureSortie) {
                $entree = Carbon::createFromFormat('H:i', $heureEntree);
                $sortie = Carbon::createFromFormat('H:i', $heureSortie);
                $heures = round($sortie->diffInMinutes($entree) / 60, 1);
            }

            if ($existing) {
                $existing->update([
                    'heure_entree' => $heureEntree,
                    'heure_sortie' => $heureSortie,
                    'heures_travaillees' => $heures,
                    'observations' => $row['observations'] ?? $existing->observations,
                ]);
                $updated++;
            } else {
                Pointage::create([
                    'agent_id' => $row['agent_id'],
                    'date_pointage' => $date,
                    'heure_entree' => $heureEntree,
                    'heure_sortie' => $heureSortie,
                    'heures_travaillees' => $heures,
                    'observations' => $row['observations'] ?? null,
                ]);
                $created++;
            }
        }

        $parts = [];
        if ($created > 0) $parts[] = "{$created} pointage(s) créé(s)";
        if ($updated > 0) $parts[] = "{$updated} pointage(s) mis à jour";
        $message = count($parts) > 0 ? implode(', ', $parts) . '.' : 'Aucun pointage enregistré.';

        return $this->success([
            'created' => $created,
            'updated' => $updated,
        ], [], [
            'message' => $message,
            'created' => $created,
            'updated' => $updated,
        ], $created > 0 ? 201 : 200);
    }

    /**
     * Display the specified pointage.
     */
    public function show(Request $request, Pointage $pointage): JsonResponse
    {
        if (!$this->scopeService()->canAccessPointage($request->user(), $pointage)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $pointage->load('agent');

        $resource = PointageResource::make($pointage);
        $resolved = $resource->resolve();

        return response()->json(array_merge($resolved, [
            'data' => $resolved,
            'pointage' => $resolved,
        ]));
    }

    /**
     * Update the specified pointage.
     */
    public function update(Request $request, Pointage $pointage): JsonResponse
    {
        if (!$this->scopeService()->canAccessPointage($request->user(), $pointage)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'heure_entree' => 'nullable|date_format:H:i',
            'heure_sortie' => 'nullable|date_format:H:i',
            'heures_travaillees' => 'nullable|numeric',
            'observations' => 'nullable|string',
        ]);

        if ((!empty($validated['heure_entree']) || !empty($validated['heure_sortie']))
            && ($justifiedAbsence = $this->justifiedAbsenceFor((int) $pointage->agent_id, $pointage->date_pointage))) {
            return response()->json([
                'message' => "Pointage bloqué : cet agent est en absence justifiée ({$justifiedAbsence['label']}).",
                'errors' => [
                    'heure_entree' => ["Impossible d'enregistrer une heure pendant une absence justifiée."],
                ],
            ], 422);
        }

        // Auto-calculate hours if both times are provided
        if (!empty($validated['heure_entree']) && !empty($validated['heure_sortie'])) {
            $entree = Carbon::createFromFormat('H:i', $validated['heure_entree']);
            $sortie = Carbon::createFromFormat('H:i', $validated['heure_sortie']);
            $validated['heures_travaillees'] = round($sortie->diffInMinutes($entree) / 60, 1);
        }

        $pointage->update($validated);
        $pointage->load('agent');

        $resource = PointageResource::make($pointage);
        $resolved = $resource->resolve();

        return response()->json(array_merge($resolved, [
            'data' => $resolved,
            'pointage' => $resolved,
            'message' => 'Pointage modifié avec succès.',
        ]));
    }

    /**
     * Remove the specified pointage.
     */
    public function destroy(Request $request, Pointage $pointage): JsonResponse
    {
        if (!$this->scopeService()->canAccessPointage($request->user(), $pointage)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $pointage->delete();

        return $this->success(null, [], [
            'message' => 'Pointage supprimé avec succès.',
        ]);
    }

    /**
     * Daily report: attendance grouped by date.
     */
    public function daily(Request $request): JsonResponse
    {
        $scope = $this->scopeService();
        $dateDébut = $request->query('date_debut', now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->query('date_fin', now()->format('Y-m-d'));
        $agent_id = $request->query('agent_id');
        $organeFilter = $request->query('organe');

        $query = Pointage::with(['agent'])
            ->whereBetween('date_pointage', [$dateDébut, $dateFin]);

        $scope->applyPointageScope($query, $request->user());

        if ($agent_id) {
            $query->where('agent_id', $agent_id);
        }

        if ($organeFilter) {
            $query->whereHas('agent', fn($q) => $q->where('organe', $organeFilter));
        }

        $pointages = $query->orderBy('date_pointage', 'desc')->get();

        // Group by date
        $pointagesByDate = $pointages->groupBy(function ($item) {
            return $item->date_pointage->format('Y-m-d');
        });

        // Build response with daily statistics
        $days = [];
        foreach ($pointagesByDate as $date => $dayPointages) {
            $days[] = [
                'date' => $date,
                'pointages' => $dayPointages->values(),
                'stats' => [
                    'count' => $dayPointages->count(),
                    'total_hours' => $dayPointages->sum('heures_travaillees'),
                    'present' => $dayPointages->filter(fn($p) => $p->heure_entree)->count(),
                    'absent' => $dayPointages->filter(fn($p) => !$p->heure_entree)->count(),
                ],
            ];
        }

        // Agents list for filter dropdown
        $agentsQuery = Agent::actifs()->orderInstitutionally();
        $scope->applyAgentScope($agentsQuery, $request->user());

        $agents = $agentsQuery->get(['id', 'nom', 'prenom', 'postnom', 'matricule_etat'])
            ->map(fn($a) => array_merge($a->toArray(), ['id_agent' => $a->id_agent]));

        return $this->success([
            'days' => $days,
            'agents' => $agents,
        ], [
            'filters' => [
                'date_debut' => $dateDébut,
                'date_fin' => $dateFin,
                'agent_id' => $agent_id,
            ],
        ], [
            'days' => $days,
            'agents' => $agents,
            'filters' => [
                'date_debut' => $dateDébut,
                'date_fin' => $dateFin,
                'agent_id' => $agent_id,
            ],
        ]);
    }

    /**
     * Monthly report with per-agent summary statistics.
     * Accepts optional ?organe=Secretariat Executif National filter.
     * Always returns stats_by_organe for the overview.
     */
    public function monthly(Request $request): JsonResponse
    {
        $scope = $this->scopeService();
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $dateDébut = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $dateFin = $dateDébut->copy()->endOfMonth();

        $organeFilter = $request->query('organe');

        $query = Pointage::with(['agent.departement', 'agent.province'])
            ->whereBetween('date_pointage', [$dateDébut, $dateFin]);

        $scope->applyPointageScope($query, $request->user());

        if ($organeFilter) {
            $query->whereHas('agent', fn($q) => $q->where('organe', $organeFilter));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('agent', fn($q) => $q->where('departement_id', $request->query('department_id')));
        }

        if ($request->filled('province_id')) {
            $query->whereHas('agent', fn($q) => $q->where('province_id', $request->query('province_id')));
        }

        $pointages = $query->orderBy('date_pointage', 'desc')->get();

        // Calculate working days for the month
        $workingDays = 0;
        $current = $dateDébut->copy();
        while ($current <= $dateFin) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        // Group by agent
        $pointagesByAgent = $pointages->groupBy('agent_id');

        // Calculate monthly statistics per agent
        $agentStats = $pointagesByAgent->map(function ($agentPointages) use ($dateDébut, $dateFin, $workingDays) {
            $totalDays = $dateDébut->diffInDays($dateFin) + 1;
            $presentDays = 0;
            $absentDays = 0;
            $totalHours = 0;

            foreach ($agentPointages as $pointage) {
                if ($pointage->heure_entree) {
                    $presentDays++;
                } else {
                    $absentDays++;
                }
                $totalHours += $pointage->heures_travaillees ?? 0;
            }

            return [
                'agent' => $agentPointages->first()->agent,
                'total_days' => $totalDays,
                'working_days' => $workingDays,
                'recorded' => count($agentPointages),
                'present' => $presentDays,
                'absent' => $absentDays,
                'total_hours' => $totalHours,
                'attendance_rate' => $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0,
            ];
        });

        // Sort by institutional hierarchy, then agent name.
        $agentStats = $agentStats->sortBy(function ($stat) {
            $agent = $stat['agent'];
            $structureRank = str_contains(strtolower((string) $agent->organe), 'national') ? 1
                : (str_contains(strtolower((string) $agent->organe), 'provincial') ? 2
                : (str_contains(strtolower((string) $agent->organe), 'local') ? 3 : 4));
            $roleText = strtolower(trim(($agent->poste_actuel ?? '') . ' ' . ($agent->fonction ?? '')));
            $roleRank = str_contains($roleText, 'secr') && str_contains($roleText, 'ex') && str_contains($roleText, 'cutif') ? 1
                : (str_contains($roleText, 'directeur') ? 2
                : (str_contains($roleText, 'chef') && str_contains($roleText, 'section') ? 3
                : (str_contains($roleText, 'chef') && str_contains($roleText, 'cellule') ? 4
                : (str_contains($roleText, 'assistant') || str_contains($roleText, 'secr') ? 5 : 6))));

            return sprintf('%02d-%02d-%s-%s', $structureRank, $roleRank, $agent->nom, $agent->prenom);
        })->values();

        // Global stats
        $globalStats = [
            'total_agents' => $agentStats->count(),
            'total_present' => $agentStats->sum('present'),
            'total_absent' => $agentStats->sum('absent'),
            'average_hours' => $agentStats->count() > 0 ? round($agentStats->sum('total_hours') / $agentStats->count(), 2) : 0,
            'average_attendance' => $agentStats->count() > 0 ? round($agentStats->avg('attendance_rate'), 2) : 0,
        ];

        // Stats by organe (always returned for overview)
        $organes = [
            'sen' => 'Secretariat Executif National',
            'sep' => 'Secretariat Executif Provincial',
            'sel' => 'Secretariat Executif Local',
        ];

        $allPointagesQuery = Pointage::with(['agent.departement', 'agent.province'])
            ->whereBetween('date_pointage', [$dateDébut, $dateFin])
            ;
        $scope->applyPointageScope($allPointagesQuery, $request->user());
        $allPointages = $allPointagesQuery->get();

        $statsByOrgane = [];
        foreach ($organes as $code => $nom) {
            $orgPointages = $allPointages->filter(fn($p) => $p->agent && $p->agent->organe === $nom);
            $orgByAgent = $orgPointages->groupBy('agent_id');

            $orgPresent = 0;
            $orgAbsent = 0;
            $orgHours = 0;
            $orgRates = [];

            foreach ($orgByAgent as $agPts) {
                $pres = $agPts->filter(fn($p) => $p->heure_entree)->count();
                $abs = $agPts->filter(fn($p) => !$p->heure_entree)->count();
                $orgPresent += $pres;
                $orgAbsent += $abs;
                $orgHours += $agPts->sum('heures_travaillees');
                if ($workingDays > 0) {
                    $orgRates[] = round(($pres / $workingDays) * 100, 2);
                }
            }

            $statsByOrgane[$code] = [
                'total_agents' => $orgByAgent->count(),
                'total_present' => $orgPresent,
                'total_absent' => $orgAbsent,
                'total_hours' => $orgHours,
                'average_attendance' => count($orgRates) > 0 ? round(array_sum($orgRates) / count($orgRates), 2) : 0,
            ];
        }

        // Sub-filter options for hierarchical drill-down
        // SEN structures: group national departments by type (direction, departement, service rattache)
        $allNational = Department::query()
            ->operationalNationalStructures()
            ->orderBy('nom')
            ->get(['id', 'code', 'nom']);

        $senStructures = [];
        foreach ($allNational as $dept) {
            if (in_array($dept->code, ['DIR'])) {
                $group = 'Direction';
            } elseif (str_starts_with($dept->code, 'D')) {
                $group = 'Départements';
            } else {
                $group = 'Attaches';
            }
            $senStructures[] = [
                'id' => $dept->id,
                'code' => $dept->code,
                'nom' => $dept->nom,
                'group' => $group,
            ];
        }

        $allProvincesQuery = Province::query();
        $scope->filterProvinces($allProvincesQuery, $request->user());

        $allProvinces = $allProvincesQuery->orderBy('nom')
            ->get(['id', 'code', 'nom']);

        return $this->success([
            'agent_stats' => $agentStats,
            'global_stats' => $globalStats,
        ], [
            'stats_by_organe' => $statsByOrgane,
            'month' => $month,
            'date_debut' => $dateDébut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
        ], [
            'agent_stats' => $agentStats,
            'global_stats' => $globalStats,
            'stats_by_organe' => $statsByOrgane,
            'month' => $month,
            'date_debut' => $dateDébut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'sen_structures' => $senStructures,
            'provinces' => $allProvinces,
        ]);
    }

    /**
     * Return agents filtered by department_id, with existing pointage data for a given date.
     */
    public function agentsByDepartment(Request $request): JsonResponse
    {
        $scope = $this->scopeService();
        $departmentId = $request->query('department_id');
        $date = $request->query('date');

        $attachPointageContext = function ($agents) use ($date) {
            if (!$date) {
                return $agents;
            }

            $pointages = Pointage::where('date_pointage', $date)
                ->whereIn('agent_id', $agents->pluck('id'))
                ->get()
                ->keyBy('agent_id');

            $absenceMap = $this->justifiedAbsencesForAgents($agents->pluck('id')->all(), $date);
            $agents->each(function ($agent) use ($pointages, $absenceMap) {
                $p = $pointages->get($agent->id);
                $agent->pointage_existant = $p ? [
                    'heure_entree' => $p->heure_entree ? Carbon::parse($p->heure_entree)->format('H:i') : null,
                    'heure_sortie' => $p->heure_sortie ? Carbon::parse($p->heure_sortie)->format('H:i') : null,
                    'observations' => $p->observations,
                ] : null;
                $absence = $absenceMap[$agent->id] ?? null;
                $agent->absence_justifiee = $absence !== null;
                $agent->absence_justifiee_label = $absence['label'] ?? null;
                $agent->absence_justifiee_type = $absence['type'] ?? null;
            });

            return $agents;
        };

        if ($departmentId === null || $departmentId === '') {
            $userOrgane = strtolower((string) ($request->user()?->agent?->organe ?? ''));
            $isTerritorialStructure = $scope->isProvincialUser($request->user())
                || $scope->isLocalUser($request->user())
                || str_contains($userOrgane, 'provincial')
                || str_contains($userOrgane, 'local');

            if ($isTerritorialStructure) {
                $agentsQuery = Agent::actifs()->orderInstitutionally();
                $scope->applyAgentScope($agentsQuery, $request->user());

                $agents = $agentsQuery->get(['id', 'nom', 'prenom', 'postnom', 'poste_actuel']);
                $attachPointageContext($agents);

                return $this->success($agents, [], ['agents' => $agents]);
            }

            return $this->success([]);
        }

        // Cas spécial id=0 : agents SEN sans département (rattachement direct)
        if ((string) $departmentId === '0') {
            $agentsQuery = Agent::actifs()
                ->where('organe', 'Secrétariat Exécutif National')
                ->whereNull('departement_id')
                ->orderInstitutionally();
            $scope->applyAgentScope($agentsQuery, $request->user());
            $agents = $agentsQuery->get(['id', 'nom', 'prenom', 'postnom', 'poste_actuel']);
            $attachPointageContext($agents);
            return $this->success($agents, [], ['agents' => $agents]);
        }

        $agentsQuery = Agent::actifs()
            ->where('departement_id', $departmentId)
            ->orderInstitutionally();

        $scope->applyAgentScope($agentsQuery, $request->user());

        $agents = $agentsQuery->get(['id', 'nom', 'prenom', 'postnom', 'poste_actuel']);

        $attachPointageContext($agents);

        return $this->success($agents, [], ['agents' => $agents]);
    }

    /**
     * Export daily attendance to Excel (streamed response).
     */
    public function exportDaily(Request $request)
    {
        $scope = $this->scopeService();
        $dateDebut = Carbon::parse($request->query('date_debut', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $dateFin = Carbon::parse($request->query('date_fin', now()->format('Y-m-d')))->startOfDay();

        if ($dateFin->lt($dateDebut)) {
            [$dateDebut, $dateFin] = [$dateFin, $dateDebut];
        }

        $agents = $this->attendanceAgentQuery($request, $scope)->get();
        $agentIds = $agents->pluck('id')->all();

        $pointages = Pointage::query()
            ->whereBetween('date_pointage', [$dateDebut->toDateString(), $dateFin->toDateString()])
            ->whereIn('agent_id', $agentIds)
            ->get();

        $pointagesByKey = $pointages->keyBy(function (Pointage $pointage) {
            return $pointage->date_pointage->format('Y-m-d') . '|' . $pointage->agent_id;
        });
        $pointageDates = $pointages->groupBy(fn(Pointage $pointage) => $pointage->date_pointage->format('Y-m-d'));

        $filename = 'pointages_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($agents, $agentIds, $pointagesByKey, $pointageDates, $dateDebut, $dateFin) {
            [$rows, $options] = $this->buildDailyAttendanceExport($agents, $agentIds, $pointagesByKey, $pointageDates, $dateDebut, $dateFin);

            $this->writeRealExcelFile($rows, 'Presences', $options);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export monthly report to Excel (streamed response).
     */
    public function exportMonthly(Request $request)
    {
        $scope = $this->scopeService();
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $dateDebut = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $dateFin = $dateDebut->copy()->endOfMonth();

        $agents = $this->attendanceAgentQuery($request, $scope)->get();
        $agentIds = $agents->pluck('id')->all();

        $pointages = Pointage::query()
            ->whereBetween('date_pointage', [$dateDebut->toDateString(), $dateFin->toDateString()])
            ->whereIn('agent_id', $agentIds)
            ->get();

        $pointagesByKey = $pointages->keyBy(function (Pointage $pointage) {
            return $pointage->date_pointage->format('Y-m-d') . '|' . $pointage->agent_id;
        });

        $agentStats = $this->buildMonthlyAttendanceStats($agents, $agentIds, $pointagesByKey, $dateDebut, $dateFin);

        $filename = 'rapport_mensuel_' . $month . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($agentStats, $dateDebut, $dateFin) {
            $rows = [];
            $rowStyles = [];
            $rows[] = ['RAPPORT MENSUEL DES PRESENCES'];
            $rowStyles[0] = 1;
            $rows[] = ['Periode', $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'), 'Jours ouvrables', $agentStats['working_days']];
            $rowStyles[1] = 2;
            $rows[] = [
                'Resume',
                'Agents',
                $agentStats['agents_count'],
                'Presences',
                $agentStats['totals']['present'],
                'Absences justifiees',
                $agentStats['totals']['justified_absences'],
                'Absents',
                $agentStats['totals']['unjustified_absences'],
            ];
            $rowStyles[2] = 2;
            $rows[] = ['Note', 'Les samedis et dimanches ne sont pas comptabilises comme absences.'];
            $rowStyles[3] = 8;
            $rows[] = [];
            $rows[] = ['Agent', 'Matricule', 'Structure', 'Organe', 'Jours ouvrables', 'Presences', 'Absences justifiees', 'Absents', 'Heures', 'Taux presence (%)', 'Raisons principales'];
            $headerRow = count($rows) - 1;
            $rowStyles[$headerRow] = 3;

            foreach ($agentStats['rows'] as $stat) {
                $rows[] = [
                    $this->agentFullName($stat['agent']),
                    $stat['agent']->matricule_etat ?: 'N/A',
                    $this->agentStructureLabel($stat['agent']),
                    $stat['agent']->organe ?: '-',
                    $stat['working_days'],
                    $stat['present'],
                    $stat['justified_absences'],
                    $stat['unjustified_absences'],
                    $stat['total_hours'],
                    $stat['attendance_rate'],
                    $stat['reasons'] ?: '-',
                ];
                $rowStyles[count($rows) - 1] = $stat['unjustified_absences'] > 0 ? 6 : ($stat['justified_absences'] > 0 ? 5 : 4);
            }

            $rows[] = [];
            $rows[] = ['RESUME FINAL'];
            $rowStyles[count($rows) - 1] = 2;
            $rows[] = ['Total agents', $agentStats['agents_count']];
            $rows[] = ['Total presences', $agentStats['totals']['present']];
            $rows[] = ['Total absences justifiees', $agentStats['totals']['justified_absences']];
            $rows[] = ['Total absents non justifies', $agentStats['totals']['unjustified_absences']];
            $rows[] = ['Taux moyen de presence (%)', $agentStats['average_attendance_rate']];

            $this->writeRealExcelFile($rows, 'Rapport Mensuel', [
                'row_styles' => $rowStyles,
                'column_widths' => [28, 16, 28, 28, 16, 14, 20, 14, 12, 18, 48],
                'freeze_row' => $headerRow + 2,
                'auto_filter' => [
                    'start_row' => $headerRow + 1,
                    'end_row' => max($headerRow + 1, $headerRow + count($agentStats['rows']) + 1),
                    'end_col' => 11,
                ],
            ]);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function attendanceAgentQuery(Request $request, UserDataScope $scope)
    {
        $query = Agent::actifs()
            ->with(['departement:id,nom', 'province:id,nom', 'localite:id,nom'])
            ->orderInstitutionally();

        $scope->applyAgentScope($query, $request->user());

        if ($request->filled('agent_id')) {
            $query->whereKey($request->query('agent_id'));
        }
        if ($request->filled('organe')) {
            $query->where('organe', $request->query('organe'));
        }
        if ($request->filled('department_id')) {
            $query->where('departement_id', $request->query('department_id'));
        }
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->query('province_id'));
        }
        if ($request->filled('localite_id')) {
            $query->where('localite_id', $request->query('localite_id'));
        }

        return $query;
    }

    private function buildDailyAttendanceExport($agents, array $agentIds, $pointagesByKey, $pointageDates, Carbon $dateDebut, Carbon $dateFin): array
    {
        $dataRows = [];
        $dataStyles = [];
        $stats = [
            'present' => 0,
            'justified_absences' => 0,
            'unjustified_absences' => 0,
            'weekend_records' => 0,
        ];

        $current = $dateDebut->copy();
        while ($current <= $dateFin) {
            $day = $current->toDateString();
            $isWeekday = $current->isWeekday();
            $dayPointages = $pointageDates->get($day, collect());

            if (!$isWeekday && $dayPointages->isEmpty()) {
                $current->addDay();
                continue;
            }

            $absenceMap = $isWeekday ? $this->justifiedAbsencesForAgents($agentIds, $current) : [];
            $agentsForDay = $isWeekday
                ? $agents
                : $agents->filter(fn(Agent $agent) => $dayPointages->contains('agent_id', $agent->id));

            foreach ($agentsForDay as $agent) {
                $pointage = $pointagesByKey->get($day . '|' . $agent->id);
                $hasPresence = $pointage && $this->pointageHasPresence($pointage);
                $absence = $absenceMap[$agent->id] ?? null;

                if (!$isWeekday) {
                    $status = $hasPresence ? 'Present (week-end)' : 'Hors jour ouvrable';
                    $reason = 'Week-end non comptabilise';
                    $style = 8;
                    $stats['weekend_records']++;
                } elseif ($hasPresence) {
                    $status = 'Present';
                    $reason = $pointage?->observations ?: '-';
                    $style = 4;
                    $stats['present']++;
                } elseif ($absence) {
                    $status = 'Absence justifiee';
                    $reason = $this->absenceExportText($absence);
                    $style = 5;
                    $stats['justified_absences']++;
                } else {
                    $status = 'Absent non justifie';
                    $reason = $pointage?->observations ?: 'Aucun pointage enregistre';
                    $style = 6;
                    $stats['unjustified_absences']++;
                }

                $dataRows[] = [
                    $current->format('d/m/Y'),
                    $this->frenchDayName($current),
                    $this->agentFullName($agent),
                    $agent->matricule_etat ?: 'N/A',
                    $this->agentStructureLabel($agent),
                    $agent->organe ?: '-',
                    $status,
                    $reason,
                    $pointage ? $this->formatPointageTime($pointage->heure_entree) : '-',
                    $pointage ? $this->formatPointageTime($pointage->heure_sortie) : '-',
                    $hasPresence ? ($pointage->heures_travaillees ?? 0) : '',
                    $pointage?->observations ?: '-',
                ];
                $dataStyles[] = $style;
            }

            $current->addDay();
        }

        $rows = [];
        $rowStyles = [];
        $rows[] = ['EXPORT DES PRESENCES'];
        $rowStyles[0] = 1;
        $rows[] = [
            'Periode',
            $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'Jours ouvrables',
            count($this->workingDates($dateDebut, $dateFin)),
            'Agents',
            $agents->count(),
        ];
        $rowStyles[1] = 2;
        $rows[] = [
            'Resume',
            'Presents',
            $stats['present'],
            'Absences justifiees',
            $stats['justified_absences'],
            'Absents',
            $stats['unjustified_absences'],
            'Pointages week-end',
            $stats['weekend_records'],
        ];
        $rowStyles[2] = 2;
        $rows[] = ['Note', 'Les samedis et dimanches ne sont pas comptabilises comme absences. Les lignes week-end apparaissent seulement si un pointage existe.'];
        $rowStyles[3] = 8;
        $rows[] = [];
        $rows[] = ['Date', 'Jour', 'Agent', 'Matricule', 'Structure', 'Organe', 'Statut', 'Raison / motif', 'Arrivee', 'Sortie', 'Heures', 'Observations'];
        $headerRow = count($rows) - 1;
        $rowStyles[$headerRow] = 3;

        foreach ($dataRows as $index => $dataRow) {
            $rows[] = $dataRow;
            $rowStyles[count($rows) - 1] = $dataStyles[$index] ?? 7;
        }

        return [$rows, [
            'row_styles' => $rowStyles,
            'column_widths' => [14, 14, 30, 16, 32, 28, 22, 52, 12, 12, 10, 38],
            'freeze_row' => $headerRow + 2,
            'auto_filter' => [
                'start_row' => $headerRow + 1,
                'end_row' => max($headerRow + 1, count($rows)),
                'end_col' => 12,
            ],
        ]];
    }

    private function buildMonthlyAttendanceStats($agents, array $agentIds, $pointagesByKey, Carbon $dateDebut, Carbon $dateFin): array
    {
        $workingDates = $this->workingDates($dateDebut, $dateFin);
        $absenceMaps = [];
        foreach ($workingDates as $date) {
            $absenceMaps[$date->toDateString()] = $this->justifiedAbsencesForAgents($agentIds, $date);
        }

        $rows = [];
        $totals = [
            'present' => 0,
            'justified_absences' => 0,
            'unjustified_absences' => 0,
        ];

        foreach ($agents as $agent) {
            $present = 0;
            $justified = 0;
            $unjustified = 0;
            $totalHours = 0;
            $reasonCounts = [];

            foreach ($workingDates as $date) {
                $day = $date->toDateString();
                $pointage = $pointagesByKey->get($day . '|' . $agent->id);
                $hasPresence = $pointage && $this->pointageHasPresence($pointage);

                if ($hasPresence) {
                    $present++;
                    $totalHours += (float) ($pointage->heures_travaillees ?? 0);
                    continue;
                }

                $absence = $absenceMaps[$day][$agent->id] ?? null;
                if ($absence) {
                    $justified++;
                    $label = $this->absenceExportText($absence, false);
                    $reasonCounts[$label] = ($reasonCounts[$label] ?? 0) + 1;
                    continue;
                }

                $unjustified++;
                $label = $pointage?->observations ? 'Absent - ' . $pointage->observations : 'Absent non justifie';
                $reasonCounts[$label] = ($reasonCounts[$label] ?? 0) + 1;
            }

            $workingDays = count($workingDates);
            $rows[] = [
                'agent' => $agent,
                'working_days' => $workingDays,
                'present' => $present,
                'justified_absences' => $justified,
                'unjustified_absences' => $unjustified,
                'total_hours' => round($totalHours, 1),
                'attendance_rate' => $workingDays > 0 ? round(($present / $workingDays) * 100, 2) : 0,
                'reasons' => $this->formatReasonCounts($reasonCounts),
            ];

            $totals['present'] += $present;
            $totals['justified_absences'] += $justified;
            $totals['unjustified_absences'] += $unjustified;
        }

        $average = count($rows) > 0
            ? round(array_sum(array_column($rows, 'attendance_rate')) / count($rows), 2)
            : 0;

        return [
            'rows' => $rows,
            'working_days' => count($workingDates),
            'agents_count' => $agents->count(),
            'totals' => $totals,
            'average_attendance_rate' => $average,
        ];
    }

    private function workingDates(Carbon $dateDebut, Carbon $dateFin): array
    {
        $dates = [];
        $current = $dateDebut->copy();
        while ($current <= $dateFin) {
            if ($current->isWeekday()) {
                $dates[] = $current->copy();
            }
            $current->addDay();
        }

        return $dates;
    }

    private function frenchDayName(Carbon $date): string
    {
        return [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ][$date->isoWeekday()] ?? '';
    }

    private function agentFullName(Agent $agent): string
    {
        $parts = array_filter([$agent->prenom, $agent->nom, $agent->postnom]);

        return trim(implode(' ', $parts)) ?: 'Agent #' . $agent->id;
    }

    private function agentStructureLabel(Agent $agent): string
    {
        $parts = array_filter([
            $agent->departement?->nom,
            $agent->province?->nom,
            $agent->localite?->nom,
        ]);
        $parts = array_values(array_unique($parts));

        return $parts ? implode(' / ', $parts) : '-';
    }

    private function pointageHasPresence(Pointage $pointage): bool
    {
        return $this->isActualTime($this->formatPointageTime($pointage->heure_entree))
            || $this->isActualTime($this->formatPointageTime($pointage->heure_sortie));
    }

    private function isActualTime(string $time): bool
    {
        return $time !== '-' && $time !== '00:00';
    }

    private function formatPointageTime($time): string
    {
        if (!$time) {
            return '-';
        }

        if ($time instanceof \DateTimeInterface) {
            return Carbon::parse($time)->format('H:i');
        }

        $value = trim((string) $time);
        if ($value === '') {
            return '-';
        }

        if (preg_match('/^\d{2}:\d{2}/', $value)) {
            return substr($value, 0, 5);
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return substr($value, 0, 5) ?: '-';
        }
    }

    private function absenceExportText(array $absence, bool $withPeriod = true): string
    {
        $parts = array_filter([
            $absence['label'] ?? 'Absence justifiee',
            $absence['reason'] ?? null,
            $withPeriod ? ($absence['period'] ?? null) : null,
        ], fn($value) => trim((string) $value) !== '');

        return implode(' - ', array_values(array_unique($parts))) ?: 'Absence justifiee';
    }

    private function formatReasonCounts(array $reasonCounts): string
    {
        if ($reasonCounts === []) {
            return '';
        }

        $parts = [];
        foreach ($reasonCounts as $reason => $count) {
            $parts[] = $reason . ' (' . $count . 'j)';
        }

        return implode('; ', $parts);
    }

    private function firstFilled(...$values): ?string
    {
        foreach ($values as $value) {
            $value = trim((string) ($value ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function absencePeriodText($dateDebut, $dateFin): ?string
    {
        if (!$dateDebut && !$dateFin) {
            return null;
        }

        $start = $dateDebut ? Carbon::parse($dateDebut)->format('d/m/Y') : null;
        $end = $dateFin ? Carbon::parse($dateFin)->format('d/m/Y') : null;

        if ($start && $end && $start !== $end) {
            return 'du ' . $start . ' au ' . $end;
        }

        return $start ? 'le ' . $start : "jusqu'au " . $end;
    }

    /**
     * Write real Excel file (.xlsx) using ZipArchive.
     */
    private function writeRealExcelFile($rows, $sheetName, array $options = [])
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'excel_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="' . htmlspecialchars($sheetName, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="3">
<font><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>
<font><bold val="1"/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
<font><bold val="1"/><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>
</fonts>
<fills count="8">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF0F2F55"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF087EA4"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFE5F7EC"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFF3D6"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFE1E1"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFEAF6FB"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FFB7DFF2"/></left><right style="thin"><color rgb="FFB7DFF2"/></right><top style="thin"><color rgb="FFB7DFF2"/></top><bottom style="thin"><color rgb="FFB7DFF2"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
</cellStyleXfs>
<cellXfs count="9">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="2" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
</cellXfs>
<cellStyles count="1">
<cellStyle name="Normal" xfId="0" builtinId="0"/>
</cellStyles>
<dxfs count="0"/>
<tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        $theme = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme">
<a:themeElements>
<a:clrScheme name="Office">
<a:dk1><a:srgbClr val="000000"/></a:dk1>
<a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
<a:dk2><a:srgbClr val="1F497D"/></a:dk2>
<a:lt2><a:srgbClr val="EBEBEB"/></a:lt2>
<a:accent1><a:srgbClr val="4472C4"/></a:accent1>
<a:accent2><a:srgbClr val="ED7D31"/></a:accent2>
<a:accent3><a:srgbClr val="A5A5A5"/></a:accent3>
<a:accent4><a:srgbClr val="FFC000"/></a:accent4>
<a:accent5><a:srgbClr val="5B9BD5"/></a:accent5>
<a:accent6><a:srgbClr val="70AD47"/></a:accent6>
<a:hyperlink><a:srgbClr val="0563C1"/></a:hyperlink>
<a:followedHyperlink><a:srgbClr val="954F72"/></a:followedHyperlink>
</a:clrScheme>
</a:themeElements>
</a:theme>';
        $zip->addFromString('xl/theme/theme1.xml', $theme);

        $sheetViews = '';
        if (!empty($options['freeze_row']) && (int) $options['freeze_row'] > 1) {
            $freezeRow = (int) $options['freeze_row'];
            $ySplit = $freezeRow - 1;
            $sheetViews = '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $ySplit . '" topLeftCell="A' . $freezeRow . '" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft"/></sheetView></sheetViews>';
        }

        $cols = '';
        if (!empty($options['column_widths']) && is_array($options['column_widths'])) {
            $cols = '<cols>';
            foreach (array_values($options['column_widths']) as $index => $width) {
                $column = $index + 1;
                $cols .= '<col min="' . $column . '" max="' . $column . '" width="' . max(8, (float) $width) . '" customWidth="1"/>';
            }
            $cols .= '</cols>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . $sheetViews . $cols . '
<sheetData>';

        $rowStyles = $options['row_styles'] ?? [];
        foreach ($rows as $rowIndex => $rowData) {
            $sheet .= '<row r="' . ($rowIndex + 1) . '">';
            $rowStyle = $rowStyles[$rowIndex] ?? (($rowIndex === 0 && !empty($rowData[0])) ? 1 : 7);
            foreach ($rowData as $colIndex => $cellValue) {
                $col = $this->getColumnLetter($colIndex + 1);
                $cellRef = $col . ($rowIndex + 1);
                $cellStyle = ' s="' . (int) $rowStyle . '"';

                if (is_int($cellValue) || is_float($cellValue)) {
                    $sheet .= '<c r="' . $cellRef . '" t="n"' . $cellStyle . '><v>' . $cellValue . '</v></c>';
                } else {
                    $text = preg_replace('/[^\P{C}\t\n\r]/u', '', strval($cellValue));
                    $text = $text === null ? strval($cellValue) : $text;
                    $sheet .= '<c r="' . $cellRef . '" t="inlineStr"' . $cellStyle . '><is><t>' . htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }

        $sheet .= '</sheetData>';

        if (!empty($options['auto_filter']) && is_array($options['auto_filter'])) {
            $startRow = (int) ($options['auto_filter']['start_row'] ?? 1);
            $endRow = (int) ($options['auto_filter']['end_row'] ?? count($rows));
            $endCol = $this->getColumnLetter((int) ($options['auto_filter']['end_col'] ?? max(1, count($rows[0] ?? []))));
            $sheet .= '<autoFilter ref="A' . $startRow . ':' . $endCol . max($startRow, $endRow) . '"/>';
        }

        $sheet .= '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:creator>E-PNMLS</dc:creator>
<cp:lastModifiedBy>E-PNMLS</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">' . now()->toIso8601String() . '</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">' . now()->toIso8601String() . '</dcterms:modified>
</cp:coreProperties>';
        $zip->addFromString('docProps/core.xml', $core);

        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Length: ' . filesize($tmpFile));
        readfile($tmpFile);
        unlink($tmpFile);
    }

    /**
     * Convert column index to letter (1=A, 2=B, etc).
     */
    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26);
        }
        return $letter;
    }

    private function justifiedAbsenceFor(int $agentId, string|\DateTimeInterface $date): ?array
    {
        return $this->justifiedAbsencesForAgents([$agentId], $date)[$agentId] ?? null;
    }

    private function justifiedAbsencesForAgents(array $agentIds, string|\DateTimeInterface $date): array
    {
        $agentIds = array_values(array_unique(array_filter(array_map('intval', $agentIds))));
        if ($agentIds === []) {
            return [];
        }

        $day = Carbon::parse($date)->toDateString();
        $absences = [];

        Holiday::query()
            ->whereIn('agent_id', $agentIds)
            ->where('statut_demande', 'approuve')
            ->whereDate('date_debut', '<=', $day)
            ->whereDate('date_fin', '>=', $day)
            ->get(['agent_id', 'type_conge', 'motif', 'observation', 'date_debut', 'date_fin'])
            ->each(function (Holiday $holiday) use (&$absences) {
                $absences[$holiday->agent_id] = [
                    'type' => 'holiday',
                    'label' => $holiday->getTypeCongeLabel() ?: 'Congé approuvé',
                    'reason' => $this->firstFilled($holiday->motif, $holiday->observation),
                    'period' => $this->absencePeriodText($holiday->date_debut, $holiday->date_fin),
                    'source' => 'Congé RH',
                ];
            });

        AgentStatus::query()
            ->whereIn('agent_id', $agentIds)
            ->whereIn('statut', ['en_conge', 'en_mission', 'en_formation', 'suspendu'])
            ->whereDate('date_debut', '<=', $day)
            ->where(function ($query) use ($day) {
                $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $day);
            })
            ->get(['agent_id', 'statut', 'motif', 'commentaire', 'date_debut', 'date_fin'])
            ->each(function (AgentStatus $status) use (&$absences) {
                $absences[$status->agent_id] ??= [
                    'type' => 'agent_status',
                    'label' => $status->statut_label ?: 'Absence justifiée',
                    'reason' => $this->firstFilled($status->motif, $status->commentaire),
                    'period' => $this->absencePeriodText($status->date_debut, $status->date_fin),
                    'source' => 'Statut agent',
                ];
            });

        RequestModel::query()
            ->whereIn('agent_id', $agentIds)
            ->whereIn('type', ['absence', 'conge', 'permission'])
            ->whereIn('statut', ['approuvé', 'approuve', 'approuvée', 'approuvee'])
            ->whereDate('date_debut', '<=', $day)
            ->where(function ($query) use ($day) {
                $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $day);
            })
            ->get(['agent_id', 'type', 'description', 'motivation', 'remarques', 'date_debut', 'date_fin'])
            ->each(function (RequestModel $request) use (&$absences) {
                $label = match ($request->type) {
                    'conge' => 'Demande de congé approuvée',
                    'permission' => 'Permission approuvée',
                    default => 'Demande d’absence approuvée',
                };
                $absences[$request->agent_id] ??= [
                    'type' => 'request',
                    'label' => $label,
                    'reason' => $this->firstFilled($request->description, $request->motivation, $request->remarques),
                    'period' => $this->absencePeriodText($request->date_debut, $request->date_fin),
                    'source' => 'Demande RH',
                ];
            });

        return $absences;
    }
}
