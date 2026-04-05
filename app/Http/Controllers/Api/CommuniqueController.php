<?php

namespace App\Http\Controllers\Api;

use App\Events\CommuniquePublished;
use App\Http\Resources\CommuniqueResource;
use App\Models\Communique;
use App\Services\NotificationService;
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

        // Notifier tous les utilisateurs du nouveau communique
        if ($communique->actif) {
            CommuniquePublished::dispatch($communique);
            NotificationService::notifierTous(
                'communique',
                'Nouveau communique : ' . $communique->titre,
                'Un nouveau communique a ete publie' . ($communique->urgence !== 'normal' ? ' (' . strtoupper($communique->urgence) . ')' : '') . '.',
                '/communiques/' . $communique->id,
                auth()->id()
            );
        }

        $communique->load('auteur');
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

        return $this->resource(CommuniqueResource::make($communique));
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

        $resource = CommuniqueResource::make($communique->fresh()->load('auteur'));

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
