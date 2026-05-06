<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ForumPostResource;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumPostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:80',
            'per_page' => 'nullable|integer|min:1|max:30',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 12);

        $posts = ForumPost::query()
            ->with(['user.role', 'user.agent.role', 'user.agent.departement', 'agent.role', 'agent.departement'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('titre', 'like', '%' . $search . '%')
                        ->orWhere('contenu', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('agent', function ($agentQuery) use ($search) {
                            $agentQuery
                                ->where('nom', 'like', '%' . $search . '%')
                                ->orWhere('prenom', 'like', '%' . $search . '%')
                                ->orWhere('postnom', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        return $this->paginated($posts, ForumPostResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'nullable|string|max:160',
            'contenu' => 'required|string|min:2|max:3000',
        ]);

        $user = $request->user();

        $post = ForumPost::create([
            'user_id' => $user->id,
            'agent_id' => $user->agent_id,
            'titre' => $validated['titre'] ?? null,
            'contenu' => $validated['contenu'],
        ]);

        $post->load(['user.role', 'user.agent.role', 'user.agent.departement', 'agent.role', 'agent.departement']);

        return $this->resource(ForumPostResource::make($post), [], [
            'message' => 'Message publie avec succes.',
        ], 201);
    }

    public function destroy(Request $request, ForumPost $forumPost): JsonResponse
    {
        if (! $this->canDelete($request->user(), $forumPost)) {
            abort(403, 'Vous ne pouvez pas supprimer ce message.');
        }

        $forumPost->delete();

        return $this->success(null, [], [
            'message' => 'Message supprime.',
        ]);
    }

    private function canDelete(User $user, ForumPost $post): bool
    {
        return $user->id === $post->user_id
            || $user->isSuperAdmin()
            || $user->hasAdminAccess();
    }
}
