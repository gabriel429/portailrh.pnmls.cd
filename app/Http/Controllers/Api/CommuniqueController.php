<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communique;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommuniqueController extends Controller
{
    /**
     * Display a paginated listing of communiques.
     */
    public function index(Request $request): JsonResponse
    {
        $communiques = Communique::with('auteur')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $communiques->items(),
            'meta' => [
                'current_page' => $communiques->currentPage(),
                'last_page' => $communiques->lastPage(),
                'per_page' => $communiques->perPage(),
                'total' => $communiques->total(),
                'from' => $communiques->firstItem(),
                'to' => $communiques->lastItem(),
            ],
        ]);
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
            NotificationService::notifierTous(
                'communique',
                'Nouveau communique : ' . $communique->titre,
                'Un nouveau communique a ete publie' . ($communique->urgence !== 'normal' ? ' (' . strtoupper($communique->urgence) . ')' : '') . '.',
                '/communiques/' . $communique->id,
                auth()->id()
            );
        }

        return response()->json([
            'message' => 'Communique publie avec succes.',
            'data' => $communique->load('auteur'),
        ], 201);
    }

    /**
     * Display the specified communique.
     */
    public function show(Communique $communique): JsonResponse
    {
        $communique->load('auteur');

        return response()->json([
            'data' => $communique,
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

        return response()->json([
            'message' => 'Communique mis a jour avec succes.',
            'data' => $communique->fresh()->load('auteur'),
        ]);
    }

    /**
     * Remove the specified communique.
     */
    public function destroy(Communique $communique): JsonResponse
    {
        $communique->delete();

        return response()->json([
            'message' => 'Communique supprime.',
        ]);
    }
}
