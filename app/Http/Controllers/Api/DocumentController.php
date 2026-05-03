<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends ApiController
{
    /**
     * List paginated documents for the authenticated user's agent.
     */
    public function index(Request $request)
    {
        $agent = $request->user()->agent;

        if (!$agent) {
            return $this->success([], ['total' => 0], [
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

        return $this->paginated($documents, DocumentResource::class, [], [
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

        NotificationService::notifierAgent(
            $document->agent,
            'document_travail',
            'Nouveau document dans votre dossier',
            'Un nouveau document a ete ajoute a votre dossier agent : ' . ($validated['nom_document'] ?? 'document') . '.',
            '/documents/' . $document->id,
            $request->user()->id
        );

        $resource = DocumentResource::make($document);

        return $this->resource($resource, [], [
            'message' => 'Document uploadé avec succès.',
            'document' => $resource->resolve(),
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

        $resource = DocumentResource::make($document);

        return $this->resource($resource, [
            'file' => [
                'size' => $fileSize,
                'extension' => strtolower($extension),
                'exists' => file_exists($filePath),
            ],
        ], [
            'document' => $resource->resolve(),
            'file_meta' => [
                'size' => $fileSize,
                'extension' => strtolower($extension),
                'exists' => file_exists($filePath),
            ],
        ]);
    }

    /**
     * Update an existing document.
     */
    public function update(Request $request, Document $document)
    {
        $user = $request->user();

        if ($document->agent_id !== $user->agent?->id && !$user->hasAdminAccess()) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        $validated = $request->validate([
            'nom_document' => 'required|string|max:255',
            'fichier' => 'nullable|file|max:10240',
            'categories_document_id' => 'nullable|string|in:identite,parcours,carriere,mission',
            'description' => 'nullable|string|max:500',
            'statut' => 'nullable|string|max:100',
            'date_expiration' => 'nullable|date',
        ]);

        $data = [
            'type' => $validated['categories_document_id'] ?? $document->type,
            'description' => ($validated['nom_document'] ?? '')
                . (!empty($validated['description']) ? ' | ' . $validated['description'] : ''),
            'statut' => $validated['statut'] ?? $document->statut,
            'date_expiration' => $validated['date_expiration'] ?? $document->date_expiration,
        ];

        if ($request->hasFile('fichier')) {
            $oldFilePath = public_path($document->fichier);
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }

            $file = $request->file('fichier');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $file->move(public_path('uploads/documents'), $filename);
            $data['fichier'] = 'uploads/documents/' . $filename;
        }

        $document->update($data);
        $document->load('agent');

        NotificationService::notifierAgent(
            $document->agent,
            'document_travail',
            'Document de dossier mis a jour',
            'Un document de votre dossier agent a ete modifie : ' . ($validated['nom_document'] ?? 'document') . '.',
            '/documents/' . $document->id,
            $user->id
        );

        return $this->resource(DocumentResource::make($document), [], [
            'message' => 'Document mis a jour avec succes.',
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

        return $this->success(null, [], ['message' => 'Document supprimé avec succès.']);
    }
}
