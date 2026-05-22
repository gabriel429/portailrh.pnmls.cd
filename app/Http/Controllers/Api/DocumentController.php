<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DocumentResource;
use App\Models\Agent;
use App\Models\Document;
use App\Services\NotificationService;
use App\Services\RoleService;
use App\Services\UserDataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentController extends ApiController
{
    private const DOCUMENT_CATEGORY_VALUES = [
        'identite',
        'parcours',
        'carriere',
        'gestion_rh',
        'documents_legaux',
        'autres',
        'mission',
    ];

    private const DOCUMENT_CATEGORY_STATS = [
        'identite',
        'parcours',
        'carriere',
        'gestion_rh',
        'documents_legaux',
        'autres',
    ];

    private const LEGACY_DOCUMENT_CATEGORY_MAP = [
        'mission' => 'autres',
    ];

    private array $documentManagerRoles = [
        'Section ressources humaines',
        'Chef Section RH',
        'RH National',
        'RH Provincial',
        'Assistant RH',
        'Assistant ressources humaines',
        'Assistant ressource humaine',
    ];

    private function normalizeDocumentCategory(?string $category): string
    {
        $category = trim((string) $category);

        if ($category === '') {
            return 'identite';
        }

        return self::LEGACY_DOCUMENT_CATEGORY_MAP[$category] ?? $category;
    }

    private function applyDocumentCategoryFilter($query, ?string $category): void
    {
        $category = $this->normalizeDocumentCategory($category);

        if ($category === 'autres') {
            $query->whereIn('type', ['autres', 'mission']);

            return;
        }

        $query->where('type', $category);
    }

    private function documentStatsForAgent(Agent $agent): array
    {
        $stats = ['total' => $agent->documents()->count()];

        foreach (self::DOCUMENT_CATEGORY_STATS as $category) {
            $stats[$category] = $category === 'autres'
                ? $agent->documents()->whereIn('type', ['autres', 'mission'])->count()
                : $agent->documents()->where('type', $category)->count();
        }

        $stats['mission'] = $stats['autres'];

        return $stats;
    }

    private function canManageAgentDocuments($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasRole($this->documentManagerRoles) || app(RoleService::class)->isAssistantRh($user)) {
            return true;
        }

        $role = Str::lower(Str::ascii(trim((string) ($user->role?->nom_role ?? ''))));

        return in_array($role, ['rh', 'ressources humaines', 'chef de section rh', 'chef section ressources humaines'], true)
            || str_contains($role, 'ressource humaine')
            || str_contains($role, 'ressources humaines');
    }

    private function canAccessDocument($user, Document $document, bool $allowOwn = true): bool
    {
        if ($allowOwn && (int) ($user?->agent?->id ?? 0) === (int) $document->agent_id) {
            return true;
        }

        if (!$this->canManageAgentDocuments($user)) {
            return false;
        }

        $agent = $document->relationLoaded('agent') ? $document->agent : $document->agent()->first();

        return app(UserDataScope::class)->canAccessAgent($user, $agent, false);
    }

    private function ensureCanManageAgentDocuments(Request $request, Agent $agent): void
    {
        $user = $request->user();

        if (!$this->canManageAgentDocuments($user)) {
            abort(403, 'Seule la Section Ressources Humaines peut ajouter ou gerer les documents agent.');
        }

        if (!app(UserDataScope::class)->canAccessAgent($user, $agent, false)) {
            abort(403, 'Vous n\'avez pas accès au dossier de cet agent.');
        }
    }

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
                    'gestion_rh' => 0,
                    'documents_legaux' => 0,
                    'autres' => 0,
                    'mission' => 0,
                ],
            ]);
        }

        $query = $agent->documents()->with('agent');

        // Optional category filter
        if ($request->filled('categorie')) {
            $this->applyDocumentCategoryFilter($query, $request->input('categorie'));
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
        $stats = $this->documentStatsForAgent($agent);

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
            'agent_id'                => 'required|integer|exists:agents,id',
            'nom_document'           => 'required|string|max:255',
            'fichier'                => 'required|file|max:10240',
            'categories_document_id' => ['nullable', 'string', Rule::in(self::DOCUMENT_CATEGORY_VALUES)],
            'description'            => 'nullable|string|max:500',
            'notify_by_mail'         => 'nullable|boolean',
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);
        $this->ensureCanManageAgentDocuments($request, $agent);

        if (!$agent) {
            return response()->json(['message' => 'Aucun agent associé à ce compte.'], 422);
        }

        $file = $request->file('fichier');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $file->move(public_path('uploads/documents'), $filename);

        $document = Document::create([
            'agent_id'    => $agent->id,
            'type'        => $this->normalizeDocumentCategory($validated['categories_document_id'] ?? 'identite'),
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
            'Un nouveau document a été ajoute a votre dossier agent : ' . ($validated['nom_document'] ?? 'document') . '.',
            '/documents/' . $document->id,
            $request->user()->id,
            $request->boolean('notify_by_mail')
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
        $document->load('agent');

        if (!$this->canAccessDocument($user, $document)) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
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
        $document->load('agent');

        if (!$this->canAccessDocument($user, $document, false)) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        $validated = $request->validate([
            'nom_document' => 'required|string|max:255',
            'fichier' => 'nullable|file|max:10240',
            'categories_document_id' => ['nullable', 'string', Rule::in(self::DOCUMENT_CATEGORY_VALUES)],
            'description' => 'nullable|string|max:500',
            'statut' => 'nullable|string|max:100',
            'date_expiration' => 'nullable|date',
            'notify_by_mail' => 'nullable|boolean',
        ]);

        $data = [
            'type' => array_key_exists('categories_document_id', $validated)
                ? $this->normalizeDocumentCategory($validated['categories_document_id'])
                : $document->type,
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
            'Document de dossier mis à jour',
            'Un document de votre dossier agent a été modifié : ' . ($validated['nom_document'] ?? 'document') . '.',
            '/documents/' . $document->id,
            $user->id,
            $request->boolean('notify_by_mail')
        );

        return $this->resource(DocumentResource::make($document), [], [
            'message' => 'Document mis à jour avec succes.',
        ]);
    }

    /**
     * Download a document file.
     */
    public function download(Document $document)
    {
        $user = auth()->user();

        if (!$this->canAccessDocument($user, $document)) {
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

        if (!$this->canAccessDocument($user, $document, false)) {
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
