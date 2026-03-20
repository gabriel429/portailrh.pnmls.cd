<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * List paginated documents for the authenticated user's agent.
     */
    public function index(Request $request)
    {
        $agent = $request->user()->agent;

        if (!$agent) {
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0],
                'stats' => [
                    'total' => 0,
                    'identite' => 0,
                    'parcours' => 0,
                    'carriere' => 0,
                    'mission' => 0,
                ],
            ]);
        }

        $query = $agent->documents()->with('agent');

        // Optional category filter
        if ($request->filled('categorie')) {
            $query->where('type', $request->input('categorie'));
        }

        // Optional search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderByDesc('created_at')->paginate(12);

        // Compute category stats (unfiltered, for the current agent)
        $stats = [
            'total'    => $agent->documents()->count(),
            'identite' => $agent->documents()->where('type', 'identite')->count(),
            'parcours' => $agent->documents()->where('type', 'parcours')->count(),
            'carriere' => $agent->documents()->where('type', 'carriere')->count(),
            'mission'  => $agent->documents()->where('type', 'mission')->count(),
        ];

        return response()->json([
            'data'  => $documents->items(),
            'meta'  => [
                'current_page' => $documents->currentPage(),
                'last_page'    => $documents->lastPage(),
                'per_page'     => $documents->perPage(),
                'total'        => $documents->total(),
                'from'         => $documents->firstItem(),
                'to'           => $documents->lastItem(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Upload a new document.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_document'           => 'required|string|max:255',
            'fichier'                => 'required|file|max:10240',
            'categories_document_id' => 'nullable|string|in:identite,parcours,carriere,mission',
            'description'            => 'nullable|string|max:500',
        ]);

        $agent = $request->user()->agent;

        if (!$agent) {
            return response()->json(['message' => 'Aucun agent associé à ce compte.'], 422);
        }

        $file = $request->file('fichier');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $file->move(public_path('uploads/documents'), $filename);

        $document = Document::create([
            'agent_id'    => $agent->id,
            'type'        => $validated['categories_document_id'] ?? 'identite',
            'fichier'     => 'uploads/documents/' . $filename,
            'description' => ($validated['nom_document'] ?? '')
                           . (!empty($validated['description']) ? ' | ' . $validated['description'] : ''),
            'statut'      => 'valide',
        ]);

        $document->load('agent');

        return response()->json([
            'message'  => 'Document uploadé avec succès.',
            'document' => $document,
        ], 201);
    }

    /**
     * Show a single document.
     */
    public function show(Document $document)
    {
        $user = auth()->user();

        // Agent can only see their own docs, unless admin
        if ($document->agent_id !== $user->agent?->id && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $document->load('agent');

        // Add file metadata
        $filePath = public_path($document->fichier);
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        $extension = pathinfo($document->fichier, PATHINFO_EXTENSION);

        return response()->json([
            'document' => $document,
            'file_meta' => [
                'size'      => $fileSize,
                'extension' => strtolower($extension),
                'exists'    => file_exists($filePath),
            ],
        ]);
    }

    /**
     * Download a document file.
     */
    public function download(Document $document)
    {
        $user = auth()->user();

        if ($document->agent_id !== $user->agent?->id && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $filePath = public_path($document->fichier);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Delete a document and its file.
     */
    public function destroy(Document $document)
    {
        $user = auth()->user();

        // Agent can delete their own, or admin can delete any
        if ($document->agent_id !== $user->agent?->id && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $filePath = public_path($document->fichier);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $document->delete();

        return response()->json(['message' => 'Document supprimé avec succès.']);
    }
}
