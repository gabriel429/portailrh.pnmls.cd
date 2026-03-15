<?php

namespace App\Http\Controllers;

use App\Models\ActivitePlan;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Department;
use App\Models\Province;
use App\Models\Localite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PlanTravailController extends Controller
{
    /**
     * Vérifie si l'utilisateur peut gérer le PTA.
     *
     * - Admin : toujours autorisé
     * - SEN   : Chef de Cellule / Chef de Section Planification
     * - SEP   : Chef de Cellule Planification, Suivi-Évaluation et Renforcement des Capacités
     * - SEL   : Assistant Technique
     */
    private function canManage(): bool
    {
        $user = auth()->user();

        if ($user->hasAdminAccess()) {
            return true;
        }

        $agent = $user->agent;
        if (!$agent) {
            return false;
        }

        // Vérifier via l'affectation active de l'agent
        if (Schema::hasTable('affectations') && Schema::hasTable('fonctions')) {
            $affectationActive = Affectation::where('agent_id', $agent->id)
                ->where('actif', true)
                ->with('fonction')
                ->first();

            if ($affectationActive && $affectationActive->fonction) {
                $nomFonction = mb_strtolower($affectationActive->fonction->nom);
                $organe = $agent->organe ?? '';

                // SEN : fonctions contenant "planification"
                if (str_contains($organe, 'National') && str_contains($nomFonction, 'planification')) {
                    return true;
                }

                // SEP : Chef de Cellule Planification, Suivi-Évaluation
                if (str_contains($organe, 'Provincial') && str_contains($nomFonction, 'planification')) {
                    return true;
                }

                // SEL : Assistant Technique
                if (str_contains($organe, 'Local') && str_contains($nomFonction, 'assistant technique')) {
                    return true;
                }
            }
        }

        // Fallback : vérifier le champ texte "fonction" de l'agent
        $fonctionAgent = mb_strtolower($agent->fonction ?? '');
        $organe = $agent->organe ?? '';

        if (str_contains($organe, 'National') && str_contains($fonctionAgent, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Provincial') && str_contains($fonctionAgent, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Local') && str_contains($fonctionAgent, 'assistant technique')) {
            return true;
        }

        return false;
    }

    private function scopeQuery($query, $agent)
    {
        $organe = $agent->organe ?? '';

        if (str_contains($organe, 'National')) {
            $query->where('niveau_administratif', 'SEN');
            if ($agent->departement_id) {
                $query->where(function ($q) use ($agent) {
                    $q->where('departement_id', $agent->departement_id)
                      ->orWhereNull('departement_id');
                });
            }
        } elseif (str_contains($organe, 'Provincial')) {
            $query->where('niveau_administratif', 'SEP')
                  ->where('province_id', $agent->province_id);
        } elseif (str_contains($organe, 'Local')) {
            $query->where('niveau_administratif', 'SEL')
                  ->where('province_id', $agent->province_id);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $agent = $user->agent;
        $annee = $request->input('annee', now()->year);
        $trimestre = $request->input('trimestre');
        $statut = $request->input('statut');

        $query = ActivitePlan::with('createur', 'departement', 'province', 'localite')
            ->parAnnee($annee);

        if ($agent) {
            $this->scopeQuery($query, $agent);
        }

        if ($trimestre) {
            $query->parTrimestre($trimestre);
        }

        if ($statut) {
            $query->where('statut', $statut);
        }

        $activites = $query->orderByRaw("FIELD(trimestre, 'T1','T2','T3','T4')")->latest()->get();

        $totalCount = $activites->count();
        $planifieeCount = $activites->where('statut', 'planifiee')->count();
        $enCoursCount = $activites->where('statut', 'en_cours')->count();
        $termineeCount = $activites->where('statut', 'terminee')->count();
        $avgPourcentage = $totalCount > 0 ? round($activites->avg('pourcentage')) : 0;

        $activitesGroupees = $activites->groupBy(fn($a) => $a->trimestre ?? 'Annuel');

        $canEdit = $this->canManage();

        return view('plan-travail.index', compact(
            'activites', 'activitesGroupees', 'annee', 'trimestre', 'statut',
            'totalCount', 'planifieeCount', 'enCoursCount', 'termineeCount', 'avgPourcentage',
            'canEdit'
        ));
    }

    public function create()
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $agent = auth()->user()->agent;
        $departments = Department::orderBy('nom')->get();
        $provinces = Province::orderBy('nom')->get();
        $localites = Localite::orderBy('nom')->get();
        $annee = now()->year;

        return view('plan-travail.create', compact('departments', 'provinces', 'localites', 'annee'));
    }

    public function store(Request $request)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $validated = $request->validate([
            'titre'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'departement_id'       => 'nullable|exists:departments,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'annee'                => 'required|integer|min:2020|max:2040',
            'trimestre'            => 'nullable|in:T1,T2,T3,T4',
            'statut'               => 'required|in:planifiee,en_cours,terminee',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date',
            'pourcentage'          => 'integer|min:0|max:100',
            'observations'         => 'nullable|string',
        ]);

        $validated['createur_id'] = auth()->user()->agent->id;

        ActivitePlan::create($validated);

        return redirect()->route('plan-travail.index')
            ->with('success', 'Activite creee avec succes.');
    }

    public function show(ActivitePlan $activitePlan)
    {
        $activitePlan->load('createur', 'departement', 'province', 'localite');
        $canEdit = $this->canManage();

        return view('plan-travail.show', compact('activitePlan', 'canEdit'));
    }

    public function edit(ActivitePlan $activitePlan)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $departments = Department::orderBy('nom')->get();
        $provinces = Province::orderBy('nom')->get();
        $localites = Localite::orderBy('nom')->get();

        return view('plan-travail.create', compact('activitePlan', 'departments', 'provinces', 'localites'));
    }

    public function update(Request $request, ActivitePlan $activitePlan)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $validated = $request->validate([
            'titre'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'departement_id'       => 'nullable|exists:departments,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'annee'                => 'required|integer|min:2020|max:2040',
            'trimestre'            => 'nullable|in:T1,T2,T3,T4',
            'statut'               => 'required|in:planifiee,en_cours,terminee',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date',
            'pourcentage'          => 'integer|min:0|max:100',
            'observations'         => 'nullable|string',
        ]);

        $activitePlan->update($validated);

        return redirect()->route('plan-travail.show', $activitePlan)
            ->with('success', 'Activite mise a jour.');
    }

    public function destroy(ActivitePlan $activitePlan)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $activitePlan->delete();

        return redirect()->route('plan-travail.index')
            ->with('success', 'Activite supprimee.');
    }

    public function updateStatut(Request $request, ActivitePlan $activitePlan)
    {
        if (!$this->canManage()) {
            abort(403);
        }

        $validated = $request->validate([
            'statut'       => 'required|in:planifiee,en_cours,terminee',
            'pourcentage'  => 'integer|min:0|max:100',
            'observations' => 'nullable|string',
        ]);

        $activitePlan->update($validated);

        return redirect()->route('plan-travail.show', $activitePlan)
            ->with('success', 'Statut mis a jour.');
    }
}
