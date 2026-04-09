                // DEBUG: Log planning pour l'année demandée
                \Log::info('Planning chargé', [
                    'structure_id' => $structure ? $structure->id : null,
                    'year' => $year,
                    'planning' => $planning,
                ]);
        // DEBUG: Log planning pour l'année demandée
        \Log::info('Planning chargé', [
            'structure_id' => $structure ? $structure->id : null,
            'year' => $year,
            'planning' => $planning,
        ]);
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\HolidayPlanning;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MyHolidayPlanningController extends Controller
{
    /**
     * Planning de congés de la structure de l'agent connecté (lecture seule)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return response()->json(['message' => 'Aucun agent associé à ce compte.'], 404);
        }

        $structure = $this->resolveAgentStructure($agent);

        if (!$structure) {
            return response()->json([
                'structure' => null,
                'planning' => null,
                'colleagues' => [],
                'stats' => null,
                'message' => 'Votre structure n\'a pas pu être identifiée. Veuillez contacter la Section RH.',
            ]);
        }

        $year = $request->get('year', date('Y'));

        // Vérifier que la table existe
        if (!Schema::hasTable('holiday_plannings')) {
            return response()->json([
                'structure' => $structure,
                'planning' => null,
                'colleagues' => [],
                'stats' => null,
                'message' => 'Le module congés n\'est pas encore déployé.',
            ]);
        }

        // Récupérer le planning de la structure
        $planning = HolidayPlanning::with(['createdBy', 'validatedBy'])
            ->forYear($year)
            ->forStructure($structure['type'], $structure['id'])
            ->first();

        // DEBUG: Log planning pour l'année demandée
        \Log::info('Planning chargé', [
            'structure_id' => $structure ? $structure->id : null,
            'year' => $year,
            'planning' => $planning,
        ]);

        // Congés approuvés des collègues (même structure, même année)
        $colleagues = [];
        if ($planning && Schema::hasTable('holidays')) {
            $colleagues = Holiday::with(['agent:id,nom,postnom,prenom,fonction'])
                ->where('holiday_planning_id', $planning->id)
                ->where('statut_demande', 'approuve')
                ->select([
                    'id', 'agent_id', 'holiday_planning_id',
                    'type_conge', 'date_debut', 'date_fin',
                    'nombre_jours', 'statut_demande',
                ])
                ->orderBy('date_debut', 'desc')
                ->get();
        }

        // DEBUG: Log planning pour l'année demandée
        \Log::info('Planning chargé', [
            'structure_id' => $structure ? $structure->id : null,
            'year' => $year,
            'planning' => $planning,
        ]);

        // Statistiques
        $stats = null;
        if ($planning) {
            $restants = $planning->jours_conge_totaux - $planning->jours_utilises;
            $taux = $planning->jours_conge_totaux > 0
                ? round(($planning->jours_utilises * 100) / $planning->jours_conge_totaux, 1)
                : 0;

            $stats = [
                'jours_totaux' => $planning->jours_conge_totaux,
                'jours_utilises' => $planning->jours_utilises,
                'jours_restants' => max(0, $restants),
                'taux' => $taux,
            ];
        }

        // Mon congé planifié (approuvé ou en attente) pour cette année
        $myHolidays = [];
        if (Schema::hasTable('holidays')) {
            $myHolidays = Holiday::with(['interimPar:id,nom,postnom,prenom,fonction'])
                ->where('agent_id', $agent->id)
                ->whereYear('date_debut', $year)
                ->whereIn('statut_demande', ['en_attente', 'approuve'])
                ->select(['id', 'agent_id', 'type_conge', 'date_debut', 'date_fin', 'nombre_jours', 'statut_demande', 'observation', 'interim_assure_par', 'holiday_planning_id'])
                ->orderBy('date_debut')
                ->get();
        }

        // Intérims : congés où je suis désigné comme intérimaire
        $myInterims = [];
        if (Schema::hasTable('holidays')) {
            $myInterims = Holiday::with(['agent:id,nom,postnom,prenom,fonction'])
                ->where('interim_assure_par', $agent->id)
                ->whereYear('date_debut', $year)
                ->whereIn('statut_demande', ['en_attente', 'approuve'])
                ->select(['id', 'agent_id', 'type_conge', 'date_debut', 'date_fin', 'nombre_jours', 'statut_demande', 'observation', 'interim_assure_par', 'holiday_planning_id'])
                ->orderBy('date_debut')
                ->get();
        }

        return response()->json([
            'structure' => $structure,
            'planning' => $planning,
            'colleagues' => $colleagues,
            'stats' => $stats,
            'my_holidays' => $myHolidays,
            'my_interims' => $myInterims,
            'year' => (int) $year,
        ]);
    }

    /**
     * Résoudre la structure de l'agent (département, SEN, SEP, SEL)
     */
    private function resolveAgentStructure(Agent $agent): ?array
    {
        // Priorité 1 : agent rattaché à un département
        if ($agent->departement_id) {
            $nom = $agent->departement?->nom ?? 'Département #' . $agent->departement_id;
            return [
                'type' => 'department',
                'id' => $agent->departement_id,
                'nom' => $nom,
            ];
        }

        // Priorité 2 : mapping par organe
        if (!$agent->organe) {
            return null;
        }

        $organe = $agent->organe;

        // Gérer avec et sans accents
        $senValues = ['Secrétariat Exécutif National', 'Secretariat Executif National'];
        $sepValues = ['Secrétariat Exécutif Provincial', 'Secretariat Executif Provincial'];
        $selValues = ['Secrétariat Exécutif Local', 'Secretariat Executif Local'];

        if (in_array($organe, $senValues)) {
            return [
                'type' => 'sen',
                'id' => 1,
                'nom' => 'Secrétariat Exécutif National',
            ];
        }

        if (in_array($organe, $sepValues) && $agent->province_id) {
            $nom = $agent->province?->nom ?? 'SEP Province #' . $agent->province_id;
            return [
                'type' => 'sep',
                'id' => $agent->province_id,
                'nom' => 'SEP — ' . $nom,
            ];
        }

        if (in_array($organe, $selValues) && $agent->province_id) {
            $nom = $agent->province?->nom ?? 'SEL Province #' . $agent->province_id;
            return [
                'type' => 'local',
                'id' => $agent->province_id,
                'nom' => 'SEL — ' . $nom,
            ];
        }

        return null;
    }
}
