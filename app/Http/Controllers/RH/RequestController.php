<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\Agent;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = auth()->user();
        $agent = $user->agent ?? null;
        $isRH = $user->hasAdminAccess();
        $requestsQuery = RequestModel::with(['agent']);

        // Seuls les RH voient toutes les demandes, les autres ne voient que les leurs
        if (!$isRH) {
            $requestsQuery->where('agent_id', $agent?->id);
        }

        $requests = $requestsQuery->latest()->paginate(15);
        return view('rh.requests.index', compact('requests', 'isRH'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = auth()->user();
        $isRH = $user->hasAdminAccess();

        // RH peut sélectionner n'importe quel agent, sinon seulement soi-même
        $agents = $isRH
            ? Agent::actifs()->get()
            : collect($user->agent ? [$user->agent] : []);

        return view('rh.requests.create', compact('agents', 'isRH'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'type' => 'required|string',
            'description' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lettre_demande' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('lettre_demande')) {
            $validated['lettre_demande'] = $request->file('lettre_demande')
                ->store('lettres_demandes', 'public');
        }

        $demande = RequestModel::create($validated);

        // Notifier les RH d'une nouvelle demande
        $agent = Agent::find($validated['agent_id']);
        $nomAgent = $agent ? $agent->prenom . ' ' . $agent->nom : 'Un agent';
        NotificationService::notifierRH(
            'demande',
            'Nouvelle demande de ' . $validated['type'],
            $nomAgent . ' a soumis une demande de ' . $validated['type'] . '.',
            '/requests/' . $demande->id,
            auth()->id()
        );

        return redirect()->route('requests.index')
            ->with('success', 'Demande créée avec succès');
    }

    /**
     * Check if user can access this request (owner or RH).
     */
    private function authorizeAccess(RequestModel $request): void
    {
        $user = auth()->user();
        if ($user->hasAdminAccess()) {
            return;
        }
        $agent = $user->agent ?? null;
        if (!$agent || $request->agent_id !== $agent->id) {
            abort(403, 'Vous n\'avez pas accès à cette demande.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestModel $request): View
    {
        $this->authorizeAccess($request);
        return view('rh.requests.show', compact('request'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequestModel $request): View
    {
        $this->authorizeAccess($request);
        $agents = Agent::actifs()->get();

        return view('rh.requests.edit', compact('request', 'agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $httpRequest, RequestModel $request): RedirectResponse
    {
        $this->authorizeAccess($request);
        $validated = $httpRequest->validate([
            'statut' => 'required|in:en_attente,approuvé,rejeté,annulé',
            'remarques' => 'nullable|string',
        ]);

        $oldStatut = $request->statut;
        $request->update($validated);

        // Notifier l'agent du changement de statut
        $agent = $request->agent;
        if ($agent) {
            $user = User::where('agent_id', $agent->id)->first();
            if ($user) {
                $type = match($validated['statut']) {
                    'approuvé' => 'demande_approuvee',
                    'rejeté' => 'demande_rejetee',
                    default => 'demande_modifiee',
                };
                $titre = match($validated['statut']) {
                    'approuvé' => 'Demande approuvée',
                    'rejeté' => 'Demande rejetée',
                    default => 'Demande mise à jour',
                };
                NotificationService::envoyer(
                    $user->id,
                    $type,
                    $titre,
                    'Votre demande de ' . $request->type . ' a été ' . $validated['statut'] . '.',
                    '/requests/' . $request->id,
                    auth()->id()
                );
            }
        }

        return redirect()->route('requests.show', $request)
            ->with('success', 'Demande modifiée avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequestModel $request): RedirectResponse
    {
        $this->authorizeAccess($request);
        $request->delete();

        return redirect()->route('requests.index')
            ->with('success', 'Demande supprimée avec succès');
    }
}
