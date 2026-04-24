# -*- coding: utf-8 -*-
import re

filepath = 'app/Http/Controllers/Api/ExecutiveDashboardController.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# The target is: usort($items, fn($a, $b) => $b['effectifs']['total'] - $a['effectifs']['total']);
# This appears twice:
# 1. For SEP (L1020) - line ends with ');' after the usort
# 2. For SEN/SEL (L1071) - same pattern

# We want to add code BEFORE the second occurrence (SEN/SEL one)

# Find second occurrence
first_idx = content.find("usort($items, fn($a, $b) => $b['effectifs']['total'] - $a['effectifs']['total']);")
if first_idx > 0:
    second_idx = content.find("usort($items, fn($a, $b) => $b['effectifs']['total'] - $a['effectifs']['total']);", first_idx + 1)
    
    if second_idx > 0:
        # Find the line number
        line_start = content.rfind('\n', 0, second_idx) + 1
        indent = ''
        # Get indentation from the line
        i = line_start
        while content[i] in ' \t':
            indent += content[i]
            i += 1
        
        # Code to insert before usort
        insert_code = f"""            // Agents SEN sans département (rattachés directement au niveau national)
            if ($code === 'SEN') {{
                $sansDeptAgents = Agent::where('organe', $nom)->whereNull('departement_id');
                $sansDeptTotal = (clone $sansDeptAgents)->count();
                $sansDeptActifs = (clone $sansDeptAgents)->actifs()->count();
                $sansDeptSuspendus = (clone $sansDeptAgents)->suspendu()->count();

                if ($sansDeptTotal > 0) {{
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
                }}
            }}

"""
        # Insert the code before the usort
        new_content = content[:line_start] + insert_code + content[line_start:]
        
        with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
            f.write(new_content)
        
        print(f"Code inserted at position {line_start} (before second usort)")
        print("Done!")
    else:
        print("Second usort not found!")
else:
    print("usort not found!")
