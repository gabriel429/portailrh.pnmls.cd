<?php

namespace App\Http\Controllers\Api;

use App\Events\CommuniquePublished;
use App\Http\Resources\CommuniqueResource;
use App\Models\Communique;
use App\Models\CommuniqueRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommuniqueController extends ApiController
{
    /**
     * Display a paginated listing of communiques.
     */
    public function index(Request $request): JsonResponse
    {
        $communiques = Communique::with('auteur')
            ->withCount('reads')
            ->latest()
            ->paginate(15);

        return $this->paginated($communiques, CommuniqueResource::class);
    }

    /**
     * Store a newly created communique.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'urgence' => 'required|in:normal,important,urgent',
            'signataire' => 'nullable|string|max:255',
            'date_expiration' => 'nullable|date|after_or_equal:today',
            'actif' => 'nullable|boolean',
        ]);

        $validated['auteur_id'] = auth()->id();
        $validated['actif'] = $request->boolean('actif', true);

        $communique = Communique::create($validated);

        if ($communique->actif) {
            CommuniquePublished::dispatch($communique);
        }

        $communique->load('auteur');
        $communique->loadCount('reads');
        $resource = CommuniqueResource::make($communique);

        return $this->resource($resource, [], [
            'message' => 'Communique publie avec succes.',
        ], 201);
    }

    /**
     * Display the specified communique.
     */
    public function show(Communique $communique): JsonResponse
    {
        $communique->load('auteur');
        $communique->loadCount('reads');

        return $this->resource(CommuniqueResource::make($communique));
    }

    public function markRead(Request $request, Communique $communique): JsonResponse
    {
        CommuniqueRead::updateOrCreate(
            [
                'communique_id' => $communique->id,
                'user_id' => $request->user()->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        return $this->success([
            'read' => true,
            'communique_id' => $communique->id,
        ], [], [
            'message' => 'Communique marque comme lu.',
        ]);
    }

    public function readers(Request $request, Communique $communique): JsonResponse
    {
        $user = $request->user();

        if (
            (int) $communique->auteur_id !== (int) $user->id
            && ! $user->isSuperAdmin()
            && ! $user->hasRole(['SEN'])
        ) {
            abort(403, 'Vous ne pouvez pas consulter les lectures de ce communique.');
        }

        $reads = $communique->reads()
            ->with(['user.role', 'user.agent.departement.province', 'user.agent.province'])
            ->latest('read_at')
            ->get()
            ->map(function (CommuniqueRead $read) {
                $agent = $read->user?->agent;

                return [
                    'id' => $read->id,
                    'read_at' => optional($read->read_at)?->toIso8601String(),
                    'user' => [
                        'id' => $read->user?->id,
                        'name' => $agent?->nom_complet ?: $read->user?->name,
                        'email' => $read->user?->email,
                        'role' => $read->user?->role?->nom_role,
                        'departement' => $agent?->departement?->nom,
                        'province' => $agent?->province?->nom ?: $agent?->departement?->province?->nom,
                        'poste' => $agent?->poste_actuel,
                    ],
                ];
            });

        return $this->success($reads, [
            'total' => $reads->count(),
        ]);
    }

    /**
     * Update the specified communique.
     */
    public function update(Request $request, Communique $communique): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'urgence' => 'required|in:normal,important,urgent',
            'signataire' => 'nullable|string|max:255',
            'date_expiration' => 'nullable|date',
            'actif' => 'nullable|boolean',
        ]);

        $validated['actif'] = $request->boolean('actif', false);

        $communique->update($validated);

        $resource = CommuniqueResource::make($communique->fresh()->load('auteur')->loadCount('reads'));

        return $this->resource($resource, [], [
            'message' => 'Communique mis a jour avec succes.',
        ]);
    }

    /**
     * Remove the specified communique.
     */
    public function destroy(Communique $communique): JsonResponse
    {
        $communique->delete();

        return $this->success(null, [], [
            'message' => 'Communique supprime.',
        ]);
    }
}
