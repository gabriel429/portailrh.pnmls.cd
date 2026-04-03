<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PointageResource;
use App\Models\Pointage;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PointageController extends ApiController
{
    /**
     * Display a paginated listing of pointages with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pointage::with(['agent']);

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

        foreach ($request->pointages as $row) {
            // Skip rows where no time was entered at all
            if (empty($row['heure_entree']) && empty($row['heure_sortie'])) {
                continue;
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
        if ($created > 0) $parts[] = "{$created} pointage(s) cree(s)";
        if ($updated > 0) $parts[] = "{$updated} pointage(s) mis a jour";
        $message = count($parts) > 0 ? implode(', ', $parts) . '.' : 'Aucun pointage enregistre.';

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
    public function show(Pointage $pointage): JsonResponse
    {
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
        $validated = $request->validate([
            'heure_entree' => 'nullable|date_format:H:i',
            'heure_sortie' => 'nullable|date_format:H:i',
            'heures_travaillees' => 'nullable|numeric',
            'observations' => 'nullable|string',
        ]);

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
            'message' => 'Pointage modifie avec succes.',
        ]));
    }

    /**
     * Remove the specified pointage.
     */
    public function destroy(Pointage $pointage): JsonResponse
    {
        $pointage->delete();

        return $this->success(null, [], [
            'message' => 'Pointage supprime avec succes.',
        ]);
    }

    /**
     * Daily report: attendance grouped by date.
     */
    public function daily(Request $request): JsonResponse
    {
        $dateDebut = $request->query('date_debut', now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->query('date_fin', now()->format('Y-m-d'));
        $agent_id = $request->query('agent_id');
        $organeFilter = $request->query('organe');

        $query = Pointage::with(['agent'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin]);

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
        $agents = Agent::actifs()->orderBy('nom')->get(['id', 'nom', 'prenom', 'postnom'])
            ->map(fn($a) => array_merge($a->toArray(), ['id_agent' => $a->id_agent]));

        return $this->success([
            'days' => $days,
            'agents' => $agents,
        ], [
            'filters' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'agent_id' => $agent_id,
            ],
        ], [
            'days' => $days,
            'agents' => $agents,
            'filters' => [
                'date_debut' => $dateDebut,
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
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $dateDebut = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $dateFin = $dateDebut->copy()->endOfMonth();

        $organeFilter = $request->query('organe');

        $query = Pointage::with(['agent.departement', 'agent.province'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin]);

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
        $current = $dateDebut->copy();
        while ($current <= $dateFin) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        // Group by agent
        $pointagesByAgent = $pointages->groupBy('agent_id');

        // Calculate monthly statistics per agent
        $agentStats = $pointagesByAgent->map(function ($agentPointages) use ($dateDebut, $dateFin, $workingDays) {
            $totalDays = $dateDebut->diffInDays($dateFin) + 1;
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

        // Sort by agent name
        $agentStats = $agentStats->sortBy(function ($stat) {
            return $stat['agent']->nom . ' ' . $stat['agent']->prenom;
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

        $allPointages = Pointage::with(['agent.departement', 'agent.province'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin])
            ->get();

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
        $allNational = Department::whereNull('province_id')
            ->orderBy('nom')
            ->get(['id', 'code', 'nom']);

        $senStructures = [];
        foreach ($allNational as $dept) {
            if (in_array($dept->code, ['DIR'])) {
                $group = 'Direction';
            } elseif (str_starts_with($dept->code, 'D')) {
                $group = 'Departements';
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

        $allProvinces = Province::orderBy('nom')
            ->get(['id', 'code', 'nom']);

        return $this->success([
            'agent_stats' => $agentStats,
            'global_stats' => $globalStats,
        ], [
            'stats_by_organe' => $statsByOrgane,
            'month' => $month,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
        ], [
            'agent_stats' => $agentStats,
            'global_stats' => $globalStats,
            'stats_by_organe' => $statsByOrgane,
            'month' => $month,
            'date_debut' => $dateDebut->format('Y-m-d'),
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
        $departmentId = $request->query('department_id');
        $date = $request->query('date');

        if (!$departmentId) {
            return $this->success([]);
        }

        $agents = Agent::actifs()
            ->where('departement_id', $departmentId)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom', 'postnom', 'poste_actuel']);

        // Attach existing pointage for each agent on the given date
        if ($date) {
            $pointages = Pointage::where('date_pointage', $date)
                ->whereIn('agent_id', $agents->pluck('id'))
                ->get()
                ->keyBy('agent_id');

            $agents->each(function ($agent) use ($pointages) {
                $p = $pointages->get($agent->id);
                $agent->pointage_existant = $p ? [
                    'heure_entree' => $p->heure_entree ? Carbon::parse($p->heure_entree)->format('H:i') : null,
                    'heure_sortie' => $p->heure_sortie ? Carbon::parse($p->heure_sortie)->format('H:i') : null,
                    'observations' => $p->observations,
                ] : null;
            });
        }

        return $this->success($agents, [], ['agents' => $agents]);
    }

    /**
     * Export daily attendance to Excel (streamed response).
     */
    public function exportDaily(Request $request)
    {
        $dateDebut = $request->query('date_debut', now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->query('date_fin', now()->format('Y-m-d'));
        $agent_id = $request->query('agent_id');

        $query = Pointage::with(['agent'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin]);

        if ($agent_id) {
            $query->where('agent_id', $agent_id);
        }

        $pointages = $query->orderBy('date_pointage', 'desc')->get();
        $pointagesByDate = $pointages->groupBy(function ($item) {
            return $item->date_pointage->format('Y-m-d');
        });

        $filename = 'pointages_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($pointagesByDate) {
            $rows = [];
            $rows[] = ['Date', 'Agent', 'Matricule', 'Entree', 'Sortie', 'Heures Travaillees', 'Statut'];

            foreach ($pointagesByDate as $date => $dayPointages) {
                foreach ($dayPointages as $pointage) {
                    $rows[] = [
                        $pointage->date_pointage->format('d/m/Y'),
                        $pointage->agent->prenom . ' ' . $pointage->agent->nom,
                        $pointage->agent->id_agent ?? $pointage->agent->id,
                        $pointage->heure_entree ?? '-',
                        $pointage->heure_sortie ?? '-',
                        $pointage->heures_travaillees ?? 0,
                        $pointage->heure_entree ? 'Present' : 'Absent',
                    ];
                }
            }

            $this->writeRealExcelFile($rows, 'Pointages');
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export monthly report to Excel (streamed response).
     */
    public function exportMonthly(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $dateDebut = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $dateFin = $dateDebut->copy()->endOfMonth();

        $query = Pointage::with(['agent'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin]);

        if ($request->filled('organe')) {
            $query->whereHas('agent', fn($q) => $q->where('organe', $request->query('organe')));
        }
        if ($request->filled('department_id')) {
            $query->whereHas('agent', fn($q) => $q->where('departement_id', $request->query('department_id')));
        }
        if ($request->filled('province_id')) {
            $query->whereHas('agent', fn($q) => $q->where('province_id', $request->query('province_id')));
        }

        $pointages = $query->orderBy('date_pointage', 'desc')->get();

        $pointagesByAgent = $pointages->groupBy('agent_id');

        $agentStats = $pointagesByAgent->map(function ($agentPointages) use ($dateDebut, $dateFin) {
            $workingDays = 0;
            $presentDays = 0;
            $absentDays = 0;
            $totalHours = 0;

            $current = $dateDebut->copy();
            while ($current <= $dateFin) {
                if ($current->isWeekday()) {
                    $workingDays++;
                }
                $current->addDay();
            }

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
                'working_days' => $workingDays,
                'recorded' => count($agentPointages),
                'present' => $presentDays,
                'absent' => $absentDays,
                'total_hours' => $totalHours,
                'attendance_rate' => $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0,
            ];
        });

        $agentStats = $agentStats->sortBy(function ($stat) {
            return $stat['agent']->nom . ' ' . $stat['agent']->prenom;
        });

        $filename = 'rapport_mensuel_' . $month . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($agentStats, $dateDebut, $dateFin) {
            $rows = [];
            $rows[] = ['RAPPORT MENSUEL DE POINTAGES'];
            $rows[] = ['Periode: ' . $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y')];
            $rows[] = [];
            $rows[] = ['Agent', 'Matricule', 'Jours Travail', 'Jours Enregistres', 'Presents', 'Absents', 'Heures Travaillees', 'Taux Assiduite (%)'];

            foreach ($agentStats as $stat) {
                $rows[] = [
                    $stat['agent']->prenom . ' ' . $stat['agent']->nom,
                    $stat['agent']->id_agent ?? $stat['agent']->id,
                    $stat['working_days'],
                    $stat['recorded'],
                    $stat['present'],
                    $stat['absent'],
                    $stat['total_hours'],
                    $stat['attendance_rate'],
                ];
            }

            $rows[] = [];
            $rows[] = ['RESUME'];
            $rows[] = ['Total Agents', $agentStats->count()];
            $rows[] = ['Total Presents', $agentStats->sum('present')];
            $rows[] = ['Total Absents', $agentStats->sum('absent')];
            $rows[] = ['Taux Moyen (%)', round($agentStats->avg('attendance_rate'), 2)];

            $this->writeRealExcelFile($rows, 'Rapport Mensuel');
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Write real Excel file (.xlsx) using ZipArchive.
     */
    private function writeRealExcelFile($rows, $sheetName)
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
<sheet name="' . htmlspecialchars($sheetName) . '" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2">
<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
<font><bold val="1"/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
</fonts>
<fills count="2">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
</fills>
<borders count="1">
<border><left/><right/><top/><bottom/><diagonal/></border>
</borders>
<cellStyleXfs count="1">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
</cellStyleXfs>
<cellXfs count="2">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
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

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';

        foreach ($rows as $rowIndex => $rowData) {
            $sheet .= '<row r="' . ($rowIndex + 1) . '">';
            foreach ($rowData as $colIndex => $cellValue) {
                $col = $this->getColumnLetter($colIndex + 1);
                $cellRef = $col . ($rowIndex + 1);
                $cellStyle = ($rowIndex === 0 && !empty($rowData[0])) ? ' s="1"' : '';

                if (is_numeric($cellValue)) {
                    $sheet .= '<c r="' . $cellRef . '" t="n"' . $cellStyle . '><v>' . $cellValue . '</v></c>';
                } else {
                    $sheet .= '<c r="' . $cellRef . '" t="inlineStr"' . $cellStyle . '><is><t>' . htmlspecialchars(strval($cellValue)) . '</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }

        $sheet .= '</sheetData></worksheet>';
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
}
