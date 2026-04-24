# -*- coding: utf-8 -*-

filepath = 'app/Http/Controllers/Api/ExecutiveDashboardController.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Insert BEFORE usort at position 46891
# The pattern is: closing braces, blank lines, then usort
# We'll insert right before usort

usort_pos = 46891

# Check the exact whitespace before usort
pre_indent = content[usort_pos - 20:usort_pos]
print(f"Before usort: {repr(pre_indent)}")

# The indent seems to be 12 spaces
insert_code = """            // Agents SEN sans département (rattachés directement au niveau national)
            if ($code === 'SEN') {
                $sansDeptAgents = Agent::where('organe', $nom)->whereNull('departement_id');
                $sansDeptTotal = (clone $sansDeptAgents)->count();
                $sansDeptActifs = (clone $sansDeptAgents)->actifs()->count();
                $sansDeptSuspendus = (clone $sansDeptAgents)->suspendu()->count();

                if ($sansDeptTotal > 0) {
                    $sansDeptActifsCount = $sansDeptActifs ?: 1;
                    $todayPresentSD = Pointage::byDate($now->toDateString())
                        ->whereNotNull('heure_entree')
                        ->whereHas('agent', fn($q) => $q->where('organe', $nom)->whereNull('departement_id'))
                        ->distinct('agent_id')->count('agent_id');

                    $monthlySD = Pointage::betweenDates($startOfMonth->toDateString(), $now->toDateString())
                        ->whereNotNull('heure_entree')
                        ->whereHas('agent', fn($q) => $q->where('organe', $nom)->whereNull('departement_id'))
                        ->select(DB::raw('COUNT(DISTINCT agent_id) as present, date_pointage'))
                        ->groupBy('date_pointage')->get();
                    $monthlyRateSD = $monthlySD->count() > 0 ? round(($monthlySD->avg('present') / $sansDeptActifsCount) * 100, 1) : 0;

                    $items[] = [
                        'id' => null,
                        'nom' => 'SEN (rattachement direct)',
                        'code' => 'SEN-DIRECT',
                        'effectifs' => ['total' => $sansDeptTotal, 'actifs' => $sansDeptActifs, 'suspendus' => $sansDeptSuspendus],
                        'presence' => [
                            'today_present' => $todayPresentSD,
                            'today_rate' => round(($todayPresentSD / $sansDeptActifsCount) * 100, 1),
                            'monthly_rate' => $monthlyRateSD,
                            'total_active' => $sansDeptActifs,
                        ],
                        'pta' => ['total' => 0, 'terminee' => 0, 'avg' => 0],
                    ];
                }
            }

"""

# Insert before usort (at the beginning of the usort line)
new_content = content[:usort_pos] + insert_code + content[usort_pos:]

with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
    f.write(new_content)

print("Code inserted successfully!")
print(f"New file size: {len(new_content)} chars")
