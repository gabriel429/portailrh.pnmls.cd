<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RequestResource;
use App\Models\Request as RequestModel;
use App\Models\Agent;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\UserDataScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends ApiController
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Display a paginated listing of requests.
     * RH staff see all requests; regular agents see only their own.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isRH = $user->hasAdminAccess();
        $scope = $this->scopeService();

        $query = RequestModel::with(['agent']);
        $scope->applyRequestScope($query, $user);

        // Filter by statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $requests = $query->latest()->paginate(15);

        return $this->paginated($requests, RequestResource::class, [], [
            'isRH' => $isRH,
        ]);
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $isRH = $user->hasAdminAccess();
        $scope = $this->scopeService();

        $rules = [
            'type' => 'required|string',
            'description' => 'required|string',
            'motivation' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lettre_demande' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ];

        // RH can select any agent; regular users submit for themselves
        if ($isRH) {
            $rules['agent_id'] = 'required|exists:agents,id';
        }

        // Motivation required for renforcement_capacites
        if ($request->input('type') === 'renforcement_capacites') {
            $rules['motivation'] = 'required|string|min:50';
        }

        $validated = $request->validate($rules);

        // If not RH, force agent_id to current user's agent
        if (!$isRH) {
            $agent = $user->agent;
            if (!$agent) {
                return response()->json([
                    'message' => 'Votre compte n\'est pas associé à un agent.',
                ], 422);
            }
            $validated['agent_id'] = $agent->id;
        } else {
            $selectedAgent = Agent::find($validated['agent_id']);

            if (!$scope->canAccessAgent($user, $selectedAgent)) {
                return response()->json([
                    'message' => 'Acces refuse pour cet agent.',
                ], 403);
            }
        }

        // Handle file upload
        if ($request->hasFile('lettre_demande')) {
            $validated['lettre_demande'] = $request->file('lettre_demande')
                ->store('lettres_demandes', 'public');
        }

        $demande = RequestModel::create($validated);

        // Notify RH staff of a new request
        $agent = Agent::find($validated['agent_id']);
        $nomAgent = $agent ? $agent->prenom . ' ' . $agent->nom : 'Un agent';
        NotificationService::notifierRH(
            'demande',
            'Nouvelle demande de ' . $validated['type'],
            $nomAgent . ' a soumis une demande de ' . $validated['type'] . '.',
            '/requests/' . $demande->id,
            $user->id
        );

        $demande->load('agent');
        $resource = RequestResource::make($demande);

        return $this->resource($resource, [], [
            'message' => 'Demande créée avec succès.',
        ], 201);
    }

    /**
     * Display a single request with its agent.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $demande = RequestModel::with(['agent'])->findOrFail($id);

        $this->authorizeAccess($request->user(), $demande);

        return $this->resource(RequestResource::make($demande), [], [
            'isRH' => $request->user()->hasAdminAccess(),
            'isOwner' => $request->user()->agent?->id === $demande->agent_id,
        ]);
    }

    /**
     * Update a request (status change by RH staff).
     */
    public function update(Request $httpRequest, int $id): JsonResponse
    {
        $demande = RequestModel::with(['agent'])->findOrFail($id);
        $user = $httpRequest->user();

        // Only RH staff can update request status
        if (!$user->hasAdminAccess()) {
            return response()->json([
                'message' => 'Seuls les agents RH peuvent modifier le statut d\'une demande.',
            ], 403);
        }

        // For "renforcement_capacites" requests, only specific roles can approve
        if ($demande->type === 'renforcement_capacites') {
            $agent = $user->agent;
            if (!$agent) {
                return response()->json([
                    'message' => 'Votre compte n\'est pas associé à un agent.',
                ], 403);
            }

            $fonction = strtolower($agent->fonction ?? '');
            $poste = strtolower($agent->poste_actuel ?? '');

            // Check if agent is Chef de Section or Chef de Cellule for Renforcement des Capacités
            $isChefSection = str_contains($fonction, 'chef de section') || str_contains($poste, 'chef de section');
            $isChefCellule = str_contains($fonction, 'chef de cellule') || str_contains($poste, 'chef de cellule');
            $isRenforcement = str_contains($fonction, 'renforcement') || str_contains($poste, 'renforcement');

            if (!($isRenforcement && ($isChefSection || $isChefCellule))) {
                return response()->json([
                    'message' => 'Seuls les Chefs de Section ou Chefs de Cellule Renforcement des Capacités peuvent traiter ce type de demande.',
                ], 403);
            }
        }

        $validated = $httpRequest->validate([
            'statut' => 'required|in:en_attente,approuvé,rejeté,annulé',
            'remarques' => 'nullable|string',
        ]);

        $demande->update($validated);

        // Notify the agent of the status change
        $agent = $demande->agent;
        if ($agent) {
            $agentUser = User::where('agent_id', $agent->id)->first();
            if ($agentUser) {
                $type = match ($validated['statut']) {
                    'approuvé' => 'demande_approuvee',
                    'rejeté' => 'demande_rejetee',
                    default => 'demande_modifiee',
                };
                $titre = match ($validated['statut']) {
                    'approuvé' => 'Demande approuvée',
                    'rejeté' => 'Demande rejetée',
                    default => 'Demande mise à jour',
                };
                NotificationService::envoyer(
                    $agentUser->id,
                    $type,
                    $titre,
                    'Votre demande de ' . $demande->type . ' a été ' . $validated['statut'] . '.',
                    '/requests/' . $demande->id,
                    $user->id
                );
            }
        }

        $resource = RequestResource::make($demande->fresh()->load('agent'));

        return $this->resource($resource, [], [
            'message' => 'Demande modifiée avec succès.',
        ]);
    }

    /**
     * Delete a request (only if en_attente and owned by agent, or RH).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $demande = RequestModel::findOrFail($id);
        $user = $request->user();

        $this->authorizeAccess($user, $demande);

        // Regular agents can only delete their own pending requests
        if (!$user->hasAdminAccess()) {
            if ($demande->statut !== 'en_attente') {
                return response()->json([
                    'message' => 'Vous ne pouvez supprimer qu\'une demande en attente.',
                ], 403);
            }
        }

        $demande->delete();

        return $this->success(null, [], [
            'message' => 'Demande supprimée avec succès.',
        ]);
    }

    /**
     * Verify that the current user can access the given request.
     */
    private function authorizeAccess($user, RequestModel $demande): void
    {
        if ($this->scopeService()->canAccessRequest($user, $demande)) {
            return;
        }

        abort(403, 'Vous n\'avez pas acces a cette demande.');
    }
}
