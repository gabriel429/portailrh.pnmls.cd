<?php

namespace App\Http\Controllers\Api;

use App\Events\CommuniquePublished;
use App\Http\Resources\CommuniqueResource;
use App\Models\Communique;
use App\Models\CommuniqueAttachment;
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
        $communiques = Communique::with(['attachments', 'auteur'])
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
            'notify_by_mail' => 'nullable|boolean',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240',
        ]);

        $validated['auteur_id'] = auth()->id();
        $validated['actif'] = $request->boolean('actif', true);
        unset($validated['notify_by_mail'], $validated['attachments']);

        $communique = Communique::create($validated);
        $this->storeAttachments($communique, $request->file('attachments', []));

        if ($communique->actif) {
            CommuniquePublished::dispatch($communique, $request->boolean('notify_by_mail'));
        }

        $communique->load(['attachments', 'auteur']);
        $communique->loadCount('reads');
        $resource = CommuniqueResource::make($communique);

        return $this->resource($resource, [], [
            'message' => 'Communiqué publié avec succès.',
        ], 201);
    }

    /**
     * Display the specified communique.
     */
    public function show(Communique $communique): JsonResponse
    {
        $communique->load(['attachments', 'auteur']);
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
            'message' => 'Communiqué marqué comme lu.',
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
            'notify_by_mail' => 'nullable|boolean',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240',
            'remove_attachment_ids' => 'nullable|array',
            'remove_attachment_ids.*' => 'integer|exists:communique_attachments,id',
        ]);

        $validated['actif'] = $request->boolean('actif', false);
        $removeAttachmentIds = $validated['remove_attachment_ids'] ?? [];
        unset($validated['notify_by_mail'], $validated['attachments'], $validated['remove_attachment_ids']);

        $communique->update($validated);
        $this->deleteAttachments($communique, $removeAttachmentIds);
        $this->storeAttachments($communique, $request->file('attachments', []));

        if ($communique->actif && $request->boolean('notify_by_mail')) {
            \App\Services\NotificationService::envoyerEmailAgentsProfessionnels(
                'Communiqué mis à jour',
                'Le communiqué "' . $communique->titre . '" a été mis à jour.',
                '/communiques/' . $communique->id
            );
        }

        $resource = CommuniqueResource::make($communique->fresh()->load(['attachments', 'auteur'])->loadCount('reads'));

        return $this->resource($resource, [], [
            'message' => 'Communiqué mis à jour avec succès.',
        ]);
    }

    /**
     * Remove the specified communique.
     */
    public function destroy(Communique $communique): JsonResponse
    {
        $communique->attachments()->get()->each->delete();
        $communique->delete();

        return $this->success(null, [], [
            'message' => 'Communiqué supprimé.',
        ]);
    }

    private function storeAttachments(Communique $communique, array $files): void
    {
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('communiques/' . $communique->id, 'public');

            $communique->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function deleteAttachments(Communique $communique, array $attachmentIds): void
    {
        if (empty($attachmentIds)) {
            return;
        }

        CommuniqueAttachment::query()
            ->where('communique_id', $communique->id)
            ->whereIn('id', $attachmentIds)
            ->get()
            ->each
            ->delete();
    }
}
