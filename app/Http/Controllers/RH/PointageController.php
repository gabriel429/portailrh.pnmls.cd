<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Pointage;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class PointageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $pointages = Pointage::with(['agent'])
            ->paginate(15);

        return view('rh.pointages.index', compact('pointages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.pointages.create', compact('agents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date_pointage' => 'required|date|unique:pointages,date_pointage,NULL,id,agent_id,' . $request->agent_id,
            'heure_entree' => 'nullable|date_format:H:i',
            'heure_sortie' => 'nullable|date_format:H:i',
            'heures_travaillees' => 'nullable|numeric',
            'observations' => 'nullable|string',
        ]);

        Pointage::create($validated);

        return redirect()->route('rh.pointages.index')
            ->with('success', 'Pointage créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pointage $pointage): View
    {
        return view('rh.pointages.show', compact('pointage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pointage $pointage): View
    {
        $agents = Agent::actifs()->get();

        return view('rh.pointages.edit', compact('pointage', 'agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pointage $pointage): RedirectResponse
    {
        $validated = $request->validate([
            'heure_entree' => 'nullable|date_format:H:i',
            'heure_sortie' => 'nullable|date_format:H:i',
            'heures_travaillees' => 'nullable|numeric',
            'observations' => 'nullable|string',
        ]);

        $pointage->update($validated);

        return redirect()->route('rh.pointages.show', $pointage)
            ->with('success', 'Pointage modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pointage $pointage): RedirectResponse
    {
        $pointage->delete();

        return redirect()->route('rh.pointages.index')
            ->with('success', 'Pointage supprimé avec succès');
    }

    /**
     * Display daily breakdown of attendance
     */
    public function daily(Request $request): View
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

        // Group by date
        $pointagesByDate = $pointages->groupBy(function ($item) {
            return $item->date_pointage->format('Y-m-d');
        });

        // Calculate daily statistics
        $dailyStats = $pointagesByDate->map(function ($dayPointages) {
            return [
                'count' => $dayPointages->count(),
                'total_hours' => $dayPointages->sum('heures_travaillees'),
                'present' => $dayPointages->filter(fn($p) => $p->heure_entree)->count(),
                'absent' => $dayPointages->filter(fn($p) => !$p->heure_entree)->count(),
            ];
        });

        $agents = Agent::actifs()->get();

        return view('rh.pointages.daily', compact('pointagesByDate', 'dailyStats', 'agents', 'dateDebut', 'dateFin', 'agent_id'));
    }

    /**
     * Display monthly report
     */
    public function monthlyReport(Request $request): View
    {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $dateDebut = \Carbon\Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $dateFin = $dateDebut->copy()->endOfMonth();

        $pointages = Pointage::with(['agent'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin])
            ->orderBy('date_pointage', 'desc')
            ->get();

        // Group by agent
        $pointagesByAgent = $pointages->groupBy('agent_id');

        // Calculate monthly statistics per agent
        $agentStats = $pointagesByAgent->map(function ($agentPointages) use ($dateDebut, $dateFin) {
            $totalDays = $dateDebut->diffInDays($dateFin) + 1;
            $workingDays = 0;
            $presentDays = 0;
            $absentDays = 0;
            $totalHours = 0;

            // Count working days (Mon-Fri typically, excluding weekends)
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
        });

        // Global stats
        $globalStats = [
            'total_agents' => $agentStats->count(),
            'total_present' => $agentStats->sum('present'),
            'total_absent' => $agentStats->sum('absent'),
            'average_hours' => $agentStats->count() > 0 ? round($agentStats->sum('total_hours') / $agentStats->count(), 2) : 0,
            'average_attendance' => $agentStats->count() > 0 ? round($agentStats->avg('attendance_rate'), 2) : 0,
        ];

        return view('rh.pointages.monthly-report', compact('agentStats', 'globalStats', 'month', 'dateDebut', 'dateFin'));
    }

    /**
     * Export daily attendance to Excel
     */
    public function exportDailyExcel(Request $request)
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

        // Create Excel file
        $filename = 'pointages_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($pointagesByDate) {
            $this->createExcelFile($pointagesByDate);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export monthly report to Excel
     */
    public function exportMonthlyExcel(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $dateDebut = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $dateFin = $dateDebut->copy()->endOfMonth();

        $pointages = Pointage::with(['agent'])
            ->whereBetween('date_pointage', [$dateDebut, $dateFin])
            ->orderBy('date_pointage', 'desc')
            ->get();

        $pointagesByAgent = $pointages->groupBy('agent_id');

        // Calculate statistics
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
            $this->createMonthlyExcelFile($agentStats, $dateDebut, $dateFin);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Create Excel file from daily data
     */
    private function createExcelFile($pointagesByDate)
    {
        $rows = [];

        // Headers
        $rows[] = ['Date', 'Agent', 'Matricule', 'Entrée', 'Sortie', 'Heures Travaillées', 'Statut'];

        // Data
        foreach ($pointagesByDate as $date => $dayPointages) {
            foreach ($dayPointages as $pointage) {
                $rows[] = [
                    $pointage->date_pointage->format('d/m/Y'),
                    $pointage->agent->prenom . ' ' . $pointage->agent->nom,
                    $pointage->agent->matricule_pnmls,
                    $pointage->heure_entree ?? '-',
                    $pointage->heure_sortie ?? '-',
                    $pointage->heures_travaillees ?? 0,
                    $pointage->heure_entree ? 'Présent' : 'Absent',
                ];
            }
        }

        $this->writeRealExcelFile($rows, 'Pointages');
    }

    /**
     * Create Excel file from monthly report
     */
    private function createMonthlyExcelFile($agentStats, $dateDebut, $dateFin)
    {
        $rows = [];

        // Title
        $rows[] = ['RAPPORT MENSUEL DE POINTAGES'];
        $rows[] = ['Période: ' . $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y')];
        $rows[] = [];

        // Headers
        $rows[] = ['Agent', 'Matricule', 'Jours Travail', 'Jours Enregistrés', 'Présents', 'Absents', 'Heures Travaillées', 'Taux Assiduité (%)'];

        // Data
        foreach ($agentStats as $stat) {
            $rows[] = [
                $stat['agent']->prenom . ' ' . $stat['agent']->nom,
                $stat['agent']->matricule_pnmls,
                $stat['working_days'],
                $stat['recorded'],
                $stat['present'],
                $stat['absent'],
                $stat['total_hours'],
                $stat['attendance_rate'],
            ];
        }

        // Summary
        $rows[] = [];
        $rows[] = ['RÉSUMÉ'];
        $rows[] = ['Total Agents', $agentStats->count()];
        $rows[] = ['Total Présents', $agentStats->sum('present')];
        $rows[] = ['Total Absents', $agentStats->sum('absent')];
        $rows[] = ['Taux Moyen (%)', round($agentStats->avg('attendance_rate'), 2)];

        $this->writeRealExcelFile($rows, 'Rapport Mensuel');
    }

    /**
     * Write real Excel file (.xlsx)
     */
    private function writeRealExcelFile($rows, $sheetName)
    {
        // Create temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'excel_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Create [Content_Types].xml
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

        // Create _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // Create xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml', $wbRels);

        // Create xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="' . htmlspecialchars($sheetName) . '" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // Create xl/styles.xml
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
<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyFill="1">
<patternFill patternType="solid" fgColor="FF366092" bgColor="FF366092"/>
</xf>
</cellXfs>
<cellStyles count="1">
<cellStyle name="Normal" xfId="0" builtinId="0"/>
</cellStyles>
<dxfs count="0"/>
<tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // Create xl/theme/theme1.xml (minimal)
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

        // Create xl/worksheets/sheet1.xml with data
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

        // Create docProps/core.xml
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:creator>Portail RH</dc:creator>
<cp:lastModifiedBy>Portail RH</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">' . now()->toIso8601String() . '</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">' . now()->toIso8601String() . '</dcterms:modified>
</cp:coreProperties>';
        $zip->addFromString('docProps/core.xml', $core);

        $zip->close();

        // Output file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Length: ' . filesize($tmpFile));
        readfile($tmpFile);
        unlink($tmpFile);
    }

    /**
     * Convert column index to letter (1=A, 2=B, etc)
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
