<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ActivitePlanResource;
use App\Models\ActivitePlan;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Department;
use App\Models\Province;
use App\Models\Localite;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PlanTravailController extends ApiController
{
    /**
     * Check if user can manage PTA (create, edit, delete).
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

        $nomFonction = $this->getNomFonctionAgent($agent);
        $organe = $agent->organe ?? '';

        if (str_contains($organe, 'National') && str_contains($nomFonction, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Provincial') && str_contains($nomFonction, 'planification')) {
            return true;
        }
        if (str_contains($organe, 'Local') && str_contains($nomFonction, 'assistant technique')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update the status of a PTA activity.
     */
    private function canUpdateStatut(ActivitePlan $activite): bool
    {
        $user = auth()->user();
        if ($user->hasAdminAccess()) {
            return true;
        }
        if ($this->canManage()) {
            return true;
        }

        $agent = $user->agent;
        if (!$agent) {
            return false;
        }

        $nomFonction = $this->getNomFonctionAgent($agent);
        $organe = $agent->organe ?? '';

        // SEN level
        if (str_contains($organe, 'National') && $activite->niveau_administratif === 'SEN') {
            if (str_contains($nomFonction, 'directeur') || str_contains($nomFonction, 'chef de département')) {
                if ($agent->departement_id && $activite->departement_id === $agent->departement_id) {
                    return true;
                }
            }
            if (str_contains($nomFonction, 'assistant de département') || str_contains($nomFonction, 'secrétaire de département')) {
                if ($agent->departement_id && $activite->departement_id === $agent->departement_id) {
                    return true;
                }
            }
            if ((str_contains($nomFonction, 'assistant') && str_contains($nomFonction, 'direction'))
                || str_contains($nomFonction, 'secrétaire de direction')) {
                if (!$activite->departement_id) {
                    return true;
                }
            }
        }

        // SEP level
        if (str_contains($organe, 'Provincial') && $activite->niveau_administratif === 'SEP') {
            if ($agent->province_id && $activite->province_id === $agent->province_id) {
                if (str_contains($nomFonction, 'secrétaire exécutif provincial') || str_contains($nomFonction, 'sep')) {
                    return true;
                }
                if (str_contains($nomFonction, 'planification')) {
                    return true;
                }
            }
        }

        // SEL level
        if (str_contains($organe, 'Local') && $activite->niveau_administratif === 'SEL') {
            if (str_contains($nomFonction, 'assistant technique')) {
                return true;
            }
        }

        return false;
    }

    private function getNomFonctionAgent(Agent $agent): string
    {
        if (Schema::hasTable('affectations') && Schema::hasTable('fonctions')) {
            $affectationActive = Affectation::where('agent_id', $agent->id)
                ->where('actif', true)
                ->with('fonction')
                ->first();

            if ($affectationActive && $affectationActive->fonction) {
                return mb_strtolower($affectationActive->fonction->nom);
            }
        }

        return mb_strtolower($agent->fonction ?? '');
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

    /**
     * Display listing of PTA activities.
     */
    public function index(Request $request): JsonResponse
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

        $resources = ActivitePlanResource::collection($activites)->resolve();
        $activitesGroupees = collect($resources)->groupBy(fn($a) => $a['trimestre'] ?? 'Annuel')->toArray();

        return $this->success($resources, [
            'stats' => [
                'total' => $totalCount,
                'planifiee' => $planifieeCount,
                'en_cours' => $enCoursCount,
                'terminee' => $termineeCount,
                'avg_pourcentage' => $avgPourcentage,
            ],
            'filters' => [
                'annee' => (int) $annee,
                'trimestre' => $trimestre,
                'statut' => $statut,
            ],
            'canEdit' => $this->canManage(),
        ], [
            'groupees' => $activitesGroupees,
            'stats' => [
                'total' => $totalCount,
                'planifiee' => $planifieeCount,
                'en_cours' => $enCoursCount,
                'terminee' => $termineeCount,
                'avg_pourcentage' => $avgPourcentage,
            ],
            'filters' => [
                'annee' => (int) $annee,
                'trimestre' => $trimestre,
                'statut' => $statut,
            ],
            'canEdit' => $this->canManage(),
        ]);
    }

    /**
     * Return data for the create form.
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $departments = Department::orderBy('nom')->get(['id', 'nom']);
        $provinces = Province::orderBy('nom')->get(['id', 'nom']);
        $localites = class_exists(Localite::class) ? Localite::orderBy('nom')->get(['id', 'nom']) : collect();

        $payload = [
            'departments' => $departments,
            'provinces' => $provinces,
            'localites' => $localites,
            'annee' => now()->year,
        ];

        return $this->success($payload, [], $payload);
    }

    /**
     * Store a new PTA activity.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
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

        $activite = ActivitePlan::create($validated);

        NotificationService::notifierTous(
            'plan_travail',
            'Nouvelle activite PTA : ' . $activite->titre,
            'Une nouvelle activite a ete ajoutee au Plan de Travail Annuel (' . $activite->annee . ' ' . ($activite->trimestre ?? '') . ').',
            '/plan-travail/' . $activite->id,
            auth()->id()
        );

        $resource = ActivitePlanResource::make($activite->load('createur', 'departement', 'province', 'localite'));

        return $this->resource($resource, [], [
            'message' => 'Activite creee avec succes.',
        ], 201);
    }

    /**
     * Display a single PTA activity.
     */
    public function show(ActivitePlan $activitePlan): JsonResponse
    {
        $activitePlan->load('createur', 'departement', 'province', 'localite');

        return $this->resource(ActivitePlanResource::make($activitePlan), [
            'canEdit' => $this->canManage(),
            'canUpdateStatut' => $this->canUpdateStatut($activitePlan),
        ], [
            'canEdit' => $this->canManage(),
            'canUpdateStatut' => $this->canUpdateStatut($activitePlan),
        ]);
    }

    /**
     * Update a PTA activity.
     */
    public function update(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
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

        NotificationService::notifierTous(
            'plan_travail',
            'PTA mis a jour : ' . $activitePlan->titre,
            'L\'activite "' . $activitePlan->titre . '" a ete mise a jour (' . $activitePlan->statut . ', ' . $activitePlan->pourcentage . '%).',
            '/plan-travail/' . $activitePlan->id,
            auth()->id()
        );

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'localite'));

        return $this->resource($resource, [], [
            'message' => 'Activite mise a jour.',
        ]);
    }

    /**
     * Remove a PTA activity.
     */
    public function destroy(ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $activitePlan->delete();

        return $this->success(null, [], [
            'message' => 'Activite supprimee.',
        ]);
    }

    /**
     * Quick status update for a PTA activity.
     */
    public function updateStatut(Request $request, ActivitePlan $activitePlan): JsonResponse
    {
        if (!$this->canUpdateStatut($activitePlan)) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'statut'       => 'required|in:planifiee,en_cours,terminee',
            'pourcentage'  => 'integer|min:0|max:100',
            'observations' => 'nullable|string',
        ]);

        $activitePlan->update($validated);

        $resource = ActivitePlanResource::make($activitePlan->fresh()->load('createur', 'departement', 'province', 'localite'));

        return $this->resource($resource, [], [
            'message' => 'Statut mis a jour.',
        ]);
    }
}
